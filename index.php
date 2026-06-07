<?php

session_start();

require_once __DIR__ . '/src/telegram.php';

// Сообщения на разных языках
$MESSAGES = [
    "ru" => [
        200 => "Заявка успешно отправлена. Мы свяжемся с вами в ближайшее время.",
        400 => "Ошибка: некорректно заполнены имя или номер телефона."
    ],
    "kk" => [
        200 => "Өтінім сәтті жіберілді. Жақын арада сізбен хабарласамыз.",
        400 => "Қате: аты-жөніңіз немесе телефон нөміріңіз дұрыс толтырылмаған."
    ],
    "en" => [
        200 => "Your request has been sent successfully. We will contact you soon.",
        400 => "Error: the name or phone number was entered incorrectly."
    ]
];

// Функция для обработки URL маршрутов
function getRoute() {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $requestUri = rtrim($requestUri, '/') ?: '/';
    return $requestUri;
}

// Функция для рендеринга шаблонов (простая замена Jinja2)
function renderTemplate($template, $data = []) {
    extract($data);
    
    $templatePath = __DIR__ . '/templates/' . $template . '.html';
    
    if (!file_exists($templatePath)) {
        throw new Exception("Template not found: " . $template);
    }
    
    ob_start();
    include $templatePath;
    return ob_get_clean();
}

// Функция для URL генерации (замена url_for Flask)
function urlFor($type, $filename = null) {
    if ($type === 'static' && $filename) {
        return '/static/' . $filename;
    }
    return '/';
}

// Функция для получения лучшего языка
function getBestLanguage() {
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $languages = ['ru', 'kk', 'en'];
    
    foreach ($languages as $lang) {
        if (strpos($acceptLanguage, $lang) !== false) {
            return $lang;
        }
    }
    
    return 'ru';
}

// Получаем текущий маршрут
$route = getRoute();

// Обработка POST-запроса на главной странице
if ($route === '/' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем язык из сессии, куки или заголовков
    $language = $_SESSION['language'] ?? ($_COOKIE['language'] ?? getBestLanguage());
    
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $printSize = $_POST['print-size'] ?? '';
    $comment = $_POST['comment'] ?? '';
    
    if ($name && $phone) {
        sendTelegramNotification($name, $email, $phone, $printSize, $comment);
        $_SESSION['flash'] = [
            'message' => $MESSAGES[$language][200],
            'type' => 'success'
        ];
    } else {
        $_SESSION['flash'] = [
            'message' => $MESSAGES[$language][400],
            'type' => 'error'
        ];
    }
    
    // Перенаправление для предотвращения дублирования POST-запросов (Post/Redirect/Get pattern)
    header('Location: /');
    exit;
}

// Маршруты
try {
    if ($route === '/' || $route === '') {
        echo renderTemplate('landing', [
            'flash' => $_SESSION['flash'] ?? null,
            'urlFor' => 'urlFor'
        ]);
        // Очищаем flash-сообщение
        unset($_SESSION['flash']);
    } elseif ($route === '/about-us') {
        echo renderTemplate('about_us', [
            'urlFor' => 'urlFor'
        ]);
    } else {
        http_response_code(404);
        echo "404 - Page not found";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "500 - Internal Server Error";
    error_log($e->getMessage());
}
