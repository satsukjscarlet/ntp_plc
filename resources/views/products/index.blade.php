@extends('layouts.app')

@section('title', 'Danh mục sản phẩm - NTP_PLC')
@section('page_title', 'Danh mục sản phẩm')
@section('page_subtitle', 'Quản lý mã vật tư, định mức khối lượng và chiều dài mặc định')

@section('content')
<div class="card panel-card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('products.index') }}" class="row g-2 align-items-end">
            <div class="col-lg-6">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="keyword" class="form-control"
                    value="{{ $filters['keyword'] ?? '' }}"
                    placeholder="Mã vật tư, tên vật tư hoặc đơn vị tính">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Hoạt động</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Ngừng hoạt động</option>
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit">
                    <i class="bi bi-search me-1"></i>Lọc
                </button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <div class="d-flex flex-wrap gap-2">
        @can('products.view')
            <a href="{{ route('products.export') }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Xuất Excel
            </a>
        @endcan

        @can('products.create')
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload me-1"></i>Nhập Excel
            </button>
            <a href="{{ asset('templates/products-import-template.xlsx') }}" class="btn btn-outline-secondary">
                <i class="bi bi-download me-1"></i>Tải file mẫu
            </a>
        @endcan
    </div>

    @can('products.create')
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Thêm sản phẩm
        </a>
    @endcan
</div>

<div class="card panel-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Mã vật tư</th>
                    <th>Tên vật tư</th>
                    <th>ĐVT</th>
                    <th class="text-end">Định mức g/ĐVT</th>
                    <th class="text-end">Chiều dài (m)</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="fw-semibold">{{ $product->material_code }}</td>
                        <td>
                            <div>{{ $product->material_name }}</div>
                            @if ($product->notes)
                                <small class="text-secondary">{{ \Illuminate\Support\Str::limit($product->notes, 80) }}</small>
                            @endif
                        </td>
                        <td>{{ $product->unit }}</td>
                        <td class="text-end">
                            {{ $product->standard_weight_g !== null ? number_format((float) $product->standard_weight_g, 4, ',', '.') : '—' }}
                        </td>
                        <td class="text-end">
                            {{ $product->default_length_m !== null ? number_format((float) $product->default_length_m, 4, ',', '.') : '—' }}
                        </td>
                        <td>
                            <span class="badge {{ $product->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $product->is_active ? 'Hoạt động' : 'Ngừng' }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            @can('products.update')
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Sửa
                                </a>
                            @endcan
                            @can('products.delete')
                                <form class="d-inline" method="POST" action="{{ route('products.destroy', $product) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit"
                                        onclick="return confirm('Xác nhận xóa sản phẩm {{ addslashes($product->material_code) }}?')">
                                        <i class="bi bi-trash"></i> Xóa
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-secondary">Chưa có sản phẩm.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
        <div class="card-footer">{{ $products->links() }}</div>
    @endif
</div>

@can('products.create')
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Nhập danh mục sản phẩm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Tệp Excel <span class="text-danger">*</span></label>
                <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls" required>
                <div class="form-text mt-2">
                    File Excel thông thường (.xlsx hoặc .xls), dòng đầu là tiêu đề. Mã vật tư đã có sẽ được cập nhật; mã đã xóa mềm sẽ được khôi phục.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-upload me-1"></i>Nhập dữ liệu
                </button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection
