<?php
// ==============================================================================
// app.php - Main Dashboard (ปรับปรุงสำหรับแสดงผลเปอร์เซ็นต์ของ Tree 101)
// (แก้ไข) - ไฟล์นี้จะทำหน้าที่เป็น Frontend (ส่วนแสดงผล) เท่านั้น
// ==============================================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// (ลบ) - ย้ายการเชื่อมต่อ DB และ SQL Query ไปไว้ที่ api.php

// (ใหม่) ตั้งค่าการคำนวณฝั่ง Client (JavaScript)
// (ต้องตั้งค่าให้ตรงกับ api.php และ dbwrite.php)
$harvest_threshold = 90.0;
$cycle_days = 12.0; 
$fill_rate_per_day = ($cycle_days > 0) ? ($harvest_threshold / $cycle_days) : 0; 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rubber Tree Monitoring System - Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .status-harvest-ready { background-color: #d4edda; color: #155724; font-weight: bold; } 
        .status-no-data { background-color: #e9ecef; color: #6c757d; } 
        
        /* (ใหม่) เพิ่ม animation_loading เล็กน้อย */
        .loading-cell { color: #aaa; font-style: italic; }
    </style>
</head>
<body>

<div class="container mt-5">
    <h1 class="mb-4 text-primary">Rubber Tree Plantation Dashboard (Tree 101)</h1>

    <div class="alert alert-info" role="alert">
        <h2 class="h5">News and Current Latex Prices </h2>
        <?php include 'price_fetcher.php'; // Include Price Fetcher file (ต้องแน่ใจว่าไฟล์นี้อยู่) ?>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h2>Status for Tree 101</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Tree ID</th>
                            <th>Location</th>
                            <th>Fill Level (%)</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <!-- (แก้ไข) - <tbody> จะถูกเติมด้วย JavaScript -->
                    <tbody id="data-container"> 
                        <!-- ข้อมูลจะถูกโหลดมาใส่ที่นี่ -->
                        <tr>
                            <td colspan="5" class="text-center loading-cell">กำลังโหลดข้อมูล...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- (ใหม่) - ส่วนของ JavaScript สำหรับการอัปเดตอัตโนมัติ -->
<script>
    // ดึงค่าการคำนวณจาก PHP มาใส่ใน JavaScript
    const harvestThreshold = <?php echo $harvest_threshold; ?>;
    const fillRatePerDay = <?php echo $fill_rate_per_day; ?>;
    const updateInterval = 5000; // อัปเดตทุก 5 วินาที (5000ms)

    // ฟังก์ชันสำหรับดึงและอัปเดตข้อมูล
    async function updateData() {
        try {
            const response = await fetch('api.php'); // 1. ไปดึงข้อมูลจาก api.php
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json(); // 2. แปลงข้อมูลเป็น JSON

            const container = document.getElementById('data-container');
            let newRowHTML = '';

            // 3. ตรวจสอบว่ามีข้อมูลส่งกลับมาหรือไม่
            if (data && data.tree_id) {
                let statusText = "✓ Normal";
                let rowClass = "";
                let currentPercentage = parseFloat(data.latex_percentage);

                // --- 4. คำนวณสถานะ (เหมือนที่เคยทำใน PHP) ---
                if (currentPercentage >= harvestThreshold) {
                    rowClass = "status-harvest-ready";
                    statusText = "🌳 พร้อมเก็บเกี่ยว!";
                } else {
                    if (fillRatePerDay > 0 && currentPercentage >= 0) {
                        const percentageRemaining = harvestThreshold - currentPercentage;
                        const daysRemainingDynamic = percentageRemaining / fillRatePerDay;
                        statusText = `⏳ (คาดว่าอีก ${Math.ceil(daysRemainingDynamic)} วัน)`;
                    } else {
                        statusText = `⏳ กำลังสะสม (${currentPercentage.toFixed(1)}%)`;
                    }
                }
                // --- สิ้นสุดการคำนวณสถานะ ---

                // 5. สร้าง HTML สำหรับแถวใหม่
                newRowHTML = `
                    <tr class="${rowClass}">
                        <td>${data.tree_id}</td>
                        <td>${data.tree_location}</td>
                        <td>${currentPercentage !== null ? currentPercentage.toFixed(2) + '%' : 'N/A'}</td>
                        <td>${statusText}</td>
                        <td>${data.last_update}</td>
                    </tr>
                `;

            } else {
                // กรณีไม่พบข้อมูล
                newRowHTML = `
                    <tr>
                        <td colspan="5" class="text-center status-no-data">? No Data (ยังไม่ได้รับข้อมูลจากเซ็นเซอร์)</td>
                    </tr>
                `;
            }
            
            // 6. อัปเดตตาราง
            container.innerHTML = newRowHTML;

        } catch (error) {
            // กรณี API ล้มเหลว หรือเชื่อมต่อไม่ได้
            console.error('Fetch error:', error);
            const container = document.getElementById('data-container');
            container.innerHTML = `<tr><td colspan="5" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
        }
    }

    // 7. สั่งให้ทำงานครั้งแรกเมื่อโหลดหน้า
    updateData();

    // 8. สั่งให้ทำงานซ้ำทุกๆ 'updateInterval' (5 วินาที)
    setInterval(updateData, updateInterval);

</script>

</body>
</html>
<?php 
// (ลบ) - ไม่ต้องปิด $conn ที่นี่ เพราะเราไม่ได้เปิด
?>

