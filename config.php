<?php
/**
 * 統一設定檔：所有機密資訊一律從環境變數讀取，
 * 不在程式碼中寫死帳號密碼，方便部署到 Render。
 *
 * 用 function 包起來（而不是檔案結尾 return 陣列），
 * 是因為這支檔案會被好幾個地方用 require_once 載入，
 * 而 require_once 對同一支檔案第二次呼叫只會回傳 true、不會重新回傳陣列，
 * 包成 function 就不受這個限制影響，隨時呼叫都拿得到完整設定。
 */

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}

function app_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $config = [
        'db' => [
            // Render / Supabase 通常會直接給一組 DATABASE_URL
            'url'     => env('DATABASE_URL'),
            // 若沒有整包 URL，就用個別欄位組合（例如本機開發用）
            'host'    => env('DB_HOST', '127.0.0.1'),
            'port'    => env('DB_PORT', '5432'),
            'name'    => env('DB_NAME', 'quizdb'),
            'user'    => env('DB_USER', 'postgres'),
            'pass'    => env('DB_PASS', ''),
            'sslmode' => env('DB_SSLMODE', 'require'), // Supabase/Render 都建議走 SSL
        ],
        'google' => [
            'client_id'     => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            // 例如 https://your-app.onrender.com/auth_google_callback.php
            'redirect_uri'  => env('GOOGLE_REDIRECT_URI'),
        ],
        'app' => [
            // 正式環境設成 true，PHP 才知道 cookie 要標記 Secure
            'force_https' => filter_var(env('FORCE_HTTPS', 'true'), FILTER_VALIDATE_BOOLEAN),
        ],
    ];

    return $config;
}

