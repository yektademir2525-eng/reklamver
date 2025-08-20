<?php
session_start();

// HTTP_REFERER (Yönlendiren) kontrolü
// Kodu çalmaya çalışanların doğrudan form göndermesini engeller.
$allowed_domain = 'https://www.sitenizinadi.com'; // Buraya kendi sitenizin adresini yazın
if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], $allowed_domain) !== 0) {
    die("Yetkisiz Erişim! Bu dosya doğrudan çağrılamaz.");
}

// E-posta adresi
$to_email = "yektademirkol3@gmail.com"; 
$subject = "Yeni Reklam Başvurusu";

// DDoS ve Spam önleme için günlük sınır
$spam_limit_seconds = 24 * 60 * 60; // 24 saat
$user_ip = $_SERVER['REMOTE_ADDR'];
$log_file = 'form_logs.txt';

// Yardımcı fonksiyonlar
function redirectWithError($message) {
    header("Location: reklam.html?status=error&message=" . urlencode($message));
    exit();
}

function redirectWithSuccess() {
    header("Location: reklam.html?status=success");
    exit();
}

// Honeypot (Bal Küpü) kontrolü
if (!empty($_POST['nickname'])) {
    redirectWithError("Bot Algılandı!");
}

// Günlük limit kontrolü
$logs = file_exists($log_file) ? unserialize(file_get_contents($log_file)) : [];
$last_submission_time = isset($logs[$user_ip]) ? $logs[$user_ip] : 0;
$current_time = time();

if ($current_time - $last_submission_time < $spam_limit_seconds) {
    redirectWithError("Günde sadece bir kez reklam gönderebilirsiniz. Lütfen daha sonra tekrar deneyin.");
}

// Form verilerinin işlenmesi
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Bot doğrulama (Captcha) kontrolü
    if (!isset($_POST['captcha']) || !isset($_SESSION['captcha_code']) || strtolower($_POST['captcha']) != strtolower($_SESSION['captcha_code'])) {
        unset($_SESSION['captcha_code']);
        redirectWithError("Bot doğrulama kodu yanlış girildi. Lütfen tekrar deneyin.");
    }
    
    // Verileri al ve doğrula
    $adUrl = $_POST['adUrl'];
    if (!filter_var($adUrl, FILTER_VALIDATE_URL) || !preg_match('/\.(gif)$/i', $adUrl)) {
        redirectWithError("Geçersiz GIF URL'si. Lütfen .gif ile biten geçerli bir URL girin.");
    }

    $adTitle = htmlspecialchars($_POST['adTitle']);
    $adDescription = htmlspecialchars($_POST['adDescription']);
    $targetUrl = htmlspecialchars($_POST['targetUrl']);

    // E-posta içeriği
    $email_content = "
        <html>
        <head><title>Yeni Reklam Başvurusu</title></head>
        <body>
            <h1>Yeni Reklam Başvurusu Geldi</h1>
            <p><strong>Reklam Başlığı:</strong> " . $adTitle . "</p>
            <p><strong>Reklam Açıklaması:</strong></p>
            <p>" . nl2br($adDescription) . "</p>
            <hr>
            <p><strong>GIF Bağlantısı:</strong> <a href='" . $adUrl . "'>" . $adUrl . "</a></p>
            <p><strong>Hedef Bağlantı (URL):</strong> <a href='" . $targetUrl . "'>" . $targetUrl . "</a></p>
            <p><strong>Gönderen IP:</strong> " . $user_ip . "</p>
        </body>
        </html>
    ";

    // E-posta başlıkları
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Reklam Formu <noreply@siteadiniz.com>" . "\r\n";

    // E-postayı gönder ve logla
    if (mail($to_email, $subject, $email_content, $headers)) {
        $logs[$user_ip] = $current_time;
        file_put_contents($log_file, serialize($logs));
        unset($_SESSION['captcha_code']); 
        redirectWithSuccess();
    } else {
        redirectWithError("Reklam başvurusu gönderilirken bir sorun oluştu.");
    }
} else {
    redirectWithError("Geçersiz İstek.");
}
?>
