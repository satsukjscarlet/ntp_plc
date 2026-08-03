@extends('layouts.app')

@section('title', 'Giám sát ' . $machine->code)

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">
                Giám sát công nghệ {{ $machine->code }}
            </h4>
            <div class="text-muted">{{ $machine->name }}</div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span id="machine-status"
                  class="badge text-bg-secondary">
                Đang tải...
            </span>

            <a href="{{ url('/machines/' . $machine->code . '/tags') }}"
               class="btn btn-outline-primary">
                Quản lý tag
            </a>
        </div>
    </div>

    <div id="monitoring-error"></div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Máy</div>
                    <div class="fw-semibold" id="summary-machine">
                        {{ $machine->code }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Tổng tag hiển thị</div>
                    <div class="fw-semibold" id="summary-total-tags">—</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Dữ liệu gần nhất</div>
                    <div class="fw-semibold" id="summary-last-seen">—</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Cập nhật API</div>
                    <div class="fw-semibold" id="summary-generated-at">—</div>
                </div>
            </div>
        </div>
    </div>

    <div id="monitoring-groups">
        <div class="text-center py-5 text-muted">
            Đang tải dữ liệu...
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.monitoringConfig = {
    machineCode: @json($machine->code),
    apiUrl: @json(url('/api/monitoring/' . $machine->code)),
    refreshInterval: 5000,
};
</script>
<script src="{{ asset('js/monitoring.js') }}"></script>
@endpush
