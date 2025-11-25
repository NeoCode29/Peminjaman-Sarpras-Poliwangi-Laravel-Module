<header class="page-header">
    <h1 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--text-main);">
        @yield('page-title', 'Dashboard')
    </h1>
    @hasSection('page-subtitle')
        <p style="margin: 0; font-size: 0.95rem; color: var(--text-muted);">
            @yield('page-subtitle')
        </p>
    @endif
</header>
