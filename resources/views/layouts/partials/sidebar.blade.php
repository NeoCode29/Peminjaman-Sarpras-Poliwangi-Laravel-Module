<aside class="app-shell__sidebar" data-sidebar>
    <div class="sidebar">
        <div class="sidebar__top">
            <div class="sidebar__brand">
                {{-- Logo / Brand Name --}}
                <h1 style="color: var(--sidebar-text); font-size: 1.2rem; font-weight: 700; margin: 0;">
                    {{ config('app.name', 'Laravel') }}
                </h1>
            </div>
            <button class="sidebar__toggle" type="button" aria-expanded="false" aria-label="Toggle menu" data-sidebar-toggle>
                <span class="sidebar__toggle-icon" data-sidebar-toggle-icon></span>
            </button>
        </div>

        <div class="sidebar__divider sidebar__divider--outside"></div>

        <div class="sidebar__panel" data-sidebar-panel>
            <div class="sidebar__divider sidebar__divider--inside"></div>

            {{-- User Card --}}
            <div class="sidebar__user-card">
                <div class="sidebar__user-avatar" style="background-color: var(--brand-accent); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem;">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="sidebar__user-info">
                    <div style="color: var(--sidebar-text); font-weight: 600; font-size: 0.95rem;">
                        {{ auth()->user()->name ?? 'User' }}
                    </div>
                    <div style="color: var(--sidebar-text); opacity: 0.7; font-size: 0.85rem;">
                        {{ auth()->user()->roles->first()->name ?? 'User' }}
                    </div>
                </div>
            </div>

            <div class="sidebar__divider"></div>

            {{-- Navigation Menu --}}
            <div class="sidebar__menu">
                @php
                    $menuItems = \App\Models\Menu::getSidebarMenus(auth()->id());
                @endphp

                @foreach($menuItems as $item)
                    {{-- Check permission --}}
                    @if(!$item['permission'] || auth()->user()->hasPermissionTo($item['permission']))
                        @php
                            $isActive = false;
                            
                            // Check active routes
                            if (!empty($item['active_routes'])) {
                                foreach ($item['active_routes'] as $activeRoute) {
                                    if (request()->routeIs($activeRoute)) {
                                        $isActive = true;
                                        break;
                                    }
                                }
                            } else {
                                $isActive = request()->routeIs($item['route'] . '*');
                            }
                        @endphp

                        {{-- Menu Item --}}
                        @if($item['is_separator'])
                            <div class="sidebar__divider"></div>
                        @else
                            <a href="{{ $item['url'] ?? route($item['route']) }}" 
                               class="sidebar__menu-item {{ $isActive ? 'sidebar__menu-item--active' : '' }}"
                               target="{{ $item['target'] ?? '_self' }}"
                               style="text-decoration: none;">
                                <span class="sidebar__menu-icon" style="display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background-color: var(--sidebar-surface);">
                                    @if($item['icon'])
                                        @switch($item['icon'])
                                            @case('heroicon-o-home')
                                                <x-heroicon-o-home style="width:18px;height:18px;" />
                                                @break
                                            @case('heroicon-o-users')
                                                <x-heroicon-o-users style="width:18px;height:18px;" />
                                                @break
                                            @case('heroicon-o-shield-check')
                                                <x-heroicon-o-shield-check style="width:18px;height:18px;" />
                                                @break
                                            @case('heroicon-o-key')
                                                <x-heroicon-o-key style="width:18px;height:18px;" />
                                                @break
                                            @case('heroicon-o-cube')
                                                <x-heroicon-o-cube style="width:18px;height:18px;" />
                                                @break
                                            @case('heroicon-o-user-circle')
                                                <x-heroicon-o-user-circle style="width:18px;height:18px;" />
                                                @break
                                            @case('heroicon-o-cog-6-tooth')
                                                <x-heroicon-o-cog-6-tooth style="width:18px;height:18px;" />
                                                @break
                                            @case('heroicon-o-bell')
                                                <x-heroicon-o-bell style="width:18px;height:18px;" />
                                                @break
                                            @default
                                                <x-dynamic-component :component="$item['icon']" style="width:18px;height:18px;" />
                                        @endswitch
                                    @endif
                                </span>
                                <span style="flex: 1; color: var(--sidebar-text); font-size: 0.95rem; font-weight: 500;">
                                    {{ $item['label'] }}
                                </span>
                            </a>

                            {{-- Children Menu (if exists) --}}
                            @if(!empty($item['children']))
                                <div class="sidebar__submenu">
                                    @foreach($item['children'] as $child)
                                        @if(!$child['permission'] || auth()->user()->hasPermissionTo($child['permission']))
                                            <a href="{{ $child['url'] ?? route($child['route']) }}" 
                                               class="sidebar__submenu-item {{ request()->routeIs($child['route'] . '*') ? 'sidebar__submenu-item--active' : '' }}"
                                               target="{{ $child['target'] ?? '_self' }}"
                                               style="text-decoration: none;">
                                                {{ $child['label'] }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    @endif
                @endforeach
            </div>

            <div class="sidebar__divider"></div>

            {{-- Footer / Logout --}}
            <div class="sidebar__footer" style="display: flex; align-items: center; justify-content: center; border: 1px solid var(--sidebar-border); border-radius: 10px; padding: 10px;">
                <form method="POST" action="{{ route('logout') }}" style="width: 100%;">
                    @csrf
                    <button
                        type="submit"
                        style="background: none; border: none; color: var(--sidebar-text); width: 100%; cursor: pointer; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 8px;"
                    >
                        <x-heroicon-o-arrow-left-on-rectangle style="width:18px;height:18px;" />
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
