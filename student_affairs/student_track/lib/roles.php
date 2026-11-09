<?php
/**
 * Role ที่ admin กำหนด (ดู CONTEXT.md)
 * ครูประจำวิชา / ครูที่ปรึกษา ไม่ใช่ role แต่เป็นหน้าที่ที่อนุมานจากข้อมูล (lib/duties.php)
 */

const TRK_ROLES = [
    'admin' => 'Admin',
    'teacher' => 'ครู',
    'executive' => 'ผู้บริหาร',
    'academic' => 'เจ้าหน้าที่วิชาการ',
    'affairs' => 'เจ้าหน้าที่กิจการนักเรียน',
];

/** role ที่เห็นข้อมูลทุกห้อง */
const TRK_ROLES_SEE_ALL = ['admin', 'executive', 'academic', 'affairs'];

/** admin_type_id ของระบบเดิม → role ที่แนะนำ (ทุกคนได้ teacher) */
function trk_suggested_roles(int $adminTypeId): array
{
    $map = [99 => 'admin', 98 => 'executive', 1 => 'academic', 5 => 'affairs'];
    $roles = ['teacher'];
    if (isset($map[$adminTypeId])) {
        $roles[] = $map[$adminTypeId];
    }
    return $roles;
}

function trk_roles_configured(): bool
{
    return (int) trk_db_value("SELECT COUNT(*) FROM trk_user_roles WHERE role = 'admin'") > 0;
}

/** role ของบุคลากร ถ้ายังไม่เคยตั้งค่า role เลย ให้ Super Admin (admin_type 99) เป็น admin เพื่อเข้ามาตั้งค่าครั้งแรก */
function trk_roles_of(int $memberId): array
{
    if (!trk_roles_configured()) {
        $adminType = (int) trk_db_value('SELECT admin_type_id FROM psn_member WHERE psn_mem_id = ?', [$memberId]);
        return $adminType === 99 ? ['admin'] : [];
    }
    $rows = trk_db_all('SELECT role FROM trk_user_roles WHERE psn_mem_id = ?', [$memberId]);
    return array_column($rows, 'role');
}

/** ผู้ใช้ที่ login อยู่ */
function trk_user(): array
{
    static $user = null;
    if ($user === null) {
        $id = (int) $_SESSION['psn_mem_id'];
        $row = trk_db_one(
            'SELECT m.psn_mem_id, m.psn_mem_name, m.psn_mem_surname, t.psn_title_name
             FROM psn_member m LEFT JOIN psn_title t ON t.psn_title_id = m.psn_title_id
             WHERE m.psn_mem_id = ?',
            [$id]
        );
        $user = [
            'id' => $id,
            'name' => $row ? trim($row['psn_title_name'] . $row['psn_mem_name'] . ' ' . $row['psn_mem_surname']) : '',
            'roles' => trk_roles_of($id),
        ];
    }
    return $user;
}

function trk_has_role(string ...$roles): bool
{
    return (bool) array_intersect($roles, trk_user()['roles']);
}

function trk_sees_all_rooms(): bool
{
    return trk_has_role(...TRK_ROLES_SEE_ALL);
}

/** หยุดและแสดงหน้า 403 ถ้าไม่มี role ที่ต้องการ */
function trk_require_role(string ...$roles): void
{
    if (!trk_has_role(...$roles)) {
        http_response_code(403);
        trk_render_message('ไม่มีสิทธิ์เข้าถึงหน้านี้', 'หน้านี้สำหรับ ' . implode(', ', array_map(function ($r) {
            return TRK_ROLES[$r];
        }, $roles)) . ' เท่านั้น');
        exit;
    }
}

/**
 * บันทึก role ของบุคลากรหลายคนพร้อมกัน
 * $assignments = [psn_mem_id => [role, ...]] (เฉพาะคนที่ส่งมาเท่านั้นที่ถูกแทนที่)
 * ต้องเหลือ admin อย่างน้อย 1 คนเสมอ
 */
function trk_save_roles(array $assignments, int $byMemberId): void
{
    trk_db_transaction(function () use ($assignments, $byMemberId) {
        $now = date('Y-m-d H:i:s');
        foreach ($assignments as $memberId => $roles) {
            trk_db_exec('DELETE FROM trk_user_roles WHERE psn_mem_id = ?', [(int) $memberId]);
            foreach (array_unique($roles) as $role) {
                if (!isset(TRK_ROLES[$role])) {
                    continue;
                }
                trk_db_exec(
                    'INSERT INTO trk_user_roles (psn_mem_id, role, assigned_by, assigned_at) VALUES (?, ?, ?, ?)',
                    [(int) $memberId, $role, $byMemberId, $now]
                );
            }
        }
        if (!trk_roles_configured()) {
            throw new RuntimeException('ต้องมี Admin อย่างน้อย 1 คน');
        }
    });
}
