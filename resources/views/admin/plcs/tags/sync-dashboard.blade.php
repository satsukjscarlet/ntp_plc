@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Đồng bộ tag - {{ $plc->name }}</h1>
            <div class="text-muted">
                {{ $plc->collector_url }}
            </div>
        </div>

        <form method="POST"
              action="{{ route('admin.plcs.tags.sync', $plc) }}">
            @csrf
            <button class="btn btn-primary"
                    onclick="return confirm('Đồng bộ toàn bộ tag từ PLC Portal?')">
                Đồng bộ tag
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-3">
        @foreach([
            'Tổng tag' => $stats['total'],
            'Đang hoạt động' => $stats['active'],
            'Tag mới' => $stats['new'],
            'Đã đổi tên' => $stats['renamed'],
            'Ngừng sử dụng' => $stats['removed'],
        ] as $label => $value)
            <div class="col-6 col-md">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-3 fw-semibold">{{ number_format($value) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Address</th>
                        <th>Tên Portal</th>
                        <th>Tên hiển thị</th>
                        <th>Kiểu dữ liệu</th>
                        <th>Trạng thái</th>
                        <th>Hoạt động</th>
                        <th>Lần thấy cuối</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tags as $tag)
                        <tr>
                            <td>{{ $tag->id }}</td>
                            <td><code>{{ $tag->address ?: 'NAME-ONLY' }}</code></td>
                            <td>{{ $tag->portal_name ?: $tag->source_name }}</td>
                            <td>{{ $tag->display_name }}</td>
                            <td>{{ $tag->data_type }}</td>
                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ $tag->sync_status }}
                                </span>
                            </td>
                            <td>{{ $tag->is_active ? 'Có' : 'Không' }}</td>
                            <td>{{ optional($tag->last_seen_at)->format('d/m/Y H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                Chưa có tag.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $tags->links() }}
        </div>
    </div>
</div>
@endsection
