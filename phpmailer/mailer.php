<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

function createMailer()
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = 'bmail.apexgenlabs.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kinza@apexgenlabs.com';
    $mail->Password = '5$tuFlg-55DuyEs5ow';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('kinza@apexgenlabs.com', 'Email_Marketing_System');

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
}

function sendMarketingEmail($toEmail, $toName, $subject, $body)
{
    try {
        $mail = createMailer();

        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->Body = nl2br($body);
        $mail->AltBody = strip_tags($body);

        $mail->send();

        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $mail->ErrorInfo
        ];
    }
}