<?php
session_start();

// Görüntü boyutları
$width = 200;
$height = 60;

// Görüntü oluşturma
$image = imagecreatetruecolor($width, $height);
$background_color = imagecolorallocate($image, 255, 255, 255);
imagefilledrectangle($image, 0, 0, $width, $height, $background_color);

// Rastgele kod oluşturma
$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$code = '';
for ($i = 0; $i < 6; $i++) {
    $code .= $characters[rand(0, strlen($characters) - 1)];
}
$_SESSION['captcha_code'] = $code;

// Kodu görüntüye yazma
$text_color = imagecolorallocate($image, 48, 48, 48);
imagettftext($image, 30, rand(-10, 10), 50, 45, $text_color, 'arial.ttf', $code);

// Görüntüyü bozmak için rastgele noktalar ve çizgiler ekle
for ($i = 0; $i < 100; $i++) {
    $point_color = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
    imagesetpixel($image, rand(0, $width), rand(0, $height), $point_color);
}
for ($i = 0; $i < 5; $i++) {
    $line_color = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
    imageline($image, 0, rand(0, $height), $width, rand(0, $height), $line_color);
}

// Görüntüyü tarayıcıya yolla
header('Content-Type: image/jpeg');
imagejpeg($image);
imagedestroy($image);
?>
