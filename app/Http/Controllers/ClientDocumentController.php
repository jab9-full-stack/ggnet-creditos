<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientDocument;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientDocumentController extends Controller
{
    public function index(Request $request, Client $client): View
    {
        abort_unless($request->user()?->can('client_documents.view'), 403);

        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $documents = $client->documents()
            ->with(['uploadedBy:id,name,email', 'verifiedBy:id,name,email'])
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('original_name', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('client-documents.index', [
            'client' => $client->load('agency:id,code,name'),
            'documents' => $documents,
            'type' => $type,
            'status' => $status,
            'search' => $search,
            'types' => $this->types(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(Request $request, Client $client): View
    {
        abort_unless($request->user()?->can('client_documents.create'), 403);

        return view('client-documents.create', [
            'client' => $client,
            'document' => new ClientDocument([
                'type' => 'dpi_front',
                'status' => 'pending',
            ]),
            'types' => $this->types(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request, Client $client, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('client_documents.create'), 403);

        $data = $this->validateDocumentCreate($request);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $fileName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs("clients/{$client->id}/documents", $fileName, 'local');

        $document = $client->documents()->create([
            'type' => $data['type'],
            'title' => $data['title'],
            'original_name' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize() ?: 0,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
            'verified_by' => $data['status'] === 'verified' ? $request->user()?->id : null,
            'verified_at' => $data['status'] === 'verified' ? now() : null,
        ]);

        $auditLogger->created($document, 'client_documents', [
            'action' => 'client_document.created',
            'client_id' => $client->id,
            'client_code' => $client->code,
            'type' => $document->type,
            'file_name' => $document->original_name,
        ]);

        return redirect()
            ->route('clients.documents.index', $client)
            ->with('status', 'Documento cargado correctamente.');
    }

    public function edit(Request $request, Client $client, ClientDocument $document): View
    {
        abort_unless($request->user()?->can('client_documents.update'), 403);
        $this->ensureDocumentBelongsToClient($client, $document);

        return view('client-documents.edit', [
            'client' => $client,
            'document' => $document,
            'types' => $this->types(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, Client $client, ClientDocument $document, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('client_documents.update'), 403);
        $this->ensureDocumentBelongsToClient($client, $document);

        $data = $this->validateDocumentUpdate($request);

        $data['updated_by'] = $request->user()?->id;

        if ($data['status'] === 'verified' && $document->status !== 'verified') {
            $data['verified_by'] = $request->user()?->id;
            $data['verified_at'] = now();
        }

        if ($data['status'] !== 'verified') {
            $data['verified_by'] = null;
            $data['verified_at'] = null;
        }

        $oldValues = $document->only(array_keys($data));

        $document->fill($data);
        $document->save();

        $auditLogger->updated(
            model: $document,
            oldValues: $oldValues,
            newValues: $document->fresh()->only(array_keys($data)),
            module: 'client_documents',
            context: [
                'action' => 'client_document.updated',
                'client_id' => $client->id,
                'client_code' => $client->code,
            ],
        );

        return redirect()
            ->route('clients.documents.index', $client)
            ->with('status', 'Documento actualizado correctamente.');
    }

    public function download(Request $request, Client $client, ClientDocument $document): StreamedResponse
    {
        abort_unless($request->user()?->can('client_documents.download'), 403);
        $this->ensureDocumentBelongsToClient($client, $document);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name
        );
    }

    public function destroy(Request $request, Client $client, ClientDocument $document, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('client_documents.delete'), 403);
        $this->ensureDocumentBelongsToClient($client, $document);

        $auditLogger->deleted($document, 'client_documents', [
            'action' => 'client_document.deleted',
            'client_id' => $client->id,
            'client_code' => $client->code,
            'type' => $document->type,
            'file_name' => $document->original_name,
            'physical_file_kept' => true,
        ]);

        $document->delete();

        return redirect()
            ->route('clients.documents.index', $client)
            ->with('status', 'Documento eliminado del expediente activo. El archivo físico se conserva para trazabilidad.');
    }

    private function validateDocumentCreate(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys($this->types()))],
            'title' => ['required', 'string', 'max:180'],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], $this->messages());
    }

    private function validateDocumentUpdate(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys($this->types()))],
            'title' => ['required', 'string', 'max:180'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], $this->messages());
    }

    private function messages(): array
    {
        return [
            'type.required' => 'El tipo de documento es obligatorio.',
            'type.in' => 'El tipo de documento seleccionado no es válido.',
            'title.required' => 'El título del documento es obligatorio.',
            'file.required' => 'Debes seleccionar un archivo.',
            'file.file' => 'El archivo no es válido.',
            'file.mimes' => 'Solo se permiten archivos JPG, JPEG, PNG, WEBP o PDF.',
            'file.max' => 'El archivo no puede superar 10 MB.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'notes.max' => 'Las observaciones no pueden superar 2000 caracteres.',
        ];
    }

    private function types(): array
    {
        return [
            'dpi_front' => 'DPI frontal',
            'dpi_back' => 'DPI reverso',
            'nit' => 'NIT',
            'receipt' => 'Recibo / comprobante',
            'guarantee' => 'Garantía',
            'other' => 'Otro',
        ];
    }

    private function statuses(): array
    {
        return [
            'pending' => 'Pendiente',
            'verified' => 'Verificado',
            'rejected' => 'Rechazado',
        ];
    }

    private function ensureDocumentBelongsToClient(Client $client, ClientDocument $document): void
    {
        abort_unless((int) $document->client_id === (int) $client->id, 404);
    }
}
