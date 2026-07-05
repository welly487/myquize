-- myquize PostgreSQL schema
-- 部署到 Supabase / Render Postgres 時，直接在 SQL Editor 或 psql 執行這份檔案即可

CREATE TABLE IF NOT EXISTS users (
    id            SERIAL PRIMARY KEY,
    google_id     VARCHAR(64) UNIQUE,          -- Google 帳號的 sub id
    email         VARCHAR(255),
    display_name  VARCHAR(255),
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS quizzes (
    id            SERIAL PRIMARY KEY,
    user_id       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title         VARCHAR(255) NOT NULL,
    description   TEXT,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS questions (
    id            SERIAL PRIMARY KEY,
    user_id       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    question      TEXT NOT NULL,
    option_a      TEXT NOT NULL,
    option_b      TEXT NOT NULL,
    option_c      TEXT NOT NULL,
    option_d      TEXT NOT NULL,
    answer        CHAR(1) NOT NULL CHECK (answer IN ('A','B','C','D'))
);

-- 保留自己的 id（而不是用複合主鍵），因為原本程式會用 ORDER BY qq.id 排序題目顯示順序
CREATE TABLE IF NOT EXISTS quiz_questions (
    id            SERIAL PRIMARY KEY,
    quiz_id       INTEGER NOT NULL REFERENCES quizzes(id) ON DELETE CASCADE,
    question_id   INTEGER NOT NULL REFERENCES questions(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_quizzes_user_id ON quizzes(user_id);
CREATE INDEX IF NOT EXISTS idx_questions_user_id ON questions(user_id);
CREATE INDEX IF NOT EXISTS idx_quiz_questions_quiz_id ON quiz_questions(quiz_id);
CREATE INDEX IF NOT EXISTS idx_quiz_questions_question_id ON quiz_questions(question_id);

-- ============================================================
-- 安全性：啟用 Row Level Security
-- ============================================================
-- 這個 App 是用 PHP + PDO 直接連線資料庫（用資料表擁有者的帳號），
-- 不是透過 Supabase 的 anon/authenticated API key 存取，
-- 所以這裡的 RLS 完全不加 policy 也沒關係——擁有者連線不受 RLS 限制，
-- 但可以把 Supabase 自動曝露出去的 REST API 這條路徹底鎖死，
-- 避免有人拿到 anon key 就能直接讀寫資料表。
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE quizzes ENABLE ROW LEVEL SECURITY;
ALTER TABLE questions ENABLE ROW LEVEL SECURITY;
ALTER TABLE quiz_questions ENABLE ROW LEVEL SECURITY;

-- 保險起見，額外把預設可能已經存在的 API 角色權限收回
REVOKE ALL ON users, quizzes, questions, quiz_questions FROM anon, authenticated;
