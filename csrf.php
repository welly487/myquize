<?php
/**
 * 簡單的 CSRF 防護：每個 session 一組 token，
 * 所有會改變資料的表單（POST）都要帶上這個 token 才放行。
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/**
 * 驗證失敗直接中止並回傳 403，不繼續往下執行原本的資料異動邏輯。
 */
function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if ($token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('請求驗證失敗，請重新整理頁面後再試一次。');
    }
}
