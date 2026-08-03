-- Kiểm tra các cột Collector V3
SHOW COLUMNS FROM plc_current_values
WHERE Field IN (
    'history_base_value',
    'history_base_quality',
    'last_history_at'
);

-- Kiểm tra trạng thái sau khi chạy collector
SELECT
    t.id,
    t.source_name,
    t.address,
    t.data_type,
    t.history_enabled,
    t.history_deadband,
    cv.value,
    cv.history_base_value,
    cv.history_base_quality,
    cv.last_history_at,
    cv.collected_at
FROM plc_tags t
LEFT JOIN plc_current_values cv
    ON cv.plc_tag_id = t.id
WHERE t.plc_id = 1
ORDER BY t.id;

-- Đếm current rows, mỗi tag chỉ nên có tối đa một dòng
SELECT
    plc_tag_id,
    COUNT(*) AS total
FROM plc_current_values
GROUP BY plc_tag_id
HAVING COUNT(*) > 1;
