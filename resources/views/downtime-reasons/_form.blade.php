@php($editing = isset($reason))
<div class="row g-3">
    <div class="col-md-3"><label class="form-label">Mã nguyên nhân <span class="text-danger">*</span></label><input name="code" class="form-control" value="{{ old('code', $reason->code ?? '') }}" required maxlength="50"></div>
    <div class="col-md-5"><label class="form-label">Tên nguyên nhân <span class="text-danger">*</span></label><input name="name" class="form-control" value="{{ old('name', $reason->name ?? '') }}" required maxlength="150"></div>
    <div class="col-md-4"><label class="form-label">Nhóm <span class="text-danger">*</span></label><select name="category" class="form-select" required>@foreach($categories as $key=>$label)<option value="{{ $key }}" @selected(old('category',$reason->category ?? 'OTHER')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Thứ tự</label><input name="display_order" type="number" min="0" class="form-control" value="{{ old('display_order',$reason->display_order ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">Màu hiển thị</label><input name="display_color" class="form-control" placeholder="#dc3545" value="{{ old('display_color',$reason->display_color ?? '') }}"></div>
    <div class="col-md-6 d-flex flex-wrap gap-4 align-items-end pb-2">
        <input type="hidden" name="is_planned" value="0"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_planned" value="1" @checked(old('is_planned',$reason->is_planned ?? false))><label class="form-check-label">Dừng kế hoạch</label></div>
        <input type="hidden" name="affects_availability" value="0"><div class="form-check"><input class="form-check-input" type="checkbox" name="affects_availability" value="1" @checked(old('affects_availability',$reason->affects_availability ?? true))><label class="form-check-label">Tính vào tổn thất Availability</label></div>
        <input type="hidden" name="requires_note" value="0"><div class="form-check"><input class="form-check-input" type="checkbox" name="requires_note" value="1" @checked(old('requires_note',$reason->requires_note ?? false))><label class="form-check-label">Bắt buộc ghi chú</label></div>
        <input type="hidden" name="is_active" value="0"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active',$reason->is_active ?? true))><label class="form-check-label">Hoạt động</label></div>
    </div>
    <div class="col-12"><label class="form-label">Mô tả</label><textarea name="description" class="form-control" rows="3">{{ old('description',$reason->description ?? '') }}</textarea></div>
</div>
<div class="mt-4 d-flex gap-2"><button class="btn btn-primary">{{ $editing ? 'Cập nhật' : 'Lưu nguyên nhân' }}</button><a href="{{ route('downtime-reasons.index') }}" class="btn btn-outline-secondary">Quay lại</a></div>
