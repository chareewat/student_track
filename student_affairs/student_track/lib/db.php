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

/** คืนทุกแถวเป็น array ของ associative array */
function trk_db_all(string $sql, array $params = []): array
{
    $stmt = trk_db_stmt($sql, $params);
    if (method_exists($stmt, 'get_result')) {
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    return trk_db_fetch_without_mysqlnd($stmt);
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
    return trk_db_stmt($sql, $params)->affected_rows;
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
