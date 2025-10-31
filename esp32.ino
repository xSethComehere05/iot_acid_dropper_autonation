/////////////////////////////////////////////////
// ====== ส่วนที่ 1: Headers และ Globals ====== //
/////////////////////////////////////////////////

// --- ไลบรารีสำหรับ WiFi และ HTTP (จากโค้ดที่ 1) ---
#include <WiFi.h>
#include <HTTPClient.h>

// --- ไลบรารีสำหรับ Servo (จากโค้ดที่ 2) ---
#include <ESP32Servo.h>

// --- ข้อมูล WiFi (จากโค้ดที่ 1) ---
// แทนที่ด้วยข้อมูล WiFi ของคุณ
const char* ssid     = "xSe1hz";
const char* password = "roblox12";

// --- ข้อมูล API และ Server (จากโค้ดที่ 1) ---
String apiKeyValue = "tPmAT5Ab3j7F9";
// TODO: อัปเดตชื่อเซ็นเซอร์และตำแหน่งให้ถูกต้อง
String sensorName = "ProjectSensor"; // แก้ไขจาก "BME280"
String sensorLocation = "101";

/////////////////////////////////////////////////
// ====== ส่วนที่ 2: Classes (จากโค้ดที่ 2) ====== //
/////////////////////////////////////////////////

///////////////////////////////////////
// -------- Raindrop Sensor -------- //
///////////////////////////////////////
class RaindropSensor {
private:
    int digitalPin;
    int analogPin;

public:
    RaindropSensor(int dPin, int aPin) {
        digitalPin = dPin;
        analogPin = aPin;
        pinMode(digitalPin, INPUT);
    }

    bool isWet() { return digitalRead(digitalPin) == LOW; }
    int readAnalog() { return analogRead(analogPin); }
};

///////////////////////////////////////
// -------- Pump Controller -------- //
///////////////////////////////////////
class PumpController {
private:
    int pumpPin;
    bool state;

public:
    PumpController(int pin) {
        pumpPin = pin;
        pinMode(pumpPin, OUTPUT);
        digitalWrite(pumpPin, HIGH); // HIGH = ปิดปั๊ม
        state = false;
    }

    void turnOn()  { digitalWrite(pumpPin, LOW);  state = true;  }
    void turnOff() { digitalWrite(pumpPin, HIGH); state = false; }
    bool isOn()    { return state; }
};

///////////////////////////////////////
// -------- Ultrasonic Sensor ------- //
///////////////////////////////////////
class MyUltrasonic {
private:
    int trigPin;
    int echoPin;
    bool enabled;

public:
    MyUltrasonic(int trig, int echo) {
        trigPin = trig;
        echoPin = echo;
        pinMode(trigPin, OUTPUT);
        pinMode(echoPin, INPUT);
        enabled = false;
    }

    void enable() { enabled = true; }

    float readDistanceCM() {
        if (!enabled) return -1;
        digitalWrite(trigPin, LOW);
        delayMicroseconds(2);
        digitalWrite(trigPin, HIGH);
        delayMicroseconds(10);
        digitalWrite(trigPin, LOW);
        long duration = pulseIn(echoPin, HIGH, 30000);
        if (duration == 0) return -1;
        return duration * 0.0343 / 2.0;
    }
};

///////////////////////////////////////
// -------- Motor Controller -------- //
///////////////////////////////////////
class MotorController {
 private:
  int motorPin;
  bool state;
 public:
  MotorController(int pin) {
    motorPin = pin;
    pinMode(motorPin, OUTPUT);
    digitalWrite(motorPin, HIGH);  // Active LOW
    state = false;
  }
  void turnOn() { digitalWrite(motorPin, LOW); state = true; }
  void turnOff() { digitalWrite(motorPin, HIGH); state = false; }
  bool isOn() { return state; }
};

///////////////////////////////////////
// -------- Continuous Servo -------- //
///////////////////////////////////////
class ContinuousServo {
private:
    Servo servo;
    int pin;
    int stopPulse;
    int forwardPulse;
    int backwardPulse;

public:
    ContinuousServo(int servoPin, int stopVal = 1500, int forwardVal = 1100, int backwardVal = 1900) {
        pin = servoPin;
        stopPulse = stopVal;
        forwardPulse = forwardVal;
        backwardPulse = backwardVal;
    }

