@extends('layouts.app')
@section('title', 'Thêm dây chuyền - NTP_PLC')
@section('page_title', 'Thêm dây chuyền')
@section('content')
<form method="POST" action="{{ route('production-lines.store') }}">@csrf
<div class="card panel-card"><div class="card-body">@include('production_lines._form')</div>
<div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('production-lines.index') }}">Hủy</a><button class="btn btn-primary">Lưu</button></div></div>
</form>
@endsection