@extends('layouts.app')

@section('title', 'Sửa sản phẩm - NTP_PLC')
@section('page_title', 'Sửa sản phẩm')
@section('page_subtitle', $product->material_code . ' - ' . $product->material_name)

@section('content')
<form method="POST" action="{{ route('products.update', $product) }}">
    @csrf
    @method('PUT')
    @include('products._form')
</form>
@endsection
