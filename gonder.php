<?php
session_start();

// HTTP_REFERER (Yönlendiren) kontrolü
$allowed_domain = 'https://yektademir2525-eng.github.io/reklamver/gonder.php'; // Buraya adres
if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], $allowed_domain) !== 0) {
    die("Yetkisiz erişim.Bu dosya dogrudan cagrilamaz.");
}

// eposta
$to_email = "yektademir2525@gmail.com"; 
$subject = "Yeni Reklam Başvurusu";

// ddos koruma
$spam_limit_seconds = 24 * 60 * 60; // 24 saat
$user_ip = $_SERVER['REMOTE_ADDR'];
$log_file = 'form_logs.txt';

// yardımcı şeyler bişe
function redirectWithError($message) {
    header("Location: reklam.html?status=error&message=" . urlencode($message));
    exit();
}

function redirectWithSuccess() {
    header("Location: reklam.html?status=success");
    exit();
}

// honeypot
if (!empty($_POST['nickname'])) {
    redirectWithError("Bot Algılandı!");
}

// limit kontrolu
$logs = file_exists($log_file) ? unserialize(file_get_contents($log_file)) : [];
$last_submission_time = isset($logs[$user_ip]) ? $logs[$user_ip] : 0;
$current_time = time();

if ($current_time - $last_submission_time < $spam_limit_seconds) {
    redirectWithError("Günde sadece bir kez reklam gönderebilirsiniz. Lütfen daha sonra tekrar deneyin.");
}

// form veri işlenmesi
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // bot doğrulama 
    if (!isset($_POST['captcha']) || !isset($_SESSION['captcha_code']) || strtolower($_POST['captcha']) != strtolower($_SESSION['captcha_code'])) {
        unset($_SESSION['captcha_code']);
        redirectWithError("Bot doğrulama kodu yanlış girildi. Lütfen tekrar deneyin.");
    }
    
    // verileri al ve doğrula
    $adUrl = $_POST['adUrl'];
    if (!filter_var($adUrl, FILTER_VALIDATE_URL) || !preg_match('/\.(gif)$/i', $adUrl)) {
        redirectWithError("Geçersiz GIF URL'si. Lütfen .gif ile biten geçerli bir URL girin.");
    }

    $adTitle = htmlspecialchars($_POST['adTitle']);
    $adDescription = htmlspecialchars($_POST['adDescription']);

    // E-posta 
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
            <p><strong>Gönderen IP:</strong> " . $user_ip . "</p>
        </body>
        </html>
    ";

    // eposta başliklai
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Reklam Formu <noreply@siteadiniz.com>" . "\r\n";

    // gönder  logla
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
