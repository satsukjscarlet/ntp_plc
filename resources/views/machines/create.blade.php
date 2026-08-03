@extends('layouts.app')
@section('title', 'Thêm máy - NTP_PLC')
@section('page_title', 'Thêm máy')
@section('content')
<form method="POST" action="{{ route('machines.store') }}">@csrf
<div class="card panel-card"><div class="card-body">@include('machines._form')</div>
<div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('machines.index') }}">Hủy</a><button class="btn btn-primary">Lưu</button></div></div>
</form>
@endsection