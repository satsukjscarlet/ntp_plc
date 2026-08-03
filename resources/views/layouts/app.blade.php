<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'NTP_PLC')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">

    @stack('styles')
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-mark">NTP</div>
                <div>
                    <div class="brand-title">NTP_PLC</div>
                    <div class="brand-subtitle">MES & OEE</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   href="{{ route('dashboard') }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>

                <div class="nav-section">Giám sát PLC</div>

                @forelse($sidebarMachines as $sidebarMachine)
                    <div class="small text-uppercase text-muted px-3 pt-2 pb-1 fw-semibold">
                        {{ $sidebarMachine->code }} - {{ $sidebarMachine->name }}
                    </div>

                    <a class="nav-item {{ request()->routeIs('monitoring.technology') && request()->route('machineCode') === $sidebarMachine->code ? 'active' : '' }}"
                       href="{{ route('monitoring.technology', $sidebarMachine->code) }}">
                        <i class="bi bi-display"></i>
                        <span>Thông số công nghệ</span>
                    </a>

                    @can('plc-tags.view')
                        <a class="nav-item {{ request()->routeIs('monitoring-layout.*') && request()->route('machineCode') === $sidebarMachine->code ? 'active' : '' }}"
                           href="{{ route('monitoring-layout.index', $sidebarMachine->code) }}">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                            <span>Bố trí màn hình</span>
                        </a>

                        <a class="nav-item {{ request()->routeIs('machines.tags.*') && request()->route('machineCode') === $sidebarMachine->code ? 'active' : '' }}"
                           href="{{ route('machines.tags.index', $sidebarMachine->code) }}">
                            <i class="bi bi-tags-fill"></i>
                            <span>Quản lý tag</span>
                        </a>
                    @endcan
                @empty
                    <span class="nav-item disabled">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>Chưa có máy hoạt động</span>
                    </span>
                @endforelse

                @can('plc-tags.configure-oee')
                    <a class="nav-item {{ request()->routeIs('oee-settings.*') || request()->routeIs('machines.oee-settings.*') ? 'active' : '' }}"
                       href="{{ route('oee-settings.index') }}">
                        <i class="bi bi-speedometer"></i>
                        <span>Cấu hình OEE</span>
                    </a>
                @endcan

                <div class="nav-section">Thiết bị</div>

                @can('plants.view')
                    <a class="nav-item {{ request()->routeIs('plants.*') ? 'active' : '' }}"
                       href="{{ route('plants.index') }}">
                        <i class="bi bi-building"></i>
                        <span>Nhà máy</span>
                    </a>
                @endcan

                @can('production-lines.view')
                    <a class="nav-item {{ request()->routeIs('production-lines.*') ? 'active' : '' }}"
                       href="{{ route('production-lines.index') }}">
                        <i class="bi bi-diagram-3"></i>
                        <span>Dây chuyền</span>
                    </a>
                @endcan

                @can('machines.view')
                    <a class="nav-item {{ request()->routeIs('machines.index') || request()->routeIs('machines.create') || request()->routeIs('machines.edit') ? 'active' : '' }}"
                       href="{{ route('machines.index') }}">
                        <i class="bi bi-gear-wide-connected"></i>
                        <span>Máy</span>
                    </a>
                @endcan

                @can('plcs.view')
                    <a class="nav-item {{ request()->routeIs('plcs.*') ? 'active' : '' }}"
                       href="{{ route('plcs.index') }}">
                        <i class="bi bi-cpu-fill"></i>
                        <span>PLC</span>
                    </a>
                @endcan

                @can('plc-tags.view')
                    <a class="nav-item {{ request()->routeIs('plc-tags.*') ? 'active' : '' }}"
                       href="{{ route('plc-tags.index') }}">
                        <i class="bi bi-tags-fill"></i>
                        <span>Tag PLC</span>
                    </a>
                @endcan

                <div class="nav-section">Sản xuất</div>

                @can('products.view')
                    <a class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}"
                       href="{{ route('products.index') }}">
                        <i class="bi bi-box-seam-fill"></i>
                        <span>Danh mục sản phẩm</span>
                    </a>
                @endcan

                @can('machine-product-standards.view')
                    <a class="nav-item {{ request()->routeIs('machine-product-standards.*') ? 'active' : '' }}"
                       href="{{ route('machine-product-standards.index') }}">
                        <i class="bi bi-sliders"></i>
                        <span>Định mức máy - sản phẩm</span>
                    </a>
                @endcan

                @can('production-shifts.view')
                    <a class="nav-item {{ request()->routeIs('production-shifts.*') ? 'active' : '' }}"
                       href="{{ route('production-shifts.index') }}">
                        <i class="bi bi-clock"></i>
                        <span>Danh mục ca làm việc</span>
                    </a>
                @endcan

                @can('downtime-reasons.view')
                    <a class="nav-item {{ request()->routeIs('downtime-reasons.*') ? 'active' : '' }}"
                       href="{{ route('downtime-reasons.index') }}">
                        <i class="bi bi-stopwatch-fill"></i>
                        <span>Nguyên nhân dừng</span>
                    </a>
                @endcan

                @can('production-declarations.view')
                    <a class="nav-item {{ request()->routeIs('production-declarations.*') ? 'active' : '' }}"
                       href="{{ route('production-declarations.index') }}">
                        <i class="bi bi-clipboard2-check-fill"></i>
                        <span>Kiểm nhập sản lượng</span>
                    </a>
                @endcan

                <div class="nav-section">OEE và báo cáo</div>

                @can('production-declarations.view')
                    <a class="nav-item {{ request()->routeIs('oee-reports.*') ? 'active' : '' }}"
                       href="{{ route('oee-reports.index') }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Báo cáo OEE</span>
                    </a>

                    <a class="nav-item {{ request()->routeIs('production-reports.*') ? 'active' : '' }}"
                       href="{{ route('production-reports.daily-detail') }}">
                        <i class="bi bi-table"></i>
                        <span>Tình hình sản xuất ngày</span>
                    </a>
                @endcan

                @canany(['users.view', 'roles.view', 'activity-logs.view'])
                    <div class="nav-section">Hệ thống</div>

                    @can('users.view')
                        <a class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"
                           href="{{ route('users.index') }}">
                            <i class="bi bi-people-fill"></i>
                            <span>Người dùng</span>
                        </a>
                    @endcan

                    @can('roles.view')
                        <a class="nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}"
                           href="{{ route('roles.index') }}">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span>Vai trò và quyền</span>
                        </a>
                    @endcan

                    @can('activity-logs.view')
                        <a class="nav-item {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"
                           href="{{ route('activity-logs.index') }}">
                            <i class="bi bi-clock-history"></i>
                            <span>Nhật ký</span>
                        </a>
                    @endcan
                @endcanany
            </nav>

            <div class="sidebar-footer">
                <div>NTP_PLC v1.0</div>
                <small>Nhựa Tiền Phong</small>
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <button class="btn btn-light d-lg-none" type="button" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>

                <div>
                    <h1 class="page-title">@yield('page_title', 'Dashboard')</h1>
                    <div class="page-subtitle">@yield('page_subtitle')</div>
                </div>

                <div class="dropdown ms-auto">
                    <button class="btn user-menu dropdown-toggle" data-bs-toggle="dropdown" type="button">
                        <span class="user-avatar">
                            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="d-none d-md-inline">
                            {{ auth()->user()->name }}
                        </span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person me-2"></i>Hồ sơ
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit">
                                    <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </header>

            <section class="content-area">
                @include('partials.flash')
                @yield('content')
            </section>

            <footer class="app-footer">
                <span>NTP_PLC</span>
                <span>Nhựa Tiền Phong</span>
            </footer>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('show');
        });
    </script>
    @stack('scripts')
</body>

</html>
