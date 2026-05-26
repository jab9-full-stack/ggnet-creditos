<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @livewireStyles

    <style>
        :root {
            --bg: #f6f7fb;
            --panel: #ffffff;
            --panel-soft: #f9fafb;
            --text: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --brand: #0f766e;
            --brand-dark: #115e59;
            --danger: #b91c1c;
            --warning: #92400e;
            --success: #047857;
            --shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(20, 184, 166, .16), transparent 32rem),
                radial-gradient(circle at top right, rgba(15, 118, 110, .10), transparent 28rem),
                var(--bg);
            color: var(--text);
        }

        a { color: inherit; text-decoration: none; }

        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.05fr .95fr;
        }

        .auth-brand {
            padding: 56px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: linear-gradient(145deg, #064e3b, #0f766e);
            color: white;
        }

        .auth-brand h1 {
            margin: 0;
            font-size: clamp(34px, 5vw, 58px);
            line-height: 1;
            letter-spacing: -.045em;
        }

        .auth-brand p {
            max-width: 620px;
            color: rgba(255,255,255,.78);
            font-size: 17px;
            line-height: 1.7;
        }

        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: fit-content;
            padding: 10px 14px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 999px;
            background: rgba(255,255,255,.10);
            font-size: 14px;
            font-weight: 700;
        }

        .auth-card-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        .auth-card {
            width: min(100%, 460px);
            background: rgba(255,255,255,.92);
            border: 1px solid rgba(229,231,235,.9);
            border-radius: 28px;
            box-shadow: var(--shadow);
            padding: 34px;
            backdrop-filter: blur(16px);
        }

        .app-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 280px 1fr;
        }

        .sidebar {
            background: #0b1220;
            color: white;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .logo-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #14b8a6, #0f766e);
            color: white;
            font-weight: 900;
        }

        .nav-group {
            display: grid;
            gap: 8px;
        }

        .nav-link {
            padding: 11px 12px;
            border-radius: 14px;
            color: rgba(255,255,255,.74);
            font-size: 14px;
            font-weight: 650;
        }

        .nav-link.active,
        .nav-link:hover {
            background: rgba(255,255,255,.10);
            color: white;
        }

        .main {
            padding: 28px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 26px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 10px 32px rgba(15, 23, 42, .045);
        }

        .panel-body { padding: 24px; }

        .grid {
            display: grid;
            gap: 18px;
        }

        .grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .metric {
            padding: 22px;
            border-radius: 22px;
            border: 1px solid var(--line);
            background: var(--panel);
        }

        .metric span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .metric strong {
            display: block;
            margin-top: 10px;
            font-size: 32px;
            letter-spacing: -.04em;
        }

        .page-title {
            margin: 0;
            font-size: 28px;
            letter-spacing: -.04em;
        }

        .page-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            line-height: 1.5;
        }

        .form-group {
            display: grid;
            gap: 8px;
            margin-top: 18px;
        }

        .label {
            font-size: 13px;
            font-weight: 750;
            color: #374151;
        }

        .input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            padding: 13px 14px;
            font-size: 15px;
            outline: none;
            background: white;
        }

        .input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 4px rgba(20,184,166,.13);
        }

        .btn {
            border: 0;
            border-radius: 14px;
            padding: 12px 16px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--brand);
            color: white;
        }

        .btn-primary:hover { background: var(--brand-dark); }

        .btn-ghost {
            background: transparent;
            color: #d1d5db;
        }

        .btn-ghost:hover { background: rgba(255,255,255,.08); }

        .error-box {
            margin-top: 16px;
            padding: 13px 14px;
            border-radius: 16px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--danger);
            font-size: 14px;
            line-height: 1.5;
        }

        .status-box {
            margin-top: 16px;
            padding: 13px 14px;
            border-radius: 16px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: var(--success);
            font-size: 14px;
        }

        .muted { color: var(--muted); }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 13px 12px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            font-size: 14px;
        }

        .table th {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        @media (max-width: 900px) {
            .auth-shell,
            .app-shell {
                grid-template-columns: 1fr;
            }

            .auth-brand {
                padding: 30px;
                min-height: 320px;
            }

            .sidebar {
                position: static;
            }

            .main {
                padding: 18px;
            }

            .grid-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    {{ $slot ?? '' }}
    @yield('content')

    @livewireScripts
</body>
</html>
