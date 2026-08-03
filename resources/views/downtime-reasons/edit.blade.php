@extends('layouts.app')
@section('title','Sửa nguyên nhân dừng')
@section('page_title','Sửa nguyên nhân dừng')
@section('content')
<div class="card"><div class="card-body"><form method="POST" action="{{ route('downtime-reasons.update',$reason) }}">@csrf @method('PUT') @include('downtime-reasons._form')</form></div></div>
@endsection
