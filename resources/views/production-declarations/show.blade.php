@extends('layouts.app')

@section('title', 'Chi tiết kiểm nhập')
@section('page_title', 'Chi tiết phiếu kiểm nhập sản lượng')
@section('page_subtitle', 'Dữ liệu sản phẩm và định mức được lưu snapshot theo thời điểm khai báo')



@push('styles')
<style>
    .production-action-toolbar {
        position: sticky;
        top: 68px;
        z-index: 1010;
        padding: .65rem;
        border: 1px solid #dee2e6;
        border-radius: .75rem;
        background: rgba(255, 255, 255, .97);
        box-shadow: 0 .25rem .75rem rgba(15, 23, 42, .08);
        backdrop-filter: blur(6px);
    }

    .production-action-toolbar .action-divider {
        width: 1px;
        height: 32px;
        background: #d7dde4;
        margin: 0 .15rem;
    }

    @media (max-width: 767.98px) {
        .production-action-toolbar {
            top: 58px;
            align-items: stretch !important;
        }

        .production-action-toolbar > a,
        .production-action-toolbar > div {
            width: 100%;
        }

        .production-action-toolbar > div {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .production-action-toolbar form,
        .production-action-toolbar form .btn,
        .production-action-toolbar > div > .btn,
        .production-action-toolbar > div > a {
            width: 100%;
        }

        .production-action-toolbar .badge {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
        }
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <span class="badge {{ $declaration->statusBadgeClass() }} fs-6">
            {{ $declaration->statusLabel() }}
        </span>
        @if($declaration->return_reason)
            <div class="small text-danger mt-1">
                <strong>Lý do trả lại:</strong> {{ $declaration->return_reason }}
            </div>
        @endif
    </div>

    <div class="d-flex flex-wrap gap-2">
        @can('production-declarations.update')
            @if($declaration->isEditable())
                <a href="{{ route('production-declarations.edit', $declaration) }}"
                   class="btn btn-outline-primary">
                    <i class="bi bi-pencil-square me-1"></i>Sửa phiếu
                </a>
                <form method="POST"
                      action="{{ route('production-declarations.submit', $declaration) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-primary">
                        <i class="bi bi-send-check me-1"></i>Gửi phê duyệt
                    </button>
                </form>
            @endif
        @endcan

        @can('production-declarations.approve')
            @if($declaration->status === \App\Models\ShiftProductionDeclaration::STATUS_SUBMITTED)
                <form method="POST"
                      action="{{ route('production-declarations.approve', $declaration) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-success"
                            onclick="return confirm('Phê duyệt phiếu này?')">
                        <i class="bi bi-check-circle me-1"></i>Phê duyệt
                    </button>
                </form>
                <button class="btn btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#returnModal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Trả lại
                </button>
            @elseif($declaration->status === \App\Models\ShiftProductionDeclaration::STATUS_APPROVED)
                <button class="btn btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#returnModal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Trả lại
                </button>
            @endif
        @endcan

        @can('production-declarations.lock')
            @if($declaration->status === \App\Models\ShiftProductionDeclaration::STATUS_APPROVED)
                <form method="POST"
                      action="{{ route('production-declarations.lock', $declaration) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-dark"
                            onclick="return confirm('Khóa phiếu này? Sau khi khóa sẽ không thể sửa.')">
                        <i class="bi bi-lock-fill me-1"></i>Khóa phiếu
                    </button>
                </form>
            @endif
        @endcan

        @can('production-declarations.unlock')
            @if($declaration->status === \App\Models\ShiftProductionDeclaration::STATUS_LOCKED)
                <button class="btn btn-warning"
                        data-bs-toggle="modal"
                        data-bs-target="#unlockModal">
                    <i class="bi bi-unlock-fill me-1"></i>Mở khóa
                </button>
            @endif
        @endcan
    </div>
</div>

@php
    $statusLabels = ['draft' => 'Nháp', 'submitted' => 'Đã gửi', 'approved' => 'Đã phê duyệt'];
    $statusClasses = ['draft' => 'secondary', 'submitted' => 'warning', 'approved' => 'success'];
@endphp

<div class="production-action-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('production-declarations.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Danh sách
    </a>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <form method="POST" action="{{ route('production-declarations.recalculate-oee', $declaration) }}">
            @csrf
            <button class="btn btn-outline-primary"><i class="bi bi-calculator me-1"></i>Tính lại OEE</button>
        </form>
        <a href="{{ route('oee-reports.index') }}" class="btn btn-outline-dark"><i class="bi bi-bar-chart me-1"></i>Báo cáo OEE</a>
        <span class="badge text-bg-{{ $statusClasses[$declaration->status] ?? 'secondary' }} fs-6">
            {{ $statusLabels[$declaration->status] ?? $declaration->status }}
        </span>
        @can('production-declarations.approve')
            @if($declaration->status === 'submitted')
                <form method="POST" action="{{ route('production-declarations.approve', $declaration) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success" onclick="return confirm('Xác nhận phê duyệt và khóa phiếu này?')">
                        <i class="bi bi-check2-circle me-1"></i>Phê duyệt
                    </button>
                </form>
            @endif
        @endcan

        @can('production-declarations.delete')
            <span class="action-divider d-none d-md-inline-block"></span>
            <button type="button"
                    class="btn btn-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteDeclarationModal">
                <i class="bi bi-trash3 me-1"></i>Xóa phiếu
            </button>
        @endcan
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Thông tin ca</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><div class="text-muted small">Máy</div><div class="fw-semibold">{{ $declaration->machine->code }} - {{ $declaration->machine->name }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">Ngày sản xuất</div><div class="fw-semibold">{{ $declaration->production_date->format('d/m/Y') }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">Ca</div><div class="fw-semibold">{{ $declaration->shift->name }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">Thời gian ca</div><div>{{ $declaration->shift_started_at->format('d/m H:i') }} – {{ $declaration->shift_ended_at->format('d/m H:i') }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">Người khai báo</div><div>{{ $declaration->operator->name }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">Thời điểm gửi</div><div>{{ $declaration->submitted_at?->format('d/m/Y H:i') ?? 'Chưa gửi' }}</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Tổng hợp</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Sản lượng tổng:</span><strong>{{ number_format((float)$declaration->total_quantity, 3) }} ĐVT</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Chính phẩm:</span><strong>{{ number_format((float)$declaration->good_quantity, 3) }} ĐVT</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Phế phẩm:</span><strong>{{ number_format((float)$declaration->reject_quantity, 3) }} ĐVT</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Phế liệu:</span><strong>{{ number_format((float)$declaration->items->sum('scrap_quantity'), 3) }} kg</strong></div>
                <div class="d-flex justify-content-between border-top pt-2"><span>Quy đổi tổng:</span><strong>{{ number_format((float)$declaration->items->sum('converted_total_kg'), 3) }} kg</strong></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header fw-semibold">Chi tiết sản phẩm</div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-light text-center small">
                <tr>
                    <th>Sản phẩm</th><th>ĐVT</th><th>Dài thực tế</th><th>Định mức g/m</th><th>Năng suất chuẩn</th>
                    <th>Tổng SL</th><th>Chính phẩm</th><th>Phế phẩm</th><th>Phế liệu (kg)</th><th>Quy đổi tổng</th><th>Lệnh SX</th>
                </tr>
            </thead>
            <tbody>
            @foreach($declaration->items as $item)
                <tr>
                    <td><strong>{{ $item->product_code_snapshot }}</strong><br><span class="text-muted">{{ $item->product_name_snapshot }}</span></td>
                    <td>{{ $item->unit_snapshot }}</td>
                    <td class="text-end">{{ number_format((float)$item->actual_length_m, 4) }} m</td>
                    <td class="text-end">{{ number_format((float)$item->standard_weight_g_snapshot, 4) }}</td>
                    <td class="text-end">{{ number_format((float)$item->standard_rate_snapshot, 4) }} {{ $item->rate_unit_snapshot }}</td>
                    <td class="text-end">{{ number_format((float)$item->total_quantity, 3) }}</td>
                    <td class="text-end">{{ number_format((float)$item->good_quantity, 3) }}</td>
                    <td class="text-end">{{ number_format((float)$item->reject_quantity, 3) }}</td>
                    <td class="text-end">{{ number_format((float)$item->scrap_quantity, 3) }}</td>
                    <td class="text-end fw-semibold">{{ number_format((float)$item->converted_total_kg, 3) }} kg<br><small class="text-muted">Chính phẩm quy đổi: {{ number_format((float)$item->converted_good_kg, 3) }} kg | Phế phẩm quy đổi: {{ number_format((float)$item->converted_reject_kg, 3) }} kg</small></td>
                    <td>{{ $item->work_order ?: '—' }}</td>
                </tr>
                @if($item->note)
                    <tr><td colspan="11" class="small text-muted"><strong>Ghi chú:</strong> {{ $item->note }}</td></tr>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Dừng máy trong ca</div>
            <div class="card-body">
                @forelse($declaration->downtimeEntries as $entry)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div><strong>{{ $entry->reason->code }} - {{ $entry->reason->name }}</strong><br><small class="text-muted">{{ $entry->note }}</small></div>
                        <span class="badge text-bg-secondary align-self-start">{{ number_format((float)$entry->minutes, 2) }} phút</span>
                    </div>
                @empty
                    <div class="text-muted">Không khai báo nguyên nhân dừng.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Dữ liệu thời gian</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Thời gian kế hoạch:</span><strong>{{ number_format((float)$declaration->planned_production_minutes, 2) }} phút</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Runtime PLC:</span><strong>{{ $declaration->plc_runtime_minutes !== null ? number_format((float)$declaration->plc_runtime_minutes, 2).' phút' : 'Chưa có' }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Availability (A):</span><strong>{{ $declaration->availability !== null ? number_format((float)$declaration->availability * 100, 2).'%' : 'Chưa tính' }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Performance (P):</span><strong>{{ $declaration->performance !== null ? number_format((float)$declaration->performance * 100, 2).'%' : 'Chưa tính' }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Quality (Q):</span><strong>{{ $declaration->quality_rate !== null ? number_format((float)$declaration->quality_rate * 100, 2).'%' : 'Chưa tính' }}</strong></div>
                <div class="d-flex justify-content-between border-top pt-2 fs-5"><span>OEE:</span><strong>{{ $declaration->oee !== null ? number_format((float)$declaration->oee * 100, 2).'%' : 'Chưa tính' }}</strong></div>
                @if($declaration->calculation_note)
                    <div class="alert {{ $declaration->oee === null ? 'alert-warning' : 'alert-info' }} small mt-3 mb-0">
                        <div class="fw-semibold mb-1">Chi tiết dữ liệu và nguyên nhân tính OEE</div>
                        {{ $declaration->calculation_note }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-header fw-semibold">
        <i class="bi bi-clock-history me-1"></i>Lịch sử xử lý phiếu
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Thời gian</th>
                    <th>Người thực hiện</th>
                    <th>Thao tác</th>
                    <th>Trạng thái</th>
                    <th>Lý do</th>
                </tr>
            </thead>
            <tbody>
            @forelse($declaration->audits as $audit)
                <tr>
                    <td>{{ $audit->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $audit->user?->name ?? 'Hệ thống' }}</td>
                    <td>{{ $audit->action }}</td>
                    <td>
                        {{ $audit->from_status ?: '—' }}
                        <i class="bi bi-arrow-right mx-1"></i>
                        {{ $audit->to_status ?: '—' }}
                    </td>
                    <td>{{ $audit->reason ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        Chưa có lịch sử xử lý.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST"
              action="{{ route('production-declarations.return', $declaration) }}"
              class="modal-content">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title">Trả lại phiếu để chỉnh sửa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Lý do trả lại <span class="text-danger">*</span></label>
                <textarea name="reason" rows="4" class="form-control" required
                          minlength="5" maxlength="2000"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Hủy</button>
                <button class="btn btn-danger">Xác nhận trả lại</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="unlockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST"
              action="{{ route('production-declarations.unlock', $declaration) }}"
              class="modal-content">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title">Mở khóa phiếu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    Mở khóa là thao tác quản trị và sẽ được ghi nhật ký.
                </div>
                <label class="form-label">Lý do mở khóa <span class="text-danger">*</span></label>
                <textarea name="reason" rows="4" class="form-control" required
                          minlength="5" maxlength="2000"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Hủy</button>
                <button class="btn btn-warning">Xác nhận mở khóa</button>
            </div>
        </form>
    </div>
</div>


@can('production-declarations.delete')
<div class="modal fade"
     id="deleteDeclarationModal"
     tabindex="-1"
     aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST"
              action="{{ route('production-declarations.destroy', $declaration) }}"
              class="modal-content">
            @csrf
            @method('DELETE')

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-trash3 me-1"></i>Xóa phiếu kiểm nhập
                </h5>
                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-warning">
                    Phiếu <strong>#{{ $declaration->id }}</strong> sẽ được
                    chuyển vào thùng rác và không còn tham gia báo cáo,
                    Dashboard hoặc tính OEE. Admin có thể khôi phục sau.
                </div>

                @if($declaration->status === \App\Models\ShiftProductionDeclaration::STATUS_LOCKED)
                    <div class="alert alert-danger">
                        Đây là phiếu đã khóa. Thao tác xóa sẽ được ghi đầy đủ
                        người thực hiện, thời gian, lý do và snapshot dữ liệu.
                    </div>
                @endif

                <label class="form-label">
                    Lý do xóa <span class="text-danger">*</span>
                </label>
                <textarea name="reason"
                          rows="4"
                          class="form-control"
                          minlength="5"
                          maxlength="2000"
                          required
                          placeholder="Ví dụ: Tạo nhầm ngày/ca, nhập trùng phiếu..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">
                    Hủy
                </button>
                <button class="btn btn-danger">
                    Xác nhận chuyển vào thùng rác
                </button>
            </div>
        </form>
    </div>
</div>
@endcan

@endsection
