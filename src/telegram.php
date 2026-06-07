<?php

require_once __DIR__ . '/config.php';


function getDebug() {
    return DEBUG;
}

function getSecretKey() {
    return SECRET_KEY;
}

function _messageText($name, $email, $phone, $printSize = null, $comment = null) {
    return "📩 Новый контакт с A-Pro-Print\n" .
           "Имя: " . ($name ?: '-') . "\n" .
           "Почта: " . ($email ?: '-') . "\n" .
           "Номер телефона: " . ($phone ?: '-') . "\n" .
           "Размер покраски: " . ($printSize ?: '-') . "\n" .
           "Комментарий: " . ($comment ?: '-');
}

function sendTelegramNotification($name, $email, $phone, $printSize = null, $comment = null) {
    if (!TELEGRAM_BOT_TOKEN || !TELEGRAM_BOT_CHAT_ID) {
        error_log("Telegram bot token или chat id не установлены");
        return false;
    }
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";

    $data = [
        "chat_id" => TELEGRAM_BOT_CHAT_ID,
        "text" => _messageText($name, $email, $phone, $printSize, $comment),
        "disable_web_page_preview" => true
    ];

    // Try using cURL if available (more reliable, better error info)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        // Verify SSL by default; if your environment has problems, consider configuring certs instead of disabling verification.
        $response = curl_exec($ch);

        if ($response === false) {
            error_log('cURL error when sending Telegram message: ' . curl_error($ch));
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $result = json_decode($response, true);

        if ($httpCode !== 200 || !isset($result['ok']) || !$result['ok']) {
            error_log('Ошибка Telegram: HTTP ' . $httpCode . ' body: ' . $response);
            return false;
        }

        return true;
    }

    // Fallback to stream approach if cURL is not available
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);

    // Before using streams, ensure HTTPS wrapper is available (openssl enabled)
    $wrappers = stream_get_wrappers();
    if (!in_array('https', $wrappers, true)) {
        error_log("Ошибка: 'https' wrapper недоступен. Включите расширение OpenSSL или установите/включите cURL в PHP.");
        return false;
    }

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $headers = 'no headers';

        if (function_exists('http_get_last_response_headers')) {
            $hdrs = http_get_last_response_headers();

            if (!empty($hdrs)) {
                $headers = implode(' | ', $hdrs);
            }
        }

        error_log("Ошибка отправки Telegram сообщения (streams). HTTP headers: " . $headers);

        return false;
    }

    $result = json_decode($response, true);
    if (!isset($result['ok']) || !$result['ok']) {
        error_log("Ошибка Telegram: " . json_encode($result));
        return false;
    }

    return true;
}
