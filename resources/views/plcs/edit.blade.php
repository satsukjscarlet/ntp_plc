@extends('layouts.app')
@section('title', 'Sửa PLC - NTP_PLC')
@section('page_title', 'Sửa PLC')
@section('content')
<form method="POST" action="{{ route('plcs.update', $plc) }}">@csrf @method('PUT')
<div class="card panel-card"><div class="card-body">@include('plcs._form')</div>
<div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('plcs.index') }}">Hủy</a><button class="btn btn-primary">Cập nhật</button></div></div>
</form>
@endsection