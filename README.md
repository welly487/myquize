# myquize

多人使用的線上測驗／題庫管理系統（PHP + PostgreSQL），支援建立試卷、CSV 匯入題庫、模擬考試，並使用 **Google 帳號登入**。

## 架構

- **後端**：PHP 8.3
- **資料庫**：PostgreSQL（建議用 [Supabase](https://supabase.com) 免費方案，或 Render 自己的 Postgres）
- **登入**：Google OAuth 2.0（不再使用帳號密碼登入）
- **部署**：Docker on Render

---

## 1. 建立資料庫

### 方案 A：用 Supabase（推薦，已內建管理介面）

1. 到 [supabase.com](https://supabase.com) 建立一個新專案。
2. 進 **SQL Editor**，貼上並執行 `schema.sql` 的內容。
3. 進 **Project Settings → Database**，複製 Connection string（URI 格式），會長得像：
   ```
   postgres://postgres:[YOUR-PASSWORD]@db.xxxxxxxx.supabase.co:5432/postgres
   ```
4. 把這串填進等一下要設定的 `DATABASE_URL` 環境變數（記得補上 `?sslmode=require`）。

### 方案 B：用 Render 的 PostgreSQL

1. Render Dashboard → New → PostgreSQL，建立一個免費資料庫。
2. 建立好之後，Render 會給你一組 **Internal Database URL**，一樣填進 `DATABASE_URL`。
3. 用 `psql` 或任何 GUI 工具連上去，執行 `schema.sql`。

---

## 2. 設定 Google 登入

1. 到 [Google Cloud Console](https://console.cloud.google.com/) 建立一個專案（或用現有的）。
2. 左側選單 **API 和服務 → OAuth 同意畫面**，設定應用程式名稱、支援電子郵件即可（測試階段選「外部」＋「測試使用者」也可以）。
3. **API 和服務 → 憑證 → 建立憑證 → OAuth 用戶端 ID**，應用程式類型選「網頁應用程式」。
4. **已授權的重新導向 URI** 填：
   ```
   https://你的Render網址.onrender.com/auth_google_callback.php
   ```
   本機測試的話再另外加一筆 `http://localhost:8080/auth_google_callback.php`。
5. 建立後會拿到 **用戶端 ID** 與 **用戶端密鑰**，等一下填進環境變數。

---

## 3. 部署到 Render

1. 把這個 repo push 到你自己的 GitHub。
2. Render Dashboard → New → Web Service → 選擇這個 repo。
3. Runtime 選 **Docker**（會自動抓 repo 裡的 `Dockerfile`）。
4. 在 **Environment** 頁籤新增以下變數：

   | Key | 說明 |
   |---|---|
   | `DATABASE_URL` | 第 1 步拿到的連線字串 |
   | `GOOGLE_CLIENT_ID` | 第 2 步拿到的用戶端 ID |
   | `GOOGLE_CLIENT_SECRET` | 第 2 步拿到的用戶端密鑰 |
   | `GOOGLE_REDIRECT_URI` | `https://你的Render網址.onrender.com/auth_google_callback.php` |
   | `FORCE_HTTPS` | `true` |

5. 部署完成後，打開網址應該會直接看到「使用 Google 帳號登入」的頁面。

> `render.yaml` 已經幫你把上面這些變數骨架列出來，用 Render 的 Blueprint 功能匯入 repo 時會自動帶出來，只是機密值仍需要自己手動填。

---

## 本機開發

```bash
# 需要 PHP 8.1+ 與 pdo_pgsql 擴充
cp .env.example .env   # 只是筆記用，PHP 讀的是系統環境變數
export DB_HOST=127.0.0.1 DB_PORT=5432 DB_NAME=quizdb DB_USER=postgres DB_PASS=yourpass DB_SSLMODE=disable
export GOOGLE_CLIENT_ID=... GOOGLE_CLIENT_SECRET=... GOOGLE_REDIRECT_URI=http://localhost:8080/auth_google_callback.php
export FORCE_HTTPS=false

php -S localhost:8080
```

或者直接用 Docker 跑：

```bash
docker build -t myquize .
docker run -p 8080:80 --env-file .env myquize
```

---

## 這次改版做了哪些安全性強化

- ❌ 移除帳號密碼登入，改用 Google OAuth，不再有密碼被暴力破解的風險。
- ✅ 所有會改變資料的操作（新增／修改／刪除試卷與題目、CSV 匯入、作答送出）都加上 **CSRF token** 驗證。
- ✅ 刪除試卷原本是用 GET 連結（容易被 CSRF 誘導點擊），改成 POST + CSRF。
- ✅ Session Cookie 加上 `HttpOnly`、`Secure`、`SameSite=Lax`，並在登入成功後 `session_regenerate_id()` 防止 session fixation。
- ✅ 修補「模擬考試」原本沒有檢查試卷歸屬的漏洞（原本登入後可用別人的 quiz_id 偷看/作答其他人的試卷）。
- ✅ CSV 匯入加上副檔名檢查與上傳錯誤檢查。
- ✅ 資料庫帳密／Google 用戶端密鑰全部改用環境變數，不再寫死在程式碼裡。

## 已知限制 / 之後可以再做的事

- 目前沒有針對 Google 登入額外做「僅允許特定 Email/網域」的白名單機制，任何人只要有 Google 帳號都能登入並建立自己的資料。如果只想讓自己或特定人使用，可以在 `auth_google_callback.php` 建立新使用者前加上 email 白名單檢查。
- 免費方案的 Render 服務閒置一段時間會自動休眠，第一次訪問會有幾秒鐘的冷啟動時間，屬正常現象。
