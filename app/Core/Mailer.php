<?php
namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Sends email through PHPMailer (SMTP). When mail is disabled in config/config.php,
 * the message is written to storage/logs/mail.log instead.
 */
class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $html): bool
    {
        $c = config('mail');

        if (empty($c['enabled'])) {
            $entry = sprintf("==== %s ====\nTo: %s <%s>\nSubject: %s\n\n%s\n\n",
                date('Y-m-d H:i:s'), $toName, $toEmail, $subject, trim(strip_tags(str_replace(['<br>', '</p>', '</tr>'], "\n", $html))));
            file_put_contents(ROOT . '/storage/logs/mail.log', $entry, FILE_APPEND | LOCK_EX);
            return true;
        }

        require_once ROOT . '/lib/PHPMailer/Exception.php';
        require_once ROOT . '/lib/PHPMailer/PHPMailer.php';
        require_once ROOT . '/lib/PHPMailer/SMTP.php';

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $c['host'];
            $mail->Port       = (int) $c['port'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $c['username'];
            $mail->Password   = $c['password'];
            $mail->SMTPSecure = $c['encryption'];
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($c['from_email'], $c['from_name']);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);
            $mail->send();
            return true;
        } catch (MailException $ex) {
            error_log('Mail error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
