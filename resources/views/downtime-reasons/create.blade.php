@extends('layouts.app')
@section('title','Thêm nguyên nhân dừng')
@section('page_title','Thêm nguyên nhân dừng')
@section('content')
<div class="card"><div class="card-body"><form method="POST" action="{{ route('downtime-reasons.store') }}">@csrf @include('downtime-reasons._form')</form></div></div>
@endsection