    void begin() {
        servo.attach(pin);
        stop();
        Serial.println("Servo ready!");
    }

    void forward(float seconds) {
        Serial.print("FORWARD ");
        Serial.print(seconds, 2);
        Serial.println(" s");
        servo.writeMicroseconds(forwardPulse);
        delay((unsigned long)(seconds * 1000));
        stop();
    }

    void backward(float seconds) {
        Serial.print("BACKWARD ");
        Serial.print(seconds, 2);
        Serial.println(" s");
        servo.writeMicroseconds(backwardPulse);
        delay((unsigned long)(seconds * 1000));
        stop();
    }

    void stop() {
        Serial.println("STOP");
        servo.writeMicroseconds(stopPulse);
    }
};

/////////////////////////////////////////////////
// ====== ส่วนที่ 3: Objects และ Functions ====== //
/////////////////////////////////////////////////

// --- Object Instances (จากโค้ดที่ 2) ---
RaindropSensor rain(14, 34);
MotorController motor(27);
PumpController pump(13);
MyUltrasonic ultrasonic(26, 25);
ContinuousServo myServo(12);

// --- Global Logic Variables (จากโค้ดที่ 2) ---
unsigned long startTime = 0;
bool timerRunning = false;
float lastVal = 0;
bool firstReading = true;

// ===== Servo time mapping constants =====
const float minDist = 6.4;   // ระยะใกล้สุด (cm)
const float maxDist = 14.5;  // ระยะไกลสุด (cm)
const float minTime = 0.1;   // เวลา servo ต่ำสุด (s)
const float maxTime = 4.6;   // เวลา servo สูงสุด (s)

// ===== Function: map distance → servo time =====
float calculateServoTime(float distance) {
    float time = (distance - minDist) * (maxTime - minTime) / (maxDist - minDist) + minTime;
    if (time < minTime) time = minTime;
    if (time > maxTime) time = maxTime;
    return time;
}

// ===== Function: perform action =====
void performAction(float FW, float BW) {
    Serial.println("⚙️ Start... ");

    pump.turnOn();
    Serial.println("Pump turn on");
    delay(1000);
    pump.turnOff();
    Serial.println("Pump turn off");

    delay(500);

    myServo.forward(FW);
    Serial.println("Stick down");

    delay(500);

    motor.turnOn();
    Serial.println("Motor turn on");
    delay(5000);
    motor.turnOff();
    Serial.println("Motor turn off");

    delay(500);

    myServo.backward(BW);
    Serial.println("Stick up");

    Serial.println("✅ Done\n");
}

// ===== Function: Send Data to Server (จากโค้ดที่ 1) =====
// ฟังก์ชันนี้จะส่งข้อมูล 3 ค่าไปยังเซิร์ฟเวอร์
void sendDataToServer(float dist, int rainAnalog, bool isRaining) {
    // ตรวจสอบการเชื่อมต่อ WiFi ก่อน
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        
        // ระบุ URL ของเซิร์ฟเวอร์
        http.begin("https://angsila.informatics.buu.ac.th/~67160205/tree/dbwrite.php");
        
        // ระบุ content-type header
        http.addHeader("Content-Type", "application/x-www-form-urlencoded");

        // แปลงค่าเป็น String
        String value1_str = String(dist, 2); // ระยะทาง (ทศนิยม 2 ตำแหน่ง)
        String value2_str = String(rainAnalog); // ค่าฝนแบบ analog
        String value3_str = String(isRaining ? 1 : 0); // ค่าฝนแบบ digital (1=เปียก, 0=แห้ง)

        // เตรียมข้อมูลที่จะ POST
        String httpRequestData = "api_key=" + apiKeyValue + "&sensor=" + sensorName
                                 + "&location=" + sensorLocation + "&value1=" + value1_str
                                 + "&value2=" + value2_str + "&value3=" + value3_str;

        Serial.print("httpRequestData: ");
        Serial.println(httpRequestData);
        
        // ส่ง HTTP POST request
        int httpResponseCode = http.POST(httpRequestData);
             
        if (httpResponseCode > 0) {
            Serial.print("HTTP Response code: ");
            Serial.println(httpResponseCode);
        } else {
            Serial.print("Error code: ");
            Serial.println(httpResponseCode);
        }
        // ปิดการเชื่อมต่อ
        http.end();
    } else {
        Serial.println("WiFi Disconnected. Cannot send data.");
    }
}


