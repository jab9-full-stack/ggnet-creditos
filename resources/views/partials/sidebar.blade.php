<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark">B</div>
        <div>
            <div>BIENESTAR</div>
            <div style="font-size:12px; color:rgba(255,255,255,.58); font-weight:600;">GGNET Créditos</div>
        </div>
    </div>

    <nav class="nav-group">
        @can('dashboard.view')
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
        @endcan

        @can('agencies.view')
            <a class="nav-link {{ request()->routeIs('agencies.*') ? 'active' : '' }}" href="{{ route('agencies.index') }}">Agencias</a>
        @endcan

        @can('users.view')
            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Usuarios</a>
        @endcan

        @can('audit_logs.view')
            <a class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" href="{{ route('audit-logs.index') }}">Bitácora</a>
        @endcan

        @can('settings.view')
            <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">Parámetros</a>
        @endcan
    </nav>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:auto;">
        @csrf
        <button class="btn btn-ghost" type="submit" style="width:100%;">
            Cerrar sesión
        </button>
    </form>
</aside>
