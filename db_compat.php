<?php
/**
 * 輕量相容層：讓原本用 mysqli 風格寫的程式
 * ($conn->prepare / $stmt->bind_param / ->get_result() / ->fetch_assoc() ...)
 * 可以在底層改用 PDO + PostgreSQL 執行，不用整份重寫每支檔案的商業邏輯。
 *
 * 使用範圍刻意只做這個專案真的有用到的幾個方法，不是完整的 mysqli 模擬。
 */

class CompatResult
{
    private array $rows;
    private int $pointer = 0;
    public int $num_rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc(): ?array
    {
        if (!isset($this->rows[$this->pointer])) {
            return null;
        }
        return $this->rows[$this->pointer++];
    }

    public function fetch_all(): array
    {
        return $this->rows;
    }
}

class CompatStatement
{
    private PDOStatement $stmt;
    private string $sql;
    private array $types = [];
    /** @var array<int, mixed> 用參照儲存，讓 bind_param 之後改變變數值也會反映到 execute() */
    private array $paramRefs = [];
    private $insertId = null;

    public function __construct(PDOStatement $stmt, string $sql)
    {
        $this->stmt = $stmt;
        $this->sql = $sql;
    }

    /**
     * 模擬 mysqli_stmt::bind_param('iss', $a, $b, $c)
     */
    public function bind_param(string $types, &...$vars): bool
    {
        $this->types = str_split($types);
        $this->paramRefs = [];
        foreach ($vars as $i => &$v) {
            $this->paramRefs[$i] = &$v;
        }
        return true;
    }

    public function execute(): bool
    {
        foreach ($this->paramRefs as $i => &$val) {
            $type = $this->types[$i] ?? 's';
            $pdoType = match ($type) {
                'i' => PDO::PARAM_INT,
                'b' => PDO::PARAM_LOB,
                default => PDO::PARAM_STR, // 's' 和 'd' 都用字串綁定即可
            };
            $this->stmt->bindValue($i + 1, $val, $pdoType);
        }

        $ok = $this->stmt->execute();

        // 若 SQL 有加上 RETURNING id，執行成功後把 id 取出，模擬 mysqli 的 insert_id
        if ($ok && stripos($this->sql, 'RETURNING') !== false) {
            $row = $this->stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['id'])) {
                $this->insertId = $row['id'];
            }
        }

        return $ok;
    }

    public function get_result(): CompatResult
    {
        return new CompatResult($this->stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function close(): bool
    {
        $this->stmt->closeCursor();
        return true;
    }

    public function __get($name)
    {
        if ($name === 'insert_id') {
            return $this->insertId ?? 0;
        }
        return null;
    }
}

class CompatConnection
{
    public PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function prepare(string $sql): CompatStatement
    {
        // MySQL 的 RAND() 在 PostgreSQL 是 RANDOM()，這裡順手轉一下避免漏改
        $sql = preg_replace('/\bRAND\(\)/i', 'RANDOM()', $sql);
        return new CompatStatement($this->pdo->prepare($sql), $sql);
    }

    public function set_charset(string $charset): bool
    {
        // PostgreSQL 連線已經在 database.php 指定 UTF8，這裡只是保留介面相容
        return true;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}
