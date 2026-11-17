<?php
/**
 * วาด flowchart ด้วย HTML + Tailwind (ไม่ใช้รูปภาพหรือไลบรารีจาก CDN)
 * เรียงจากบนลงล่าง จึงแสดงผลได้ทุกขนาดจอ ข้อความภาษาไทยตัดบรรทัดเองตามความกว้าง
 *
 *   echo trk_flowchart([
 *       ['start', 'เริ่ม'],
 *       ['step', 'เข้าเมนู "นักเรียนขาดรายวิชา"', ['menu' => 'นักเรียนขาดรายวิชา']],
 *       ['decision', 'นักเรียน ม.1–3?', ['yes' => [...nodes], 'no' => [...nodes]]],
 *       ['end', 'จบ'],
 *   ]);
 *
 * ชนิดของกล่อง (ดูคำอธิบายสัญลักษณ์ใน trk_flowchart_legend())
 *   start / end  จุดเริ่ม / จุดจบ
 *   legacy       ทำในระบบเช็คชื่อเดิม
 *   step         ผู้ใช้ทำในระบบติดตามนักเรียน
 *   system       ระบบทำให้อัตโนมัติ
 *   manual       ทำนอกระบบ
 *   decision     ตรวจเงื่อนไข มีทางแยก yes/no (หรือ else = ข้อความสั้นเมื่อไม่ผ่าน)
 *   loop         หมายเหตุการทำซ้ำ (ใช้ท้าย flowchart)
 *
 * ตัวเลือกของกล่อง: menu (ชื่อเมนูที่ต้องกด), doc (เอกสารที่ได้), note (คำอธิบายเพิ่ม)
 */

const TRK_FLOW_STYLES = [
    'start'    => ['box' => 'rounded-full bg-navy text-white border-navy', 'icon' => 'play', 'tag' => ''],
    'end'      => ['box' => 'rounded-full bg-navy text-white border-navy', 'icon' => 'flag', 'tag' => ''],
    'legacy'   => ['box' => 'rounded-2xl bg-sand/40 border-sand', 'icon' => 'check-check', 'tag' => 'ระบบเช็คชื่อเดิม'],
    'step'     => ['box' => 'rounded-2xl bg-white border-navy/25', 'icon' => 'user', 'tag' => 'ทำในระบบ'],
    'system'   => ['box' => 'rounded-2xl bg-emerald-50 border-emerald-300', 'icon' => 'cpu', 'tag' => 'ระบบทำให้'],
    'manual'   => ['box' => 'rounded-2xl border-dashed bg-canvas border-ink/30', 'icon' => 'users', 'tag' => 'นอกระบบ'],
    'decision' => ['box' => 'rounded-2xl bg-amber-50 border-amber-400', 'icon' => 'split', 'tag' => 'ตรวจเงื่อนไข'],
    'loop'     => ['box' => 'rounded-2xl bg-accent-50 border-accent-100', 'icon' => 'repeat', 'tag' => 'ทำซ้ำ'],
];

function trk_flowchart(array $nodes): string
{
    return '<div class="flow mx-auto flex w-full max-w-md flex-col items-stretch">' . trk_flow_nodes($nodes) . '</div>';
}

function trk_flow_nodes(array $nodes): string
{
    $html = '';
    $prev = null;
    foreach (array_values($nodes) as $node) {
        if ($node[0] === 'loop') {
            // หมายเหตุการทำซ้ำ: ไม่ต่อเส้นจากจุดจบ
            $html .= '<div class="mt-4">' . trk_flow_node($node) . '</div>';
        } else {
            if ($prev !== null) {
                // หลังกล่องตรวจเงื่อนไขแบบสั้น ทางหลักคือ "ใช่"
                $html .= trk_flow_arrow($prev[0] === 'decision' && isset($prev[2]['else']) ? 'ใช่' : '');
            }
            $html .= trk_flow_node($node);
        }
        $prev = $node;
    }
    return $html;
}

/** เส้นเชื่อมพร้อมหัวลูกศรชี้ลง */
function trk_flow_arrow(string $label = ''): string
{
    $html = '<div class="flex flex-col items-center" aria-hidden="true">'
        . '<span class="block h-4 w-0.5 bg-navy/30"></span>';
    if ($label !== '') {
        $html .= '<span class="my-0.5 rounded-full bg-white px-2 text-[12px] font-semibold text-navy/70 ring-1 ring-navy/15">' . e($label) . '</span>'
            . '<span class="block h-2 w-0.5 bg-navy/30"></span>';
    }
    return $html . '<svg viewBox="0 0 12 8" class="h-2 w-3 text-navy/40"><path d="M0 0h12L6 8z" fill="currentColor"/></svg></div>';
}

