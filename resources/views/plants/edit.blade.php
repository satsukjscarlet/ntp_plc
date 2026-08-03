@extends('layouts.app')
@section('title', 'Sửa nhà máy - NTP_PLC')
@section('page_title', 'Sửa nhà máy')
@section('content')
<form method="POST" action="{{ route('plants.update', $plant) }}">@csrf @method('PUT')
<div class="card panel-card"><div class="card-body">@include('plants._form')</div>
<div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('plants.index') }}">Hủy</a><button class="btn btn-primary">Cập nhật</button></div></div>
</form>
@endsection