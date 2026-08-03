{{-- Thay các menu Thiết bị đang disabled trong layouts/app.blade.php bằng đoạn này --}}

<div class="nav-section">Thiết bị</div>

@can('plants.view')
<a class="nav-item {{ request()->routeIs('plants.*') ? 'active' : '' }}" href="{{ route('plants.index') }}">
    <i class="bi bi-building"></i><span>Nhà máy</span>
</a>
@endcan

@can('production-lines.view')
<a class="nav-item {{ request()->routeIs('production-lines.*') ? 'active' : '' }}" href="{{ route('production-lines.index') }}">
    <i class="bi bi-diagram-3"></i><span>Dây chuyền</span>
</a>
@endcan

@can('machines.view')
<a class="nav-item {{ request()->routeIs('machines.*') ? 'active' : '' }}" href="{{ route('machines.index') }}">
    <i class="bi bi-gear-wide-connected"></i><span>Máy</span>
</a>
@endcan

@can('plcs.view')
<a class="nav-item {{ request()->routeIs('plcs.*') ? 'active' : '' }}" href="{{ route('plcs.index') }}">
    <i class="bi bi-cpu-fill"></i><span>PLC</span>
</a>
@endcan

@can('plc-tags.view')
<a class="nav-item {{ request()->routeIs('plc-tags.*') ? 'active' : '' }}" href="{{ route('plc-tags.index') }}">
    <i class="bi bi-tags-fill"></i><span>Tag PLC</span>
</a>
@endcan
