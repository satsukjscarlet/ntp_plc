@extends('layouts.app')

@section('title', 'Nhật ký - NTP_PLC')
@section('page_title', 'Nhật ký hoạt động')
@section('page_subtitle', 'Theo dõi thay đổi trong hệ thống')

@section('content')
<div class="card panel-card">
    @if ($logs instanceof \Illuminate\Pagination\AbstractPaginator)
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Nhóm</th>
                    <th>Mô tả</th>
                    <th>Người thực hiện</th>
                    <th>Đối tượng</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at }}</td>
                        <td>{{ $log->log_name ?: '—' }}</td>
                        <td>{{ $log->description }}</td>
                        <td>{{ $log->causer_type ? class_basename($log->causer_type).' #'.$log->causer_id : 'Hệ thống' }}</td>
                        <td>{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    @else
        <div class="card-body py-5 text-center">
            <i class="bi bi-clock-history display-5 text-secondary"></i>
            <h5 class="mt-3">Chưa cài module nhật ký</h5>
            <p class="text-secondary mb-0">
                Bảng activity_log chưa tồn tại. Module này sẽ được hoàn thiện sau.
            </p>
        </div>
    @endif
</div>
@endsection
