<?php
// ==============================================================================
// api.php - (ไฟล์ใหม่)
// ทำหน้าที่ดึงข้อมูลล่าสุดจาก DB และส่งกลับเป็น JSON
// ==============================================================================

header('Content-Type: application/json'); // บอก Browser ว่านี่คือไฟล์ JSON
ini_set('display_errors', 0); // ไม่ควรแสดง Error ใน API
error_reporting(0);

include 'db_connect.php'; 

if ($conn->connect_error) {
    // ส่งกลับ JSON error ถ้าเชื่อมต่อ DB ไม่ได้
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// SQL Query (เหมือนเดิม)
$sql_status = "
    SELECT 
        t.tree_id, 
        t.tree_location, 
        s.latex_percentage, 
        latest.max_time as last_update
    FROM 
        rubber_trees t
    LEFT JOIN (
        SELECT tree_id, MAX(timestamp) as max_time 
        FROM sensor_data 
        WHERE tree_id = '101'
        GROUP BY tree_id
    ) latest ON t.tree_id = latest.tree_id
    LEFT JOIN 
        sensor_data s 
        ON latest.tree_id = s.tree_id AND latest.max_time = s.timestamp
    WHERE 
        t.tree_id = '101'
    ORDER BY t.tree_id ASC;
";

$result = $conn->query($sql_status);
$data = null;

if ($result && $result->num_rows > 0) {
    // ดึงข้อมูลแถวเดียว
    $row = $result->fetch_assoc();
    
    // (สำคัญ) - แก้ไขข้อมูล NULL ก่อนส่ง
    // ถ้ายังไม่มีข้อมูล sensor (s.latex_percentage เป็น NULL)
    // เราจะยังส่งข้อมูลต้นไม้ (t.tree_id) กลับไป
    $data = [
        'tree_id'       => $row['tree_id'],
        'tree_location' => $row['tree_location'],
        'latex_percentage' => $row['latex_percentage'] !== null ? (float)$row['latex_percentage'] : null,
        'last_update'   => $row['last_update'] ?? 'No Sensor Data'
    ];
}

if (isset($conn)) {
    $conn->close(); 
}

// ส่งข้อมูลกลับเป็น JSON
// ถ้า $data ยังเป็น null (ไม่พบต้น 101 เลย) จะส่งค่า null กลับไป
echo json_encode($data);

?>
