@extends('layouts.app')

@section('title', $showTrash ? 'Thùng rác phiếu sản lượng' : 'Kiểm nhập sản lượng theo ca')
@section('page_title', $showTrash ? 'Thùng rác phiếu kiểm nhập' : 'Kiểm nhập sản lượng theo ca')
@section('page_subtitle', $showTrash
    ? 'Các phiếu đã xóa mềm và có thể được Admin khôi phục'
    : 'Dữ liệu công nhân nhập kết hợp với dữ liệu PLC để tính OEE')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted">
        @if($showTrash)
            Phiếu trong thùng rác không tham gia Dashboard, báo cáo hoặc OEE.
        @else
            Phiếu đã xóa được giữ trong thùng rác để tránh mất dữ liệu.
        @endif
    </div>

    <div class="d-flex gap-2">
        @can('production-declarations.restore')
            @if($showTrash)
                <a href="{{ route('production-declarations.index') }}"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Danh sách đang hoạt động
                </a>
            @else
                <a href="{{ route('production-declarations.index', ['trash' => 1]) }}"
                   class="btn btn-outline-danger">
                    <i class="bi bi-trash3 me-1"></i>Thùng rác
                </a>
            @endif
        @endcan

        @if(!$showTrash)
            @can('production-declarations.create')
                <a href="{{ route('production-declarations.create') }}"
                   class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Tạo phiếu cuối ca
                </a>
            @endcan
        @endif
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET">
            @if($showTrash)
                <input type="hidden" name="trash" value="1">
            @endif

            <div class="col-md-3">
                <select name="machine_id" class="form-select">
                    <option value="">Tất cả máy</option>
                    @foreach($machines as $machine)
                        <option value="{{ $machine->id }}"
                            @selected(request('machine_id') == $machine->id)>
                            {{ $machine->code }} - {{ $machine->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if(!$showTrash)
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="draft" @selected(request('status') === 'draft')>Nháp</option>
                        <option value="submitted" @selected(request('status') === 'submitted')>Chờ phê duyệt</option>
                        <option value="returned" @selected(request('status') === 'returned')>Đã trả lại</option>
                        <option value="approved" @selected(request('status') === 'approved')>Đã phê duyệt</option>
                        <option value="locked" @selected(request('status') === 'locked')>Đã khóa</option>
                    </select>
                </div>
            @endif

            <div class="col-md-2">
                <input type="date"
                       name="date_from"
                       class="form-control"
                       value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date"
                       name="date_to"
                       class="form-control"
                       value="{{ request('date_to') }}">
            </div>

            <div class="col-md-3">
                <button class="btn btn-outline-primary">Lọc</button>
                <a href="{{ route('production-declarations.index', $showTrash ? ['trash' => 1] : []) }}"
                   class="btn btn-outline-secondary">
                    Xóa lọc
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Máy/Ca</th>
                    <th>Sản phẩm</th>
                    <th class="text-end">Đạt</th>
                    <th class="text-end">Phế</th>
                    <th class="text-end">OEE</th>
                    <th>Trạng thái</th>
                    @if($showTrash)
                        <th>Thông tin xóa</th>
                    @endif
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($declarations as $item)
                <tr>
                    <td>{{ $item->production_date->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $item->machine->code }}</strong>
                        <div class="small text-muted">{{ $item->shift->name }}</div>
                    </td>
                    <td>
                        {{ $item->product_code ?: '—' }}
                        <div class="small text-muted">
                            {{ $item->product_name }}
                            @if($item->items_count > 1)
                                · {{ $item->items_count }} sản phẩm
                            @endif
                        </div>
                    </td>
                    <td class="text-end">
                        {{ number_format((float) $item->good_quantity, 3) }}
                    </td>
                    <td class="text-end">
                        {{ number_format((float) $item->reject_quantity, 3) }}
                    </td>
                    <td class="text-end fw-bold">
                        {{ $item->oee !== null
                            ? number_format((float) $item->oee * 100, 2) . '%'
                            : 'Chưa tính' }}
                    </td>
                    <td>
                        <span class="badge {{ $item->statusBadgeClass() }}">
                            {{ $item->statusLabel() }}
                        </span>
                    </td>

                    @if($showTrash)
                        <td style="min-width: 260px">
                            <div>
                                <strong>{{ $item->deleter?->name ?? 'Hệ thống' }}</strong>
                                · {{ $item->deleted_at?->format('d/m/Y H:i:s') }}
                            </div>
                            <div class="small text-danger">
                                {{ $item->delete_reason ?: 'Không có lý do' }}
                            </div>
                        </td>
                    @endif

                    <td class="text-end text-nowrap">
                        @if($showTrash)
                            @can('production-declarations.restore')
                                <form method="POST"
                                      action="{{ route('production-declarations.restore', $item->id) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Khôi phục phiếu #{{ $item->id }}?')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-success">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Khôi phục
                                    </button>
                                </form>
                            @endcan
                        @else
                            <a href="{{ route('production-declarations.show', $item) }}"
                               class="btn btn-sm btn-outline-primary">
                                Xem
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showTrash ? 9 : 8 }}"
                        class="text-center py-4 text-muted">
                        {{ $showTrash
                            ? 'Thùng rác đang trống.'
                            : 'Chưa có phiếu kiểm nhập.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($declarations->hasPages())
        <div class="card-footer">
            {{ $declarations->links() }}
        </div>
    @endif
</div>
@endsection
