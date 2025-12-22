#define ENABLE_USER_AUTH
#define ENABLE_DATABASE

#include <FirebaseClient.h>
#include "ExampleFunctions.h"

// ===============================
// WiFi 설정
// ===============================
#define WIFI_SSID ""
#define WIFI_PASSWORD ""

// ===============================
// Firebase 설정
// ===============================
#define API_KEY ""
#define USER_EMAIL ""
#define USER_PASSWORD ""
#define DATABASE_URL ""

// ===============================
// 초음파 핀
// ===============================
#define TRIG_PIN D7 
#define ECHO_PIN D2
#define DETECT_THRESHOLD 90.0  // cm 이하면 감지로 판단

// ===============================
// Firebase 준비
// ===============================
SSL_CLIENT ssl_client;
using AsyncClient = AsyncClientClass;
AsyncClient aClient(ssl_client);

UserAuth user_auth(API_KEY, USER_EMAIL, USER_PASSWORD, 3000);
FirebaseApp app;
RealtimeDatabase Database;

bool firebase_ready = false;

// ===============================
// 초음파 거리 측정 함수
// ===============================
float getDistance()
{
    // 안정적 트리거
    digitalWrite(TRIG_PIN, LOW);
    delayMicroseconds(2);

    digitalWrite(TRIG_PIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIG_PIN, LOW);

    // Echo 시간 측정 (타임아웃 30ms)
    long duration = pulseIn(ECHO_PIN, HIGH, 30000);

    // 시간→거리(cm)
    float distance = (duration * 0.0343) / 2.0;

    return distance;
}

// ===============================
// SETUP
// ===============================
void setup()
{
    Serial.begin(115200);

    pinMode(TRIG_PIN, OUTPUT);
    pinMode(ECHO_PIN, INPUT);

    // -------------------------------
    // WiFi 연결
    // -------------------------------
    Serial.println("Connecting to WiFi...");
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    while (WiFi.status() != WL_CONNECTED) {
        Serial.print(".");
        delay(300);
    }
    Serial.println("\nWiFi connected!");

    // -------------------------------
    // Firebase 인증
    // -------------------------------
    set_ssl_client_insecure_and_buffer(ssl_client);

    initializeApp(aClient, app, getAuth(user_auth), auth_debug_print, "authTask");

    app.getApp<RealtimeDatabase>(Database);
    Database.url(DATABASE_URL);

    Serial.println("Firebase initialized");
}

// ===============================
// LOOP
// ===============================
void loop()
{
    app.loop();

    if (app.ready() && !firebase_ready) {
        firebase_ready = true;
        Serial.println("Firebase Ready!");
    }

    if (!firebase_ready)
        return;

    // -------------------------------
    // 초음파 거리 측정
    // -------------------------------
    float dist = getDistance();

    bool detected = (dist > 0 && dist <= DETECT_THRESHOLD);
    long timestamp = millis() / 1000;

    Serial.print("Distance: ");
    Serial.print(dist);
    Serial.println(" cm");

    // -------------------------------
    // Firebase에 JSON 업로드
    // -------------------------------
    object_t json, j1, j2, j3;
    JsonWriter writer;

    writer.create(j1, "detected", detected);
    writer.create(j2, "distance", number_t(dist, 2));
    writer.create(j3, "timestamp", timestamp);
    writer.join(json, 3, j1, j2, j3);

    bool ok = Database.update(aClient, "/events", json);

    if (ok)
        Serial.println("Firebase updated");
    else
        Serial.println(aClient.lastError().message().c_str());

    delay(800);
}
