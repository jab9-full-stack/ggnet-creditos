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
            grid-template-columns: 280px minmax(0, 1fr);
            overflow-x: hidden;
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

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 30;
        }

        .main {
            position: relative;
            min-width: 0;
            max-width: 100%;
            overflow-x: hidden;
            transition: opacity .16s ease, transform .16s ease;
        }

        .main.is-soft-loading {
            opacity: .92;
            transform: translateY(1px);
        }

        .main.is-soft-ready {
            animation: soft-main-in .16s ease-out;
        }

        .page-loader {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 280px;
            width: calc(100vw - 280px);
            max-width: calc(100vw - 280px);
            overflow: hidden;
            z-index: 20;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at center, rgba(14, 165, 233, .13), transparent 380px),
                rgba(3, 37, 52, .74);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .16s ease, visibility .16s ease;
            backdrop-filter: blur(2px);
        }

        .page-loader.is-active,
        .page-loader.is-complete {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .loader-core {
            position: relative;
            width: min(38vw, 230px);
            aspect-ratio: 1;
            display: grid;
            place-items: center;
            transform: scale(.98);
            opacity: .96;
            transition: transform .16s ease, opacity .16s ease;
        }

        .page-loader.is-active .loader-core,
        .page-loader.is-complete .loader-core {
            transform: scale(1);
            opacity: 1;
        }

        .loader-ring {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            border: 2px solid rgba(125, 240, 255, .38);
            box-shadow:
                0 0 16px rgba(125, 240, 255, .34),
                inset 0 0 16px rgba(125, 240, 255, .18);
        }

        .loader-ring::before {
            content: "";
            position: absolute;
            inset: -5px;
            border-radius: inherit;
            border: 4px solid transparent;
            border-top-color: #d9fbff;
            border-right-color: rgba(155, 238, 255, .62);
            filter: drop-shadow(0 0 8px rgba(125, 240, 255, .72));
            animation: loader-spin .85s linear infinite;
        }

        .page-loader.is-complete .loader-ring::before {
            animation-duration: .36s;
            border-color: rgba(217, 251, 255, .85);
        }

        .loader-network {
            position: absolute;
            width: 54%;
            height: 54%;
            opacity: .42;
            border: 1px solid rgba(155, 238, 255, .32);
            clip-path: polygon(50% 0%, 95% 34%, 78% 92%, 24% 92%, 5% 34%);
            background:
                linear-gradient(35deg, transparent 49%, rgba(155,238,255,.32) 50%, transparent 51%),
                linear-gradient(145deg, transparent 49%, rgba(155,238,255,.26) 50%, transparent 51%),
                radial-gradient(circle at 50% 0%, rgba(155,238,255,.28) 0 4px, transparent 5px),
                radial-gradient(circle at 95% 34%, rgba(155,238,255,.24) 0 4px, transparent 5px),
                radial-gradient(circle at 78% 92%, rgba(155,238,255,.24) 0 4px, transparent 5px),
                radial-gradient(circle at 24% 92%, rgba(155,238,255,.24) 0 4px, transparent 5px),
                radial-gradient(circle at 5% 34%, rgba(155,238,255,.24) 0 4px, transparent 5px),
                rgba(155,238,255,.055);
            box-shadow: inset 0 0 22px rgba(155,238,255,.10);
            animation: loader-pulse 1.15s ease-in-out infinite;
        }

        .loader-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #d9fbff;
            box-shadow: 0 0 12px rgba(155,238,255,.82);
        }

        .loader-dot:nth-child(1) { top: 17%; left: 50%; }
        .loader-dot:nth-child(2) { top: 34%; right: 19%; }
        .loader-dot:nth-child(3) { right: 14%; bottom: 30%; }
        .loader-dot:nth-child(4) { bottom: 13%; left: 48%; }
        .loader-dot:nth-child(5) { top: 42%; left: 16%; }

        .loader-text {
            position: relative;
            z-index: 2;
            color: #e6fcff;
            font-size: clamp(21px, 3vw, 32px);
            letter-spacing: .06em;
            font-weight: 300;
            text-shadow:
                0 0 8px rgba(155,238,255,.85),
                0 0 18px rgba(34,211,238,.65);
        }

        .loader-subtext {
            display: none;
        }

        @keyframes loader-spin {
            to { transform: rotate(360deg); }
        }

        @keyframes loader-pulse {
            0%, 100% { transform: scale(.96); opacity: .28; }
            50% { transform: scale(1.03); opacity: .44; }
        }

        @keyframes soft-main-in {
            from { opacity: .96; transform: translateY(1px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 900px) {
            .sidebar {
                position: static;
                height: auto;
            }

            .page-loader {
                left: 0;
                top: 92px;
                width: 100vw;
                max-width: 100vw;
            }

            .loader-core {
                width: min(68vw, 240px);
            }
        }



    </style>
</head>
<body>
    <div id="app-content">
        {{ $slot ?? '' }}
        @yield('content')
    </div>

    <div class="page-loader" id="page-loader" aria-live="polite" aria-hidden="true">
        <div class="loader-core">
            <div class="loader-ring"></div>
            <div class="loader-network"></div>
            <span class="loader-dot"></span>
            <span class="loader-dot"></span>
            <span class="loader-dot"></span>
            <span class="loader-dot"></span>
            <span class="loader-dot"></span>
            <div class="loader-text" id="page-loader-text">CARGANDO</div>
            <div class="loader-subtext" id="page-loader-subtext">Procesando solicitud</div>
        </div>
    </div>

    @livewireScripts

    <script>
        (function () {
            const loader = document.getElementById('page-loader');
            const loaderText = document.getElementById('page-loader-text');
            const loaderSubtext = document.getElementById('page-loader-subtext');
            const appContent = document.getElementById('app-content');

            let softNavInProgress = false;
            let loaderHideTimer = null;

            function currentMain() {
                return document.querySelector('#app-content .main');
            }

            function showLoader(text = 'Cargando', subtext = '') {
                if (!loader) return;

                window.clearTimeout(loaderHideTimer);

                loaderText.textContent = text;
                loaderSubtext.textContent = '';
                loader.classList.remove('is-complete');
                loader.classList.add('is-active');
                loader.setAttribute('aria-hidden', 'false');

                const main = currentMain();
                if (main) {
                    main.classList.add('is-soft-loading');
                    main.classList.remove('is-soft-ready');
                }
            }

            function completeLoader() {
                if (!loader) return;

                loaderText.textContent = 'Listo';
                loaderSubtext.textContent = '';
                loader.classList.remove('is-active');
                loader.classList.add('is-complete');

                const main = currentMain();
                if (main) {
                    main.classList.remove('is-soft-loading');
                    main.classList.add('is-soft-ready');

                    window.setTimeout(function () {
                        main.classList.remove('is-soft-ready');
                    }, 180);
                }

                loaderHideTimer = window.setTimeout(function () {
                    loader.classList.remove('is-complete');
                    loader.setAttribute('aria-hidden', 'true');
                }, 220);
            }

            function hideLoader() {
                if (!loader) return;

                window.clearTimeout(loaderHideTimer);
                loader.classList.remove('is-active', 'is-complete');
                loader.setAttribute('aria-hidden', 'true');

                const main = currentMain();
                if (main) {
                    main.classList.remove('is-soft-loading', 'is-soft-ready');
                }
            }

            function shouldHandleLink(link) {
                if (!link) return false;
                if (link.target && link.target !== '_self') return false;
                if (link.hasAttribute('download')) return false;
                if (link.dataset.noSoftNav === 'true') return false;

                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                    return false;
                }

                const url = new URL(link.href, window.location.origin);

                if (url.origin !== window.location.origin) return false;
                if (url.pathname.startsWith('/livewire-')) return false;

                return true;
            }

            function updateSidebarActive(url) {
                const path = new URL(url, window.location.origin).pathname;

                document.querySelectorAll('.sidebar .nav-link[href]').forEach(function (link) {
                    const linkPath = new URL(link.href, window.location.origin).pathname;
                    const isAgencies = path.startsWith('/agencies') && linkPath === '/agencies';
                    const isExact = path === linkPath;

                    link.classList.toggle('active', isExact || isAgencies);
                });
            }

            async function softNavigate(url, push = true) {
                if (softNavInProgress) return;

                const existingMain = currentMain();

                if (!existingMain) {
                    window.location.href = url;
                    return;
                }

                softNavInProgress = true;
                showLoader();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html, application/xhtml+xml'
                        },
                        credentials: 'same-origin'
                    });

                    if (!response.ok) {
                        window.location.href = url;
                        return;
                    }

                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const nextMain = doc.querySelector('#app-content .main');

                    if (!nextMain) {
                        window.location.href = url;
                        return;
                    }

                    document.title = doc.title || document.title;

                    existingMain.innerHTML = nextMain.innerHTML;

                    if (push) {
                        history.pushState({ softNav: true }, '', url);
                    }

                    updateSidebarActive(url);
                    window.scrollTo({ top: 0, behavior: 'instant' });
                    initializePageBehaviors();
                    completeLoader();
                } catch (error) {
                    console.error('Soft navigation failed:', error);
                    window.location.href = url;
                } finally {
                    softNavInProgress = false;
                }
            }

            function initializeAgencyForm() {
                const nameInput = document.getElementById('agency-name');
                const codeInput = document.getElementById('agency-code');
                const numericInputs = document.querySelectorAll('.only-numbers');

                function generateCode(value) {
                    return (value || '')
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toUpperCase()
                        .replace(/[^A-Z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '')
                        .slice(0, 30);
                }

                if (nameInput && codeInput && nameInput.dataset.codeBound !== 'true') {
                    nameInput.dataset.codeBound = 'true';

                    const syncCode = function () {
                        codeInput.value = generateCode(nameInput.value);
                    };

                    nameInput.addEventListener('input', syncCode);

                    if (!codeInput.value && nameInput.value) {
                        syncCode();
                    }
                }

                numericInputs.forEach(function (input) {
                    if (input.dataset.numericBound === 'true') return;

                    input.dataset.numericBound = 'true';

                    input.addEventListener('input', function () {
                        input.value = input.value.replace(/[^0-9]/g, '');
                    });

                    input.addEventListener('paste', function (event) {
                        event.preventDefault();

                        const text = (event.clipboardData || window.clipboardData)
                            .getData('text')
                            .replace(/[^0-9]/g, '');

                        document.execCommand('insertText', false, text);
                    });
                });
            }

            function initializePageBehaviors() {
                initializeAgencyForm();

                document.querySelectorAll('form').forEach(function (form) {
                    if (form.dataset.loaderBound === 'true') return;

                    form.dataset.loaderBound = 'true';

                    form.addEventListener('submit', function () {
                        showLoader('Cargando');
                    });
                });
            }

            document.addEventListener('click', function (event) {
                const link = event.target.closest('a');

                if (!shouldHandleLink(link)) return;

                event.preventDefault();
                softNavigate(link.href);
            });

            window.addEventListener('popstate', function () {
                softNavigate(window.location.href, false);
            });

            window.addEventListener('pageshow', function () {
                hideLoader();
                updateSidebarActive(window.location.href);
            });

            initializePageBehaviors();
            updateSidebarActive(window.location.href);

            window.BienestarUI = {
                showLoader,
                completeLoader,
                hideLoader,
                softNavigate,
                initializePageBehaviors
            };
        })();
    </script>
</body>
</html>
