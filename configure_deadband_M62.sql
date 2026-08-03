-- Nhiệt độ: chỉ lưu khi thay đổi từ 0.2 độ trở lên
UPDATE plc_tags
SET history_deadband = 0.2
WHERE plc_id = 1
  AND data_type = 'REAL'
  AND (
      source_name LIKE '%Nhiệt độ%'
      OR source_name LIKE '%nhiệt độ%'
  );

-- Áp suất: chỉ lưu khi thay đổi từ 0.05 trở lên
UPDATE plc_tags
SET history_deadband = 0.05
WHERE plc_id = 1
  AND data_type = 'REAL'
  AND (
      source_name LIKE '%Áp suất%'
      OR source_name LIKE '%Ápp lực%'
      OR source_name LIKE '%áp suất%'
      OR source_name LIKE '%áp lực%'
  );

-- Tải, cân, định lượng: ngưỡng ban đầu 0.1
UPDATE plc_tags
SET history_deadband = 0.1
WHERE plc_id = 1
  AND data_type = 'REAL'
  AND (
      source_name LIKE '%Tải%'
      OR source_name LIKE '%Cân%'
      OR source_name LIKE '%Định lượng%'
  );

-- Giờ chạy: 0.01 giờ, tương đương khoảng 36 giây
UPDATE plc_tags
SET history_deadband = 0.01
WHERE plc_id = 1
  AND data_type = 'REAL'
  AND source_name LIKE '%giờ chạy%';

-- BOOL phải ghi ngay khi đổi trạng thái
UPDATE plc_tags
SET history_deadband = 0
WHERE plc_id = 1
  AND data_type = 'BOOL';

-- Kiểm tra kết quả
SELECT
    id,
    source_name,
    address,
    data_type,
    history_enabled,
    history_deadband
FROM plc_tags
WHERE plc_id = 1
ORDER BY data_type, source_name;
