<?php
declare(strict_types=1);

function send_app_mail(
    string $to,
    string $subject,
    string $body,
    ?string $replyTo = null
): bool {
    $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'no-reply@ketelofferte24.nl';
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'KetelOfferte24.nl';

    $headers = [];

    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';

    if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    return mail(
        $to,
        $subject,
        $body,
        implode("\r\n", $headers)
    );
}
