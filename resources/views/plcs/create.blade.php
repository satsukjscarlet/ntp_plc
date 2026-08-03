@extends('layouts.app')
@section('title', 'Thêm PLC - NTP_PLC')
@section('page_title', 'Thêm PLC')
@section('content')
<form method="POST" action="{{ route('plcs.store') }}">@csrf
<div class="card panel-card"><div class="card-body">@include('plcs._form')</div>
<div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('plcs.index') }}">Hủy</a><button class="btn btn-primary">Lưu</button></div></div>
</form>
@endsection