<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Debug ON
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host = 'bmail.apexgenlabs.com';
    $mail->SMTPAuth = true;

    $mail->Username = 'kinza@apexgenlabs.com';
    $mail->Password = '5$tuFlg-55DuyEs5ow';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('kinza@apexgenlabs.com', 'Email Marketing System');

    // Put your receiver email here
    $mail->addAddress('YOUR_RECEIVER_EMAIL@gmail.com', 'Test User');

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    $mail->Subject = 'Test Email From PHP';
    $mail->Body = 'This is a test email from Email Marketing System.';
    $mail->AltBody = 'This is a test email from Email Marketing System.';

    $mail->send();

    echo "<h2 style='color:green;'>Email sent successfully!</h2>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Email not sent</h2>";
    echo "<b>PHPMailer Error:</b> " . $mail->ErrorInfo;
}