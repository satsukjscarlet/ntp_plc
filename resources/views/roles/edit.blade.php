@extends('layouts.app')

@section('title', 'Sửa vai trò - NTP_PLC')
@section('page_title', 'Sửa vai trò')
@section('page_subtitle', $role->name)

@section('content')
<form method="POST" action="{{ route('roles.update', $role) }}">
    @csrf
    @method('PUT')

    <div class="card panel-card">
        <div class="card-body">
            @include('roles._form')
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('roles.index') }}" class="btn btn-light">Hủy</a>
            @if ($role->name !== 'SUPER_ADMIN')
                <button class="btn btn-primary">Cập nhật</button>
            @endif
        </div>
    </div>
</form>
@endsection
