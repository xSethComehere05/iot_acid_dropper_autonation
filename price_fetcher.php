<?php
// ไฟล์ price_fetcher.php - สำหรับแสดงราคาและข่าวสารยางพารา (ข้อมูลจำลอง)

// ข้อมูลราคาและแนวโน้มปัจจุบัน (Hardcoded Data)
// ในการใช้งานจริง ข้อมูลส่วนนี้จะต้องถูกดึงมาจาก API หรือ Web Scraping
$current_price = "52.70 บาท/กก. (Field Latex ณ วันที่ " . date("d/m/Y") . ")"; 
$price_trend = "📈 +0.30 บาท จากเมื่อวาน"; // สมมติว่าราคาเพิ่มขึ้น

// ข้อมูลข่าวสารล่าสุด (Hardcoded Headlines)
$news_headlines = [
    "คาดการณ์: ราคายางพาราโลกมีแนวโน้มปรับตัวขึ้นในช่วงไตรมาสถัดไป เนื่องจากความต้องการของจีน",
    "กรมวิชาการเกษตร เตือนเกษตรกรเฝ้าระวังโรคใบร่วงในพื้นที่ภาคใต้ช่วงปลายฝน",
    "อัปเดต: สภาพอากาศเหมาะกับการกรีดยางในช่วง 3 วันนี้",
    "ธ.ก.ส. เปิดโครงการสินเชื่อเพื่อปรับปรุงสวนยางพารา"
];

// ส่วนแสดงผลลัพธ์ในรูปแบบ HTML
echo "<p><strong>ราคาตลาด:</strong> <span class='badge badge-success'>" . htmlspecialchars($current_price) . "</span> <span class='text-secondary'>" . htmlspecialchars($price_trend) . "</span></p>";
echo "<strong>ข่าวสารและข้อมูลอัปเดต:</strong>";
echo "<ul class='mb-0'>";
foreach ($news_headlines as $news) {
    echo "<li><small>" . htmlspecialchars($news) . "</small></li>";
}
echo "</ul>";

?>
