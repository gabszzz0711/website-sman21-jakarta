<?php
/**
 * SMAN 21 JAKARTA
 * File: api/ai-chat.php
 * Deskripsi: Endpoint AI Chatbot Anti-Gagal (Auto-Fallback ke Database SQL)
 */

ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// =============================================================
// 1. KONFIGURASI API KEY & DATABASE
// =============================================================

define('GEMINI_API_KEY', 'AQ.API_KEY');

$db_host = 'sql305.byethost3.com';
$db_user = 'b3_43046986';
$db_pass = 'J@karta5';
$db_name = 'b3_43046986_sman21jkt';

$pdo = null;

if (file_exists(__DIR__ . '/../config/database.php')) {
    @include_once __DIR__ . '/../config/database.php';
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        $pdo = null;
    }
}

function sendSuccess($reply, $source = 'S21 AI Assistant') {
    if (ob_get_length()) ob_clean();
    http_response_code(200);
    echo json_encode([
        'status'       => 'success',
        'answer'       => $reply,
        'reply'        => $reply,
        'message'      => $reply,
        'source'       => $source,
        'related_link' => ''
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// =============================================================
// 2. TANGKAP INPUT PENGGUNAKAN
// =============================================================

$userQuery = trim($_POST['query'] ?? $_POST['q'] ?? $_GET['query'] ?? $_GET['q'] ?? '');

if (empty($userQuery)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if (is_array($jsonData)) {
            $userQuery = trim($jsonData['query'] ?? $jsonData['q'] ?? $jsonData['message'] ?? '');
        }
    }
}

if (empty($userQuery)) {
    sendSuccess("Halo! Ada yang bisa saya bantu mengenai informasi SMAN 21 Jakarta?");
}

// =============================================================
// 3. CARI JAWABAN DI DATABASE SQL TERLEBIH DAHULU (PENCARIAN LOKAL)
// =============================================================

$localAnswer = null;
$allKnowledgeText = "";

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT pertanyaan, jawaban, keyword FROM ai_knowledge WHERE status_aktif = 1 ORDER BY prioritas DESC");
        if ($stmt) {
            $rows = $stmt->fetchAll();
            $queryLower = mb_strtolower($userQuery, 'UTF-8');
            
            foreach ($rows as $row) {
                $allKnowledgeText .= "P: " . $row['pertanyaan'] . " | J: " . $row['jawaban'] . "\n";
                
                // Cek pencocokan kata kunci
                $keywords = array_map('trim', explode(',', mb_strtolower($row['keyword'], 'UTF-8')));
                foreach ($keywords as $kw) {
                    if (!empty($kw) && strpos($queryLower, $kw) !== false) {
                        $localAnswer = $row['jawaban'];
                        break 2;
                    }
                }
                
                // Cek pencocokan judul pertanyaan
                if (strpos($queryLower, mb_strtolower($row['pertanyaan'], 'UTF-8')) !== false) {
                    $localAnswer = $row['jawaban'];
                    break;
                }
            }
        }
    } catch (Exception $e) {
        // Abaikan error database
    }
}

// =============================================================
// 4. COBA PANGGAL GEMINI API (JIKA KONEKSI TERSEDIA)
// =============================================================

$apiKey = trim(GEMINI_API_KEY, " \t\n\r\0\x0B\"'");

if (!empty($apiKey) && strpos($apiKey, 'AIzaSy') === 0 && function_exists('curl_init')) {
    
    $systemInstruction = "Anda adalah Asisten AI Resmi SMAN 21 Jakarta. Jawab pertanyaan dengan ramah, sopan, dan informatif berdasarkan data sekolah.";
    $promptContent = "Data Sekolah:\n" . ($allKnowledgeText ?: "SMAN 21 Jakarta berlokasi di Kayu Putih, Pulogadung, Jakarta Timur.") . "\n\nPertanyaan: " . $userQuery;

    $payloadData = [
        'system_instruction' => ['parts' => [['text' => $systemInstruction]]],
        'contents' => [['role' => 'user', 'parts' => [['text' => $promptContent]]]],
        'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 800]
    ];

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . urlencode($apiKey));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payloadData),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            sendSuccess(trim($result['candidates'][0]['content']['parts'][0]['text']), 'S21 AI Engine');
        }
    }
}

// =============================================================
// 5. FALLBACK OTOMATIS JIKA GEMINI DIBLOKIR/ERROR
// =============================================================

if (!empty($localAnswer)) {
    sendSuccess($localAnswer, 'Basis Data SMAN 21');
}

// Jawaban standar jika data tidak ditemukan & Gemini tidak terhubung
$defaultResponse = "SMAN 21 Jakarta berlokasi di Jl. Tanah Mas Raya No.1, Kayu Putih, Pulogadung, Jakarta Timur.\n\n"
                 . "Untuk informasi lebih lanjut mengenai pendaftaran, akademik, atau kegiatan sekolah, silakan hubungi pihak tata usaha di (021) 4892346 atau email ke info@sman21jkt.sch.id.";

sendSuccess($defaultResponse, 'Sistem Informasi SMAN 21');