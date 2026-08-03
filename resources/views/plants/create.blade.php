@extends('layouts.app')
@section('title', 'Thêm nhà máy - NTP_PLC')
@section('page_title', 'Thêm nhà máy')
@section('content')
<form method="POST" action="{{ route('plants.store') }}">@csrf
<div class="card panel-card"><div class="card-body">@include('plants._form')</div>
<div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('plants.index') }}">Hủy</a><button class="btn btn-primary">Lưu</button></div></div>
</form>
@endsection