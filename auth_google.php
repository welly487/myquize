<?php
require_once __DIR__ . '/session_init.php';

$config = require __DIR__ . '/config.php';
$google = $config['google'];

if (empty($google['client_id']) || empty($google['redirect_uri'])) {
    die('伺服器尚未設定 Google 登入（缺少 GOOGLE_CLIENT_ID / GOOGLE_REDIRECT_URI 環境變數）。');
}

// state 用來防止 CSRF，callback 會驗證這個值有沒有對上
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = [
    'client_id'     => $google['client_id'],
    'redirect_uri'  => $google['redirect_uri'],
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
