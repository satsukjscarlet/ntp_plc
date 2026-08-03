@extends('layouts.app')
@section('title', 'Sửa tag PLC - NTP_PLC')
@section('page_title', 'Sửa tag PLC')
@section('content')
<form method="POST" action="{{ route('plc-tags.update', $plcTag) }}">@csrf @method('PUT')
<div class="card panel-card"><div class="card-body">@include('plc_tags._form')</div>
<div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('plc-tags.index') }}">Hủy</a><button class="btn btn-primary">Cập nhật</button></div></div>
</form>
@endsection