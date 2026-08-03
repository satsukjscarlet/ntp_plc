@extends('layouts.app')

@section('title', 'Thêm vai trò - NTP_PLC')
@section('page_title', 'Thêm vai trò')
@section('page_subtitle', 'Tạo nhóm quyền mới')

@section('content')
<form method="POST" action="{{ route('roles.store') }}">
    @csrf

    <div class="card panel-card">
        <div class="card-body">
            @include('roles._form')
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('roles.index') }}" class="btn btn-light">Hủy</a>
            <button class="btn btn-primary">Lưu vai trò</button>
        </div>
    </div>
</form>
@endsection
