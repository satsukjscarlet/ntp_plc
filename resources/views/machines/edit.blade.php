@extends('layouts.app')
@section('title', 'Sửa máy - NTP_PLC')
@section('page_title', 'Sửa máy')
@section('content')
<form method="POST" action="{{ route('machines.update', $machine) }}">@csrf @method('PUT')
<div class="card panel-card"><div class="card-body">@include('machines._form')</div>
<div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('machines.index') }}">Hủy</a><button class="btn btn-primary">Cập nhật</button></div></div>
</form>
@endsection