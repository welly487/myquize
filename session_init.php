<?php
/**
 * 所有頁面都應該 require 這支檔案，而不是直接呼叫 session_start()。
 * 這裡統一設定 Cookie 的 HttpOnly / Secure / SameSite，降低 Session 被竊取或
 * 被跨站請求偽造（CSRF）利用的風險。
 */

if (session_status() === PHP_SESSION_NONE) {
    $config = require __DIR__ . '/config.php';

    $isHttps = $config['app']['force_https']
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'); // Render 在 proxy 後面要看這個

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

require_once __DIR__ . '/csrf.php';
