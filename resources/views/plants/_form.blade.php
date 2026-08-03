<div class="row g-3">
<div class="col-md-3"><label class="form-label">Mã *</label><input class="form-control" name="code" required value="{{ old('code', $plant->code ?? '') }}"></div>
<div class="col-md-9"><label class="form-label">Tên nhà máy *</label><input class="form-control" name="name" required value="{{ old('name', $plant->name ?? '') }}"></div>
<div class="col-12"><label class="form-label">Địa chỉ</label><input class="form-control" name="address" value="{{ old('address', $plant->address ?? '') }}"></div>
<div class="col-12"><label class="form-label">Mô tả</label><textarea class="form-control" name="description" rows="3">{{ old('description', $plant->description ?? '') }}</textarea></div>
<div class="col-md-3"><label class="form-label">Trạng thái</label><select class="form-select" name="is_active"><option value="1" @selected(old('is_active', $plant->is_active ?? true)==1)>Hoạt động</option><option value="0" @selected(old('is_active', $plant->is_active ?? true)==0)>Ngừng</option></select></div>
</div>