@extends('layouts.app')

@section('title', 'Thêm người dùng - NTP_PLC')
@section('page_title', 'Thêm người dùng')
@section('page_subtitle', 'Tạo tài khoản mới')

@section('content')
<form method="POST" action="{{ route('users.store') }}">
    @csrf

    <div class="card panel-card">
        <div class="card-body">
            @include('users._form')
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-light">Hủy</a>
            <button class="btn btn-primary">Lưu người dùng</button>
        </div>
    </div>
</form>
@endsection
