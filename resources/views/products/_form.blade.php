@php($editing = isset($product))

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Vui lòng kiểm tra lại dữ liệu:</div>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card panel-card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Mã vật tư <span class="text-danger">*</span></label>
                <input type="text" name="material_code" class="form-control text-uppercase"
                    maxlength="100" required autofocus
                    value="{{ old('material_code', $product->material_code ?? '') }}">
            </div>

            <div class="col-md-8">
                <label class="form-label">Tên vật tư <span class="text-danger">*</span></label>
                <input type="text" name="material_name" class="form-control"
                    maxlength="255" required
                    value="{{ old('material_name', $product->material_name ?? '') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Đơn vị tính <span class="text-danger">*</span></label>
                <input type="text" name="unit" class="form-control" maxlength="30" required
                    placeholder="Ví dụ: m, cây, cuộn, kg"
                    value="{{ old('unit', $product->unit ?? '') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Định mức (g/ĐVT)</label>
                <input type="number" name="standard_weight_g" class="form-control"
                    min="0" step="0.0001"
                    value="{{ old('standard_weight_g', $product->standard_weight_g ?? '') }}">
                <div class="form-text">Ví dụ g/m, g/cây hoặc g/cuộn tùy đơn vị tính.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Chiều dài mặc định (m)</label>
                <input type="number" name="default_length_m" class="form-control"
                    min="0" step="0.0001"
                    value="{{ old('default_length_m', $product->default_length_m ?? '') }}">
                <div class="form-text">Có thể thay đổi chiều dài thực tế khi kiểm nhập sản lượng.</div>
            </div>

            <div class="col-12">
                <label class="form-label">Ghi chú</label>
                <textarea name="notes" class="form-control" rows="3" maxlength="2000">{{ old('notes', $product->notes ?? '') }}</textarea>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" role="switch"
                        id="is_active" name="is_active" value="1"
                        @checked(old('is_active', $product->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Đang hoạt động</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between">
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Quay lại
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>{{ $editing ? 'Cập nhật' : 'Lưu sản phẩm' }}
        </button>
    </div>
</div>
