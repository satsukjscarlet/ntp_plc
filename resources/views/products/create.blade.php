@extends('layouts.app')

@section('title', 'Thêm sản phẩm - NTP_PLC')
@section('page_title', 'Thêm sản phẩm')
@section('page_subtitle', 'Khai báo mã vật tư và thông số mặc định')

@section('content')
<form method="POST" action="{{ route('products.store') }}">
    @csrf
    @include('products._form')
</form>
@endsection
