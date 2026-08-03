@extends('layouts.app')
@section('title', 'Sửa dây chuyền - NTP_PLC')
@section('page_title', 'Sửa dây chuyền')
@section('content')
<form method="POST" action="{{ route('production-lines.update', $productionLine) }}">@csrf @method('PUT')
<div class="card panel-card"><div class="card-body">@include('production_lines._form')</div>
<div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('production-lines.index') }}">Hủy</a><button class="btn btn-primary">Cập nhật</button></div></div>
</form>
@endsection