/////////////////////////////////////////////////
// ====== ส่วนที่ 4: Setup (รวม 2 โค้ด) ======= //
/////////////////////////////////////////////////

void setup() {
    Serial.begin(115200); // ใช้ Baud rate จากโค้ดที่ 2
    
    // --- เริ่มการเชื่อมต่อ WiFi (จากโค้ดที่ 1) ---
    WiFi.begin(ssid, password);
    Serial.println("Connecting to WiFi");
    while(WiFi.status() != WL_CONNECTED) { 
        delay(500);
        Serial.print(".");
    }
    Serial.println("");
    Serial.print("Connected to WiFi network with IP Address: ");
    Serial.println(WiFi.localIP());
    // --- สิ้นสุดการเชื่อมต่อ WiFi ---

    delay(5000); // Delay จากโค้ดที่ 2
    
    // --- เริ่มการตั้งค่าเซ็นเซอร์ (จากโค้ดที่ 2) ---
    ultrasonic.enable();
    myServo.begin();
    delay(5000); // Delay จากโค้ดที่ 2
}

/////////////////////////////////////////////////
// ====== ส่วนที่ 5: Loop (รวม 2 โค้ด) ======== //
/////////////////////////////////////////////////

void loop() {
    float distances[5];
    bool raining = rain.isWet();
    int rainAnalogVal = rain.readAnalog(); // << อ่านค่า analog เพื่อส่ง
    
    // อ่าน ultrasonic 5 ครั้ง ห่างกัน 1 วิ
    for (int i = 0; i < 5; i++) {
        distances[i] = ultrasonic.readDistanceCM();
        Serial.print("Distance reading ");
        Serial.print(i + 1);
        Serial.print(": ");
        Serial.println(distances[i]);
        delay(1000);
    }

    // หาค่าที่น้อยที่สุด
    float val = distances[0];
    for (int i = 1; i < 5; i++) {
        if (distances[i] < val) val = distances[i];
    }

    // คำนวณเวลา servo ตาม distance
    float FW = calculateServoTime(val) + 0.3;
    float BW = FW + 0.2; // เวลากลับขึ้น สามารถปรับตามต้องการ

    Serial.println("----------------------------");
    Serial.print("Min distance (val): "); Serial.println(val, 2);
    Serial.print("Servo down time (FW): "); Serial.println(FW, 2);
    Serial.print("Rain (Digital): "); Serial.println(raining ? "YES" : "NO");
    Serial.print("Rain (Analog): "); Serial.println(rainAnalogVal); // << แสดงผลค่า analog
    Serial.println("----------------------------");

    // ข้ามรอบแรก
    if (firstReading) {
        firstReading = false;
        lastVal = val;
        Serial.println("🔹 รอบแรก: เก็บค่า val ไว้โดยไม่จับเวลา");
        
        // --- ส่งข้อมูลรอบแรก ---
        sendDataToServer(val, rainAnalogVal, raining);
        
        delay(3000);
        return;
    }

    // ตรวจว่าน้ำสูงขึ้น ≥ 0.5 → เริ่มจับเวลา
    if ((lastVal - val) >= 0.3 && !timerRunning) {
        startTime = millis();
        timerRunning = true;
        Serial.println("⏱ น้ำสูงขึ้น ≥ 0.5 → เริ่มจับเวลา 1 นาที...");
    }

    // ระหว่างจับเวลา
    if (timerRunning) {
        unsigned long elapsed = millis() - startTime;

        if (raining) {
            Serial.println("🌧 ฝนตกระหว่างจับเวลา → ทำงานทันที");
            performAction(FW, BW);
            timerRunning = false;
        } else if (elapsed >= 30000) { // ปรับตามเวลาจับ (นี่คือ 30 วินาที)
            Serial.println("⏰ ครบ 30 วินาที → ทำงาน");
            performAction(FW, BW);
            timerRunning = false;
        }
    }

    // --- ส่งข้อมูลไปยัง Server (จากโค้ดที่ 1) ---
    // ส่งข้อมูลทุกครั้งที่วน loop
    // value1 = ระยะทาง (val)
    // value2 = ค่าฝน analog (rainAnalogVal)
    // value3 = สถานะฝน (raining)
    sendDataToServer(val, rainAnalogVal, raining);

    lastVal = val;
    delay(3000); // Delay นี้มาจากโค้ดที่ 2
}