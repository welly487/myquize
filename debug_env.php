<?php
/**
 * 暫時性除錯頁面：確認環境變數有沒有真的傳進容器。
 * 密碼會被遮蔽，只顯示有沒有讀到、格式對不對。
 * 檢查完記得把這支檔案刪掉，不要留在正式環境上。
 */

function mask_url(string $url): string
{
    // postgresql://user:password@host... -> postgresql://user:******@host...
    return preg_replace('#(://[^:]+:)[^@]+(@)#', '$1******$2', $url);
}

$vars = ['DATABASE_URL', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_SSLMODE',
         'GOOGLE_CLIENT_ID', 'GOOGLE_REDIRECT_URI', 'FORCE_HTTPS'];

header('Content-Type: text/plain; charset=utf-8');

foreach ($vars as $name) {
    $value = getenv($name);
    if ($value === false || $value === '') {
        echo "$name = (沒有讀到 / 空值)\n";
        continue;
    }
    if ($name === 'DATABASE_URL') {
        $value = mask_url($value);
    }
    echo "$name = $value\n";
}
