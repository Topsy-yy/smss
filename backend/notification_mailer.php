<?php
require_once __DIR__ . '/../PHPMailer/PHPMailerAutoload.php';

function sendNotificationEmail($toEmail, $subject, $htmlBody, $plainTextBody = '') {
    if (empty($toEmail)) {
        return false;
    }

    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = SMTP_AUTH;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;

    $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
    $mail->addReplyTo(SMTP_USER, SMTP_FROM_NAME);
    $mail->addAddress($toEmail);
    $mail->isHTML(true);

    $mail->Subject = $subject;
    $mail->Body = $htmlBody;
    $mail->AltBody = ($plainTextBody !== '') ? $plainTextBody : strip_tags($htmlBody);

    return $mail->send();
}
?>
