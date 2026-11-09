<?php

function trk_audit(string $action, array $detail = []): void
{
    trk_db_exec(
        'INSERT INTO trk_audit_log (psn_mem_id, action, detail, ip, created_at) VALUES (?, ?, ?, ?, ?)',
        [
            trk_user()['id'],
            $action,
            json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            $_SERVER['REMOTE_ADDR'] ?? null,
            date('Y-m-d H:i:s'),
        ]
    );
}
