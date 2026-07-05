<?php
require_once __DIR__ . '/session_init.php';
require_once __DIR__ . '/database.php';

require_once __DIR__ . '/config.php';
$config = app_config();
$google = $config['google'];

// 1. 驗證 state，避免 CSRF / 被塞入偽造的授權碼
$state = $_GET['state'] ?? '';
if ($state === '' || empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
    unset($_SESSION['oauth_state']);
    die('登入驗證失敗，請重新從登入頁開始。');
}
unset($_SESSION['oauth_state']);

if (isset($_GET['error'])) {
    header('Location: login.php?error=' . urlencode('登入已取消'));
    exit;
}

$code = $_GET['code'] ?? '';
if ($code === '') {
    die('缺少授權碼');
}

// 2. 用授權碼換 access token
$tokenResponse = http_post_form('https://oauth2.googleapis.com/token', [
    'code'          => $code,
    'client_id'     => $google['client_id'],
    'client_secret' => $google['client_secret'],
    'redirect_uri'  => $google['redirect_uri'],
    'grant_type'    => 'authorization_code',
]);

$tokenData = json_decode($tokenResponse['body'] ?? '', true);
if ($tokenResponse['status'] !== 200 || empty($tokenData['access_token'])) {
    error_log('Google token exchange failed: ' . ($tokenResponse['body'] ?? 'no response'));
    die('登入失敗，無法向 Google 取得授權，請稍後再試。');
}

// 3. 用 access token 取得使用者基本資料
$userInfoResponse = http_get('https://www.googleapis.com/oauth2/v3/userinfo', $tokenData['access_token']);
$profile = json_decode($userInfoResponse['body'] ?? '', true);

if ($userInfoResponse['status'] !== 200 || empty($profile['sub'])) {
    error_log('Google userinfo failed: ' . ($userInfoResponse['body'] ?? 'no response'));
    die('登入失敗，無法取得 Google 帳號資訊，請稍後再試。');
}

$googleId    = $profile['sub'];
$email       = $profile['email'] ?? null;
$displayName = $profile['name'] ?? ($email ?? 'Google 使用者');

// 4. 找使用者：先用 google_id 找，找不到再用 email 找（讓舊帳號可以綁定 Google）
$stmt = $conn->prepare('SELECT id, display_name FROM users WHERE google_id = ?');
$stmt->bind_param('s', $googleId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user && $email) {
    $stmt = $conn->prepare('SELECT id, display_name FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if ($existing) {
        // 舊帳號綁定 Google
        $stmt = $conn->prepare('UPDATE users SET google_id = ? WHERE id = ?');
        $stmt->bind_param('si', $googleId, $existing['id']);
        $stmt->execute();
        $user = $existing;
    }
}

if (!$user) {
    $stmt = $conn->prepare('INSERT INTO users (google_id, email, display_name) VALUES (?, ?, ?) RETURNING id');
    $stmt->bind_param('sss', $googleId, $email, $displayName);
    $stmt->execute();
    $user = ['id' => $stmt->insert_id, 'display_name' => $displayName];
}

// 5. 登入成功：重新產生 session id，避免 session fixation
session_regenerate_id(true);
$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['display_name'] ?: $displayName;

header('Location: index.php');
exit;

/**
 * 簡易 POST 表單請求（不依賴額外套件，方便打包進 Docker）
 */
function http_post_form(string $url, array $fields): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $body];
}

function http_get(string $url, string $bearerToken): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $bearerToken],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $body];
}
