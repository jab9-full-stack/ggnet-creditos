<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\ClientReferenceController;
use App\Http\Controllers\CreditRequestController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\CreditDisbursementController;
use App\Http\Controllers\CreditInstallmentOverdueController;
use App\Http\Controllers\CreditLateFeeController;
use App\Http\Controllers\CreditLateFeeSettingController;
use App\Http\Controllers\CreditPaymentController;
use App\Http\Controllers\CreditPaymentReceiptController;
use App\Http\Controllers\CreditPaymentVoidController;
use App\Http\Controllers\CreditRequestStatusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::resource('agencies', AgencyController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:agencies.view');

    Route::resource('users', UserController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:users.view');

    Route::resource('clients', ClientController::class)
        ->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:clients.view');

    Route::post('/credit-requests/{creditRequest}/submit', [CreditRequestStatusController::class, 'submit'])
        ->middleware('permission:credit_requests.update')
        ->name('credit-requests.submit');

    Route::post('/credit-requests/{creditRequest}/start-review', [CreditRequestStatusController::class, 'startReview'])
        ->middleware('permission:credit_requests.review')
        ->name('credit-requests.start-review');

    Route::post('/credit-requests/{creditRequest}/approve', [CreditRequestStatusController::class, 'approve'])
        ->middleware('permission:credit_requests.approve')
        ->name('credit-requests.approve');

    Route::post('/credit-requests/{creditRequest}/reject', [CreditRequestStatusController::class, 'reject'])
        ->middleware('permission:credit_requests.reject')
        ->name('credit-requests.reject');

    Route::post('/credit-requests/{creditRequest}/cancel', [CreditRequestStatusController::class, 'cancel'])
        ->middleware('permission:credit_requests.update')
        ->name('credit-requests.cancel');

    Route::resource('credit-requests', CreditRequestController::class)
        ->parameters(['credit-requests' => 'creditRequest'])
        ->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:credit_requests.view');

    Route::post('/credits/{credit}/disburse', [CreditDisbursementController::class, 'store'])
        ->middleware('permission:credits.disburse')
        ->name('credits.disburse');

    Route::post('/credits/{credit}/payments', [CreditPaymentController::class, 'store'])
        ->middleware('permission:credit_payments.create')
        ->name('credits.payments.store');

    Route::post('/credits/{credit}/mark-overdue', [CreditInstallmentOverdueController::class, 'store'])
        ->middleware('permission:credit_installments.mark_overdue')
        ->name('credits.installments.mark-overdue');

    Route::post('/credits/{credit}/apply-late-fees', [CreditLateFeeController::class, 'store'])
        ->middleware('permission:credit_installments.apply_late_fee')
        ->name('credits.installments.apply-late-fees');

    Route::post('/credits/{credit}/payments/{payment}/void', [CreditPaymentVoidController::class, 'store'])
        ->middleware('permission:credit_payments.void')
        ->name('credits.payments.void');

    Route::get('/credits/{credit}/payments/{payment}/receipt', [CreditPaymentReceiptController::class, 'show'])
        ->middleware('permission:credit_payment_receipts.view')
        ->name('credits.payments.receipt.show');

    Route::resource('credits', CreditController::class)
        ->only(['index', 'show'])
        ->middleware('permission:credits.view');



    Route::get('/clients/{client}/documents/{document}/download', [ClientDocumentController::class, 'download'])
        ->middleware('permission:client_documents.download')
        ->name('clients.documents.download');

    Route::resource('clients.references', ClientReferenceController::class)
        ->parameters(['references' => 'reference'])
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:client_references.view');


    Route::resource('clients.documents', ClientDocumentController::class)
        ->parameters(['documents' => 'document'])
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:client_documents.view');

    Route::get('/cash', [CashSessionController::class, 'index'])
        ->middleware('permission:cash.view')
        ->name('cash.index');

    Route::post('/cash/open', [CashSessionController::class, 'open'])
        ->middleware('permission:cash.open')
        ->name('cash.open');

    Route::post('/cash/{cashSession}/close', [CashSessionController::class, 'close'])
        ->middleware('permission:cash.close')
        ->name('cash.close');

    Route::post('/cash/{cashSession}/movements', [CashMovementController::class, 'store'])
        ->middleware('permission:cash_movements.create')
        ->name('cash.movements.store');

    Route::post('/cash/{cashSession}/movements/{movement}/void', [CashMovementController::class, 'voidMovement'])
        ->middleware('permission:cash_movements.void')
        ->name('cash.movements.void');


    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('permission:audit_logs.view')
        ->name('audit-logs.index');

    Route::get('/settings/credit-late-fees', [CreditLateFeeSettingController::class, 'edit'])
        ->middleware('permission:settings.view')
        ->name('settings.credit-late-fees.edit');

    Route::put('/settings/credit-late-fees', [CreditLateFeeSettingController::class, 'update'])
        ->middleware('permission:settings.update')
        ->name('settings.credit-late-fees.update');

    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('permission:settings.view')
        ->name('settings.index');

    Route::put('/settings/{setting}', [SettingController::class, 'update'])
        ->middleware('permission:settings.update')
        ->name('settings.update');
});
