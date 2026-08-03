# Bản đã đồng bộ Monitoring Runtime

## Đã sửa trực tiếp trong dự án

1. Xóa migration trùng:

```text
database/migrations/2026_07_30_200100_add_monitoring_layout_position_to_plc_tags.php
```

Migration đúng được giữ lại:

```text
database/migrations/2026_07_30_190100_add_monitoring_position_to_plc_tags.php
```

2. Chuẩn hóa `routes/web.php`:

- Chỉ còn một route `/monitoring/{machineCode}/technology`.
- Route runtime dùng `TechnologyMonitoringController`.
- Giữ `/monitoring/{machineCode}` cho màn hình monitoring cũ.
- Giữ route API cập nhật runtime `/monitoring/{machineCode}/technology/data`.

3. Giữ bản CSS runtime đã sửa cho bảng nhiều cột:

- Mỗi cột có chiều rộng cố định.
- Bảng cuộn ngang.
- Cột tên dòng cố định bên trái.
- Không còn ép hơn 30 cột vào một màn hình.

## Cài vào máy

Sao lưu dự án hiện tại, sau đó chép đè nội dung gói này vào:

```text
D:\xampp\htdocs\NTP_PLC
```

Gói không chứa `.env`, `collector/.env`, `.venv` và file log. Giữ nguyên các file này trên máy của bạn.

Chạy:

```powershell
cd D:\xampp\htdocs\NTP_PLC

php artisan optimize:clear
php artisan migrate
php artisan route:list --path=technology
```

Kết quả route phải có đúng hai dòng:

```text
GET|HEAD monitoring/{machineCode}/technology
GET|HEAD monitoring/{machineCode}/technology/data
```

Sau đó mở:

```text
http://127.0.0.1:8000/monitoring/M62/technology
```

Nhấn `Ctrl + F5` để bỏ cache CSS/JavaScript.

## Migration mong đợi

Sau khi xóa migration trùng, Laravel sẽ chạy tiếp:

```text
2026_07_30_210000_add_runtime_display_settings_to_plc_tags
```

Không chạy `migrate:fresh` vì thao tác đó xóa dữ liệu PLC.
