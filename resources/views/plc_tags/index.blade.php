@extends('layouts.app')

@section('title', 'Tag PLC - NTP_PLC')
@section('page_title', 'Tag PLC')
@section('page_subtitle', 'Quản lý biến dữ liệu PLC')

@section('content')
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <form class="d-flex gap-2" method="GET">
        <select class="form-select" name="plc_id">
            <option value="">Tất cả PLC</option>
            @foreach ($plcs as $plc)
                <option value="{{ $plc->id }}" @selected(request('plc_id') == $plc->id)>
                    {{ $plc->machine->code }} - {{ $plc->name }}
                </option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary">Lọc</button>
    </form>

    @can('plc-tags.create')
        <a href="{{ route('plc-tags.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Thêm tag
        </a>
    @endcan
</div>

<div class="card panel-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>PLC</th>
                <th>Address</th>
                <th>Tên nguồn</th>
                <th>Tên hiển thị</th>
                <th>Kiểu / Đơn vị</th>
                <th>Nhóm</th>
                <th>OEE role</th>
                <th>Hiển thị</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($plcTags as $plcTag)
                <tr>
                    <td>{{ $plcTag->plc->machine->code }} / {{ $plcTag->plc->name }}</td>
                    <td><code>{{ $plcTag->address ?: '—' }}</code></td>
                    <td>{{ $plcTag->source_name }}</td>
                    <td>{{ $plcTag->display_name ?: '—' }}</td>
                    <td>{{ $plcTag->data_type }} / {{ $plcTag->unit ?: '—' }}</td>
                    <td>{{ $plcTag->display_group ?: '—' }} #{{ $plcTag->display_order }}</td>
                    <td>{{ $plcTag->oee_role ?: '—' }}</td>
                    <td>
                        <span class="badge {{ $plcTag->is_visible ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $plcTag->is_visible ? 'Có' : 'Không' }}
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        @can('plc-tags.update')
                            <a href="{{ route('plc-tags.edit', $plcTag) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        @endcan
                        @can('plc-tags.delete')
                            <form class="d-inline" method="POST" action="{{ route('plc-tags.destroy', $plcTag) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa tag này?')">Xóa</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center py-5 text-secondary">Chưa có tag PLC.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($plcTags->hasPages())
        <div class="card-footer">{{ $plcTags->links() }}</div>
    @endif
</div>
@endsection
