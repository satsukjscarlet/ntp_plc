{{-- Chèn vào trang sửa PLC nếu muốn tạo token từ giao diện --}}

<div class="card panel-card mt-3">
    <div class="card-body">
        <h5 class="card-title">Collector API Token</h5>

        <p class="text-secondary">
            Token chỉ hiển thị một lần sau khi tạo mới.
        </p>

        <form
            method="POST"
            action="{{ route('plcs.collector-token', $plc) }}"
        >
            @csrf
            <button
                class="btn btn-outline-warning"
                onclick="return confirm('Token cũ sẽ mất hiệu lực. Tiếp tục?')"
            >
                Tạo lại token
            </button>
        </form>

        @if (session('collector_token'))
            <div class="alert alert-warning mt-3 mb-0">
                <div class="fw-semibold">
                    Sao chép token này ngay:
                </div>

                <code class="d-block text-break mt-2">
                    {{ session('collector_token') }}
                </code>
            </div>
        @endif
    </div>
</div>
