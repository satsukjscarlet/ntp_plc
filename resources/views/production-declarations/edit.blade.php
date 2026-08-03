@extends('layouts.app')

@section('title', 'Sửa phiếu kiểm nhập')
@section('page_title', 'Sửa phiếu kiểm nhập sản lượng')
@section('page_subtitle', 'Cập nhật dữ liệu phiếu trên điện thoại hoặc máy tính')

@section('content')
    @if($declaration->return_reason)
        <div class="alert alert-warning">
            <strong>Lý do trả lại:</strong> {{ $declaration->return_reason }}
        </div>
    @endif

    @include('production-declarations._mobile_form')
@endsection
