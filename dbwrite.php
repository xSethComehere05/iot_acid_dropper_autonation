<?php
// 1. เปลี่ยนชื่อไฟล์ include ให้ตรงกับ app.php
include 'db_connect.php'; // <-- แก้ไขจาก 'connect.php'

// 2. API KEY (ควรตั้งให้ตรงกับ ESP32)
$api_key_value = "tPmAT5Ab3j7F9";

$api_key = $sensor = $location = $value1 = $value2 = $value3 = "";

// ===== (ใหม่) ตั้งค่า Min/Max Distance (จากโค้ด Arduino) =====
// 6.4 cm คือ ระดับสูง (100%)
// 14.5 cm คือ ระดับต่ำ (0%)
$minDist = 6.4; 
$maxDist = 14.5;
// =======================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $api_key = test_input($_POST["api_key"]);

    if($api_key == $api_key_value) {
        
        $location = test_input($_POST["location"]);  // นี่คือ tree_id เช่น "101"

        // ===== (ใหม่) 1. ตรวจสอบว่าใช่ต้นไม้ 101 หรือไม่ =====
        if ($location != "101") {
            echo "Data rejected: Not tree 101.";
            exit(); // ออกจากการทำงานทันที
        }
        // ===============================================

        $sensor   = test_input($_POST["sensor"]);    // เช่น "ProjectSensor"
        $value1   = test_input($_POST["value1"]);    // นี่คือ distance (เราจะแมปไปที่ latex_percentage)
        
        // $conn มาจากไฟล์ db_connect.php ที่เรา include
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // ===== (ใหม่) 2. คำนวณเปอร์เซ็นต์ความจุ =====
        $rawDistance = (float)$value1;
        $fillRange = $maxDist - $minDist;
        
        // คำนวณ % (กลับด้านค่า: ระยะทางน้อย = % มาก)
        $fillPercentage = (($maxDist - $rawDistance) / $fillRange) * 100;

        // จำกัดค่าให้อยู่ระหว่าง 0 - 100
        if ($fillPercentage < 0) $fillPercentage = 0.0;
        if ($fillPercentage > 100) $fillPercentage = 100.0;
        // ==========================================

        // 3. (สำคัญมาก) แก้ไข SQL ให้ INSERT เฉพาะค่าที่คำนวณได้
        // เราไม่เก็บ $value2 (acid_level) แล้วตามคำขอ
        
        $sql = "INSERT INTO sensor_data (tree_id, latex_percentage, timestamp)
                VALUES ('$location', '$fillPercentage', NOW())";
        
        // โค้ดเดิม (เก็บไว้อ้างอิง)
        // $sql = "INSERT INTO sensor_data (tree_id, latex_percentage, acid_level_percent, timestamp)
        //         VALUES ('$location', '$value1', '$value2', NOW())";

        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully (Tree: $location, Fill: " . number_format($fillPercentage, 2) . "%)";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

        $conn->close();
    }
    else {
        echo "Wrong API Key provided.";
    }

}
else {
    echo "No data posted with HTTP POST.";
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

