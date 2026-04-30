# ⚡ SteamAuthZero

**Самая быстрая, легкая и современная библиотека для авторизации через Steam на PHP.**

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-777bb4.svg?style=flat-square)](https://www.php.net/releases/8.1/en.php)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![Zero Dependencies](https://img.shields.io/badge/dependencies-zero-success?style=flat-square)]()

**SteamAuthZero** — это современная замена устаревшим библиотекам (вроде `LightOpenID`). Она написана на нативном PHP 8.1+, использует строгую типизацию и не тянет за собой Guzzle, cURL или другие тяжелые зависимости.

## 🚀 Особенности

* Никаких лишних файлов в `vendor`. Работает на голом PHP.
* Использует нативные PHP стримы (`stream_context`) вместо тяжелых cURL-оберток.
* Вместо непонятных массивов `$user['personaname']` вы получаете удобный объект `SteamUser`.
* Правильная валидация подписи OpenID 2.0.
* Простая интеграция за 3 минуты.

---

## 📦 Установка

Скачайте папку SteamAuthZero и подключите к проекту:  
`require_once 'path/to/SteamAuthZero/SteamAuthZero.php';`

---

## 🛠 Использование

1. Инициализация
```php
use Wakanda\SteamAuthZero\SteamAuthZero;

$apiKey = 'YOUR_STEAM_API_KEY'; // Получить тут: https://steamcommunity.com/dev/apikey
$returnUrl = 'https://mysite.com/login.php'; // Ссылка на этот же скрипт

$auth = new SteamAuthZero($returnUrl, $apiKey);
```

2. Полный пример входа
```php
try {
    // Если Steam перенаправил пользователя обратно (есть параметры openid_mode)
    if (isset($_GET['openid_mode'])) {
        
        // 1. Валидация подписи (без запросов к API профиля, только проверка входа)
        $steamId = $auth->validate();

        if ($steamId) {
            // 2. Получение данных профиля (Аватар, Никнейм, SteamId)
            // Возвращает объект SteamUser
            $user = $auth->getUserInfo($steamId);

            echo "<h1>Welcome, {$user->personaName}!</h1>";
            echo "<img src='{$user->avatarUrl}' style='border-radius: 50%'>";
            echo "<p>SteamID: {$user->steamId}</p>";

            // Сохраняем в сессию
            // $_SESSION['user'] = $user;
        } else {
            echo "Ошибка валидации! Попробуйте снова.";
        }

    } else {
        // Если пользователь просто зашел на страницу — показываем кнопку
        $loginUrl = $auth->getLoginUrl();
        echo '<a href="' . $loginUrl . '"><img src="https://community.cloudflare.steamstatic.com/public/images/signinthroughsteam/sits_01.png"></a>';
    }

} catch (Exception $e) {
    // Библиотека выбрасывает RuntimeException при ошибках сети или API
    die("Ошибка: " . $e->getMessage());
}
```

---

## 🧩 Объект пользователя (DTO)

Метод `getUserInfo()` возвращает строго типизированный объект `teamUser`. Вам больше не нужно гадать, какие ключи есть в массиве.

```php
class SteamUser
{
    public string $steamId;     // 76561198...
    public string $personaName; // Wakanda
    public string $avatarUrl;   // https://avatars.../full.jpg
    public string $profileUrl;  // https://steamcommunity.com/id/...
}
```

---

## ❓ Частые проблемы (FAQ)

Ошибка: `Failed to connect to Steam` на локальном сервере  
Это происходит из-за отсутствия SSL сертификатов.  
Библиотека автоматически отключает проверку SSL `(verify_peer => false)`, если запрос выполняется локально, но убедитесь, что ваш файрвол не блокирует исходящие соединения.

---

## 📄 Лицензия

MIT License. Делайте с кодом что хотите, это Open Source.
