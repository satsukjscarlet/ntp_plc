@extends('layouts.app')

@section('title', 'Sửa người dùng - NTP_PLC')
@section('page_title', 'Sửa người dùng')
@section('page_subtitle', $user->name)

@section('content')
<form method="POST" action="{{ route('users.update', $user) }}">
    @csrf
    @method('PUT')

    <div class="card panel-card">
        <div class="card-body">
            @include('users._form')
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-light">Hủy</a>
            <button class="btn btn-primary">Cập nhật</button>
        </div>
    </div>
</form>
@endsection
