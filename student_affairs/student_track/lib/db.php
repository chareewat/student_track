<?php
/**
 * ตัวช่วยฐานข้อมูล: ทุก query ใช้ prepared statement
 * ใช้ ? เป็นตัวแทนค่า แล้วส่งค่าเป็น array ตามลำดับ
 */

function trk_db_init(mysqli $conn): void
{
    $GLOBALS['trk_db'] = $conn;
}

function trk_db(): mysqli
{
    return $GLOBALS['trk_db'];
}

function trk_db_stmt(string $sql, array $params): mysqli_stmt
{
    $stmt = trk_db()->prepare($sql);
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        $values = array_values($params);
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    return $stmt;
}

/**
 * สถิติ query ของ request นี้ (จำนวนและเวลารวม) ใช้ในหน้าตรวจสอบระบบ
 * เก็บรายการ query ที่ช้าที่สุดเฉพาะเมื่อ $GLOBALS['trk_db_profile'] = true
 */
function trk_db_record(string $sql, float $started, int $rows): void
{
    $ms = (microtime(true) - $started) * 1000;
    $stats = &$GLOBALS['trk_db_stats'];
    if (!is_array($stats)) {
        $stats = ['count' => 0, 'ms' => 0.0, 'slowest' => []];
    }
    $stats['count']++;
    $stats['ms'] += $ms;
    if (!empty($GLOBALS['trk_db_profile'])) {
        $key = preg_replace('/\s+/', ' ', trim($sql));
        $agg = $stats['slowest'][$key] ?? ['calls' => 0, 'ms' => 0.0, 'max_ms' => 0.0, 'rows' => 0];
        $agg['calls']++;
        $agg['ms'] += $ms;
        $agg['max_ms'] = max($agg['max_ms'], $ms);
        $agg['rows'] += $rows;
        $stats['slowest'][$key] = $agg;
    }
}

/** คืนทุกแถวเป็น array ของ associative array */
function trk_db_all(string $sql, array $params = []): array
{
    $started = microtime(true);
    $stmt = trk_db_stmt($sql, $params);
    if (method_exists($stmt, 'get_result')) {
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } else {
        $rows = trk_db_fetch_without_mysqlnd($stmt);
    }
    trk_db_record($sql, $started, count($rows));
    return $rows;
}

/** สำรองสำหรับ host ที่ไม่มี mysqlnd (ไม่มี get_result) */
function trk_db_fetch_without_mysqlnd(mysqli_stmt $stmt): array
{
    $meta = $stmt->result_metadata();
    if (!$meta) {
        return [];
    }
    $row = [];
    $refs = [];
    foreach ($meta->fetch_fields() as $field) {
        $row[$field->name] = null;
        $refs[] = &$row[$field->name];
    }
    $stmt->bind_result(...$refs);
    $rows = [];
    while ($stmt->fetch()) {
        $copy = [];
        foreach ($row as $k => $v) {
            $copy[$k] = $v;
        }
        $rows[] = $copy;
    }
    return $rows;
}

/** คืนแถวแรก หรือ null */
function trk_db_one(string $sql, array $params = []): ?array
{
    $rows = trk_db_all($sql, $params);
    return $rows ? $rows[0] : null;
}

/** คืนค่าคอลัมน์แรกของแถวแรก หรือ null */
function trk_db_value(string $sql, array $params = [])
{
    $row = trk_db_one($sql, $params);
    return $row ? reset($row) : null;
}

/** รัน INSERT/UPDATE/DELETE คืนจำนวนแถวที่เปลี่ยน */
function trk_db_exec(string $sql, array $params = []): int
{
    $started = microtime(true);
    $affected = trk_db_stmt($sql, $params)->affected_rows;
    trk_db_record($sql, $started, max(0, $affected));
    return $affected;
}

function trk_db_transaction(callable $fn)
{
    $db = trk_db();
    $db->begin_transaction();
    try {
        $result = $fn();
        $db->commit();
        return $result;
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}
