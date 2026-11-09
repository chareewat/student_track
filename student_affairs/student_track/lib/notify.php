<?php
/**
 * การแจ้งเตือนในระบบ (ไม่มี LINE/Push ในเวอร์ชันแรก)
 */

function trk_notify(int $recipientId, string $title, string $link, ?int $documentId = null): void
{
    trk_db_exec(
        'INSERT INTO trk_notifications (recipient_id, title, link, document_id, created_at) VALUES (?, ?, ?, ?, ?)',
        [$recipientId, mb_substr($title, 0, 255), $link, $documentId, date('Y-m-d H:i:s')]
    );
}

function trk_unread_count(int $memberId): int
{
    return (int) trk_db_value('SELECT COUNT(*) FROM trk_notifications WHERE recipient_id = ? AND read_at IS NULL', [$memberId]);
}

/** อ่านแล้วเมื่อผู้รับเปิดเอกสาร */
function trk_mark_document_read(int $memberId, int $documentId): void
{
    trk_db_exec(
        'UPDATE trk_notifications SET read_at = ? WHERE recipient_id = ? AND document_id = ? AND read_at IS NULL',
        [date('Y-m-d H:i:s'), $memberId, $documentId]
    );
}