function trk_flow_node(array $node): string
{
    $type = $node[0];
    $text = $node[1];
    $opt = $node[2] ?? [];
    $style = TRK_FLOW_STYLES[$type] ?? TRK_FLOW_STYLES['step'];

    if ($type === 'start' || $type === 'end') {
        return '<div class="flex justify-center"><div class="inline-flex max-w-full items-center gap-2 border px-4 py-2 text-sm font-semibold shadow-soft ' . $style['box'] . '">'
            . trk_icon($style['icon'], 'h-4 w-4 text-accent') . '<span>' . e($text) . '</span></div></div>';
    }

    $tagColors = [
        'legacy' => 'text-ink/60', 'step' => 'text-navy', 'system' => 'text-emerald-700',
        'manual' => 'text-ink/60', 'decision' => 'text-amber-800', 'loop' => 'text-accent-600',
    ];
    $box = '<div class="border-2 px-3.5 py-2.5 shadow-soft ' . $style['box'] . '">'
        . '<div class="flex items-center gap-1.5 text-[12px] font-semibold ' . ($tagColors[$type] ?? 'text-navy') . '">'
        . trk_icon($style['icon'], 'h-3.5 w-3.5') . e($style['tag'])
        . (!empty($opt['who']) ? '<span class="ml-auto font-medium text-ink/50">' . e($opt['who']) . '</span>' : '')
        . '</div>'
        . '<div class="mt-1 text-sm leading-relaxed text-ink">' . e($text) . '</div>';
    $chips = '';
    if (!empty($opt['menu'])) {
        $chips .= '<span class="chip-navy">' . trk_icon('chevron-right', 'h-3 w-3') . 'เมนู ' . e($opt['menu']) . '</span>';
    }
    foreach ((array) ($opt['doc'] ?? []) as $doc) {
        $chips .= '<span class="chip-purple">' . trk_icon('file-text', 'h-3 w-3') . e($doc) . '</span>';
    }
    if ($chips !== '') {
        $box .= '<div class="mt-2 flex flex-wrap gap-1.5">' . $chips . '</div>';
    }
    if (!empty($opt['note'])) {
        $box .= '<div class="mt-1.5 text-xs leading-relaxed text-ink/60">' . e($opt['note']) . '</div>';
    }
    $box .= '</div>';

    if ($type !== 'decision') {
        return $box;
    }

    // ทางแยกแบบสั้น: ไม่ผ่านเงื่อนไขแล้วจบ/รอ → แสดงเป็นป้ายข้างล่าง ทางหลักไปต่อ
    if (isset($opt['else'])) {
        return $box . '<div class="mt-1.5 flex items-start gap-1.5 pl-3 text-xs text-ink/60">'
            . '<span class="chip-sand shrink-0">ไม่ใช่</span><span class="pt-0.5">' . e($opt['else']) . '</span></div>';
    }

    // ทางแยกเต็ม: 2 คอลัมน์ แล้วรวมกลับเป็นเส้นเดียว
    $branch = function (array $nodes, string $label): string {
        return '<div class="flex min-w-0 flex-col">' . trk_flow_arrow($label) . trk_flow_nodes($nodes)
            . '<div class="flex flex-1 justify-center" aria-hidden="true"><span class="block min-h-4 w-0.5 bg-navy/30"></span></div></div>';
    };
    return $box
        . '<div class="flex justify-center" aria-hidden="true"><span class="block h-3 w-0.5 bg-navy/30"></span></div>'
        . '<div class="relative grid grid-cols-2 gap-2 sm:gap-3">'
        . '<span class="absolute left-1/4 right-1/4 top-0 h-0.5 bg-navy/30" aria-hidden="true"></span>'
        . $branch($opt['yes'] ?? [], $opt['yes_label'] ?? 'ใช่')
        . $branch($opt['no'] ?? [], $opt['no_label'] ?? 'ไม่ใช่')
        . '<span class="absolute bottom-0 left-1/4 right-1/4 h-0.5 bg-navy/30" aria-hidden="true"></span>'
        . '</div>';
}

/** คำอธิบายสัญลักษณ์ */
function trk_flowchart_legend(): string
{
    $items = [
        ['start', 'จุดเริ่ม / จุดจบ'],
        ['step', 'ผู้ใช้ทำในระบบติดตามนักเรียน'],
        ['system', 'ระบบทำให้อัตโนมัติ'],
        ['legacy', 'ทำในระบบเช็คชื่อเดิม'],
        ['manual', 'ทำนอกระบบ (เอกสาร/ลงพื้นที่)'],
        ['decision', 'ตรวจเงื่อนไข มีทางแยก'],
        ['loop', 'ทำซ้ำในครั้งถัดไป'],
    ];
    $html = '<ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">';
    foreach ($items as [$type, $label]) {
        $s = TRK_FLOW_STYLES[$type];
        $html .= '<li class="flex items-center gap-2.5 text-sm">'
            . '<span class="grid h-8 w-10 shrink-0 place-items-center border-2 ' . $s['box'] . '">'
            . trk_icon($s['icon'], 'h-3.5 w-3.5 ' . ($type === 'start' ? 'text-accent' : 'text-ink/60')) . '</span>'
            . '<span>' . e($label) . '</span></li>';
    }
    return $html . '</ul>';
}
