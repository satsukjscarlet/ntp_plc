# NTP_PLC Python Collector – Siemens S7-1200 M62

## 1. Chép thư mục

Giải nén toàn bộ vào:

```text
D:\xampp\htdocs\NTP_PLC\collector
```

## 2. Mở PowerShell

```powershell
cd D:\xampp\htdocs\NTP_PLC\collector
Set-ExecutionPolicy -Scope Process Bypass
.\install.ps1
```

## 3. Cấu hình `.env`

Mở:

```text
D:\xampp\htdocs\NTP_PLC\collector\.env
```

Sửa:

```env
COLLECTOR_TOKEN=token_thật_của_plc_1
```

Nếu Laravel chạy bằng:

```powershell
php artisan serve
```

thì giữ:

```env
LARAVEL_BASE_URL=http://127.0.0.1:8000
```

## 4. Kiểm tra chỉ đọc PLC, chưa gửi Laravel

```powershell
.\.venv\Scripts\Activate.ps1
python collector.py --dry-run
```

Kết quả mong đợi:

```text
Đọc PLC thành công: 123 tag
Hoàn thành một chu kỳ với 123 tag
```

## 5. Kiểm tra heartbeat

```powershell
python collector.py --heartbeat
```

## 6. Chạy một chu kỳ có gửi Laravel

```powershell
python collector.py --once
```

Nếu API hợp lệ, log sẽ báo:

```text
Laravel nhận dữ liệu thành công
```

## 7. Kiểm tra database

```sql
SELECT COUNT(*) FROM plc_current_values;

SELECT *
FROM plc_collection_logs
ORDER BY id DESC
LIMIT 10;

SELECT *
FROM plc_current_values
ORDER BY collected_at DESC
LIMIT 20;
```

## 8. Chạy liên tục 5 giây/lần

```powershell
python collector.py
```

Dừng bằng:

```text
Ctrl + C
```

## 9. Log

```text
collector\logs\collector.log
```

## Payload gửi Laravel

```json
{
  "plc_code": "M62",
  "collected_at": "2026-07-30T16:45:00+07:00",
  "duration_ms": 813,
  "values": [
    {
      "address": "%I0.0",
      "source_name": "Dừng khẩn cấp",
      "data_type": "BOOL",
      "display_format": "BOOL",
      "value": true,
      "raw_value": "true",
      "quality": "GOOD",
      "comment": null
    }
  ]
}
```

Middleware Bearer token sẽ xác định PLC, nên `plc_code` chủ yếu dùng kiểm tra và ghi log.
