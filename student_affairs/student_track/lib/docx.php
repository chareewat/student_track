<?php
/**
 * สร้างเอกสาร ขร. เป็น Word (.docx) ด้วย PhpWord ให้ครูแก้ไขต่อได้
 *
 * เนื้อหาเหมือนเทมเพลต PDF (views/pdf/*.php) แต่ใช้ฟอนต์ TH SarabunIT๙ 16pt ตามแบบฟอร์มต้นฉบับ
 * (ฟอนต์มาตรฐานหนังสือราชการที่มีในเครื่องของโรงเรียน ส่วน PDF ฝังฟอนต์ Sarabun ไว้ในไฟล์)
 */

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;

class TrkDocx
{
    const FONT = 'TH SarabunIT๙';
    const SIZE = 16;
    const SYMBOL_FONT = 'Segoe UI Symbol';

    /** @var PhpWord */
    public $word;
    /** @var \PhpOffice\PhpWord\Element\Section */
    public $section;

    public function __construct(string $title)
    {
        require_once TRK_ROOT . '/vendor/autoload.php';
        Settings::setTempDir(TRK_ROOT . '/storage/tmp');
        Settings::setOutputEscapingEnabled(true);

        $this->word = new PhpWord();
        $this->word->setDefaultFontName(self::FONT);
        $this->word->setDefaultFontSize(self::SIZE);
        $this->word->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0, 'spacing' => 0]);
        $this->word->getSettings()->setThemeFontLang(new Language(Language::EN_US, null, 'th-TH'));
        $this->word->getDocInfo()->setTitle($title)->setCreator('ระบบติดตามนักเรียน');
        $this->section = $this->word->addSection([
            'paperSize' => 'A4',
            'marginTop' => Converter::cmToTwip(1.5),
            'marginBottom' => Converter::cmToTwip(1.5),
            'marginLeft' => Converter::cmToTwip(3),
            'marginRight' => Converter::cmToTwip(2),
        ]);
    }

    /** ย่อหน้า: $runs = 'ข้อความ' หรือ [['ข้อความ', ['bold' => true]], 'ข้อความ', ...] */
    public function p($runs, array $para = [], $container = null)
    {
        $container = $container ?: $this->section;
        $tr = $container->addTextRun($para);
        foreach ((array) $runs as $run) {
            if (is_array($run)) {
                $tr->addText((string) $run[0], $run[1] ?? []);
            } else {
                $tr->addText((string) $run);
            }
        }
        return $tr;
    }

    /** ย่อหน้าเนื้อความ ย่อหน้าแรก 2.5 ซม. จัดแบบไทย */
    public function body($runs, float $indentCm = 2.5): void
    {
        $this->p($runs, ['alignment' => Jc::THAI_DISTRIBUTE, 'indentation' => ['firstLine' => Converter::cmToTwip($indentCm)]]);
    }

    public function blank(int $sizePt = 8): void
    {
        $this->section->addText('', ['size' => $sizePt]);
    }

    public function checkbox(bool $checked): array
    {
        return [$checked ? '☑' : '☐', ['name' => self::SYMBOL_FONT, 'size' => 13]];
    }

    public static function fill(?string $value, int $dots = 20): string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : str_repeat('.', $dots);
    }

    public function formCode(string $code): void
    {
        $this->p([[$code, ['size' => 14]]], ['alignment' => Jc::END]);
    }

    public function voidBanner(array $doc): void
    {
        if ($doc['status'] === 'cancelled') {
            $this->p([['ยกเลิกแล้ว: ' . $doc['cancel_reason'], ['bold' => true, 'color' => 'C00000', 'size' => 18]]], ['alignment' => Jc::CENTER]);
        }
    }

    /** หัวบันทึกข้อความ (ขร.1, ขร.3, ขร.6) */
    public function memoHeader(string $unit, ?string $docNo, array $date, array $subjectRuns): void
    {
        $t = $this->section->addTable(['cellMargin' => 0]);
        $t->addRow();
        $t->addCell(Converter::cmToTwip(3.2), ['valign' => 'center'])
            ->addImage(TRK_ROOT . '/assets/img/garuda.jpg', ['height' => Converter::cmToPoint(1.5), 'width' => Converter::cmToPoint(1.5)]);
        $t->addCell(Converter::cmToTwip(9.6), ['valign' => 'center'])
            ->addText('บันทึกข้อความ', ['bold' => true, 'size' => 29], ['alignment' => Jc::CENTER]);
        $t->addCell(Converter::cmToTwip(3.2));

        $label = ['bold' => true, 'size' => 20];
        $this->p([['ส่วนราชการ ', $label], $unit]);
        $tab = ['tabs' => [new \PhpOffice\PhpWord\Style\Tab('left', Converter::cmToTwip(8))]];
        $this->p([['ที่ ', $label], self::fill($docNo, 22), "\t", ['วันที่ ', $label],
            $date['day'] . ' เดือน ' . $date['month'] . ' พ.ศ. ' . $date['year']], $tab);
        $this->p(array_merge([['เรื่อง ', $label]], $subjectRuns),
            ['borderBottomSize' => 6, 'borderBottomColor' => '000000', 'spaceAfter' => 120]);
        $this->blank();
    }

    /** หัวหนังสือภายนอก (ขร.2, บค.14, ขร.5) */
    public function letterHead(string $prefix, ?string $docNo, array $lines, array $date): void
    {
        $this->section->addImage(TRK_ROOT . '/assets/img/garuda.jpg',
            ['height' => Converter::cmToPoint(2.3), 'width' => Converter::cmToPoint(2.3), 'alignment' => Jc::CENTER]);
        $t = $this->section->addTable(['cellMargin' => 0]);
        $t->addRow();
        $t->addCell(Converter::cmToTwip(9))->addText('ที่ ' . $prefix . self::fill($docNo, 12));
        $right = $t->addCell(Converter::cmToTwip(7));
        foreach ($lines as $line) {
            $right->addText($line);
        }
        $this->p('วันที่ ' . $date['day'] . ' เดือน ' . $date['month'] . ' พ.ศ. ' . $date['year'],
            ['indentation' => ['left' => Converter::cmToTwip(8)], 'spaceBefore' => 120]);
        $this->blank();
    }

    /**
     * ช่องลงชื่อ: $blocks = [['ลงชื่อ ....', '(ชื่อ)', 'ตำแหน่ง'], ...] วางเป็นคอลัมน์
     * $offsetCm = เว้นทางซ้ายก่อนคอลัมน์ (เช่น ลายเซ็นผู้บริหารชิดขวา)
     */
    public function signatures(array $blocks, float $offsetCm = 0, int $size = self::SIZE): void
    {
        $usable = 16.0 - $offsetCm;
        $t = $this->section->addTable(['cellMargin' => Converter::cmToTwip(0.1)]);
        $t->addRow(null, ['cantSplit' => true]);
        if ($offsetCm > 0) {
            $t->addCell(Converter::cmToTwip($offsetCm));
        }
        foreach ($blocks as $lines) {
            $cell = $t->addCell(Converter::cmToTwip($usable / count($blocks)));
            $cell->addText('', ['size' => 10]);
            foreach ($lines as $line) {
                $cell->addText($line, ['size' => $size], ['alignment' => Jc::CENTER]);
            }
        }
    }

    /** ช่องลงชื่อ 1 คน ($dots สั้นลงเมื่อวางหลายคอลัมน์) */
    public function sign(string $name, string $position, int $dots = 36): array
    {
        return ['ลงชื่อ ' . str_repeat('.', $dots), '(' . self::fill($name, $dots - 4) . ')', $position];
    }

    /** เส้นจุดสำหรับเขียนความเห็น */
    public function dottedLines(int $n): void
    {
        // ใช้แถวตารางที่มีเส้นขอบล่างแบบจุด (ยาวเต็มบรรทัดพอดีไม่ว่าเครื่องจะมีฟอนต์อะไร)
        // ไม่ใช้เส้นขอบย่อหน้า เพราะ Word รวมย่อหน้าที่มีเส้นขอบเหมือนกันเป็นกรอบเดียว
        $t = $this->section->addTable(['cellMargin' => 0]);
        for ($i = 0; $i < $n; $i++) {
            $t->addRow(Converter::cmToTwip(0.85), ['exactHeight' => true]);
            $t->addCell(Converter::cmToTwip(16), ['borderBottomSize' => 4, 'borderBottomColor' => '000000', 'borderBottomStyle' => 'dotted'])
                ->addText('');
        }
    }

    /** ตารางมีเส้นขอบ: $rows[0] เป็นหัวตาราง */
    public function grid(array $rows, array $widthsCm, int $size = 15): void
    {
        $t = $this->section->addTable(['borderSize' => 4, 'borderColor' => '000000', 'cellMargin' => Converter::cmToTwip(0.08)]);
        foreach ($rows as $i => $row) {
            $t->addRow(null, ['cantSplit' => true, 'tblHeader' => $i === 0]);
            foreach ($row as $j => $text) {
                $t->addCell(Converter::cmToTwip($widthsCm[$j]), ['valign' => 'center'])
                    ->addText((string) $text, ['size' => $size, 'bold' => $i === 0], ['alignment' => Jc::CENTER]);
            }
        }
    }

    public function pageBreak(): void
    {
        $this->section->addPageBreak();
    }

    /** ส่งไฟล์ให้ดาวน์โหลด */
    public function download(string $filename): void
    {
        $tmp = tempnam(TRK_ROOT . '/storage/tmp', 'docx');
        IOFactory::createWriter($this->word, 'Word2007')->save($tmp);
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header("Content-Disposition: attachment; filename=\"document.docx\"; filename*=UTF-8''" . rawurlencode($filename));
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: private, no-store');
        readfile($tmp);
        @unlink($tmp);
    }
}

// ---------------------------------------------------------------- เอกสารแต่ละชนิด

/** @param array $ctx ['school', 'amphur', 'province'] */
function trk_docx_build(array $doc, array $ctx): TrkDocx
{
    $builders = [
        'KR1' => 'trk_docx_kr1', 'KR2' => 'trk_docx_kr2', 'BK14' => 'trk_docx_bk14',
        'KR3' => 'trk_docx_kr3', 'KR5' => 'trk_docx_kr5', 'KR6' => 'trk_docx_kr6',
    ];
    $w = new TrkDocx(trk_document_title($doc));
    $w->voidBanner($doc);
    $builders[$doc['doc_type']]($w, $doc, $ctx);
    return $w;
}

function trk_docx_supported(string $type): bool
{
    return in_array($type, ['KR1', 'KR2', 'BK14', 'KR3', 'KR5', 'KR6'], true);
}

function trk_docx_kr1(TrkDocx $w, array $doc, array $ctx): void
{
    $d = $doc['data'];
    $th = $d['thresholds'];
    $total = (int) $d['subject']['total_hours'];
    $class = $d['classroom']['level'] . '/' . $d['classroom']['room'];

    $w->formCode('แบบ ขร.๑');
    $subject = ['นักเรียนขาดเรียนรายวิชา '];
    foreach ($th as $n => $pct) {
        $subject[] = $w->checkbox((int) $d['round_no'] === (int) $n);
        $subject[] = ' ครั้งที่ ' . $n . ' ขาด ' . (int) $pct . '%  ';
    }
    $w->memoHeader($ctx['school'] . ' อำเภอ' . $ctx['amphur'] . ' จังหวัด' . $ctx['province'], $doc['doc_no'],
        trk_thai_date_parts($d['issued_date']), $subject);
    $w->p([['เรียน ', ['bold' => true]], 'ผู้อำนวยการ' . $ctx['school']]);
    $w->blank();
    $w->body('ด้วยข้าพเจ้า ' . $d['teacher']['name'] . ' ตำแหน่ง ' . TrkDocx::fill($d['teacher']['position'], 10)
        . ' กลุ่มสาระการเรียนรู้' . TrkDocx::fill($d['subject']['group'], 20)
        . ' ได้รับมอบหมายภาระงานสอน ภาคเรียนที่ ' . $d['semester']['term'] . '/' . $d['semester']['year']
        . ' รายวิชา ' . $d['subject']['code'] . ' ' . $d['subject']['name'] . ' จำนวน ' . trk_format_credit($d['subject']['credit'])
        . ' หน่วยกิต มีเวลาเรียนทั้งหมด ' . $total . ' ชั่วโมง/ภาคเรียน ซึ่งนักเรียนสามารถขาดและลาได้ไม่เกิน '
        . (int) floor($total * 20 / 100) . ' ชั่วโมง/ภาคเรียน เพื่อให้มีเวลาเรียนคิดเป็นร้อยละ 80 ของเวลาเรียนทั้งหมด'
        . ' ตามแนวปฏิบัติการวัดและประเมินผลการเรียนรู้ ตามหลักสูตรแกนกลางการศึกษาขั้นพื้นฐาน พุทธศักราช 2551');
    $w->body('ในการนี้ ข้าพเจ้าได้ทำการสอนนักเรียนชั้นมัธยมศึกษาปีที่ ' . $class . ' ตั้งแต่วันที่ ' . trk_thai_date($d['from_date'])
        . ' ถึงวันที่ ' . trk_thai_date($d['to_date']) . ' พบว่า มีนักเรียนขาดและลาตั้งแต่ ' . (int) $d['threshold_hours']
        . ' ชั่วโมงขึ้นไป (ร้อยละ ' . (int) $th[$d['round_no']] . ' ของเวลาเรียนทั้งหมด) ซึ่งมีความเสี่ยง "หมดสิทธิ์สอบ" ในรายวิชาดังกล่าว ดังนี้');
    foreach ($d['students'] as $i => $s) {
        $w->p(($i + 1) . '. ' . $s['name'] . '   เลขประจำตัว ' . $s['code'] . '   ขาดเรียนสะสมมาแล้ว ' . (int) $s['absent_hours'] . ' ชั่วโมง',
            ['indentation' => ['left' => Converter::cmToTwip(1.25)]]);
    }
    $w->body('วันที่ที่นักเรียนขาดเรียน ระบุตามเอกสารดังแนบ');
    $w->body('จึงเรียนเพื่อโปรดทราบและช่วยเหลือติดตามนักเรียน');
    $advisors = [];
    foreach ($d['advisors'] ?: [''] as $a) {
        $advisors = array_merge($advisors, $w->sign($a, 'ครูที่ปรึกษาชั้น ม.' . $class), ['']);
    }
    $w->signatures([$w->sign($d['teacher']['name'], 'ครูประจำวิชา'), $advisors]);
    $w->p([['ความคิดเห็นของรองผู้อำนวยการกลุ่มบริหารวิชาการ', ['bold' => true]]]);
    $w->dottedLines(2);
    $w->signatures([$w->sign($d['signatories']['deputy_academic'], 'รองผู้อำนวยการกลุ่มบริหารวิชาการ')], 7);
    $w->p([['ความคิดเห็นของผู้อำนวยการ' . $ctx['school'], ['bold' => true]]]);
    $w->dottedLines(2);
    $w->signatures([$w->sign($d['signatories']['director'], 'ผู้อำนวยการ' . $ctx['school'])], 7);
    $w->p('"เรียนดี มีคุณธรรม"', ['alignment' => Jc::CENTER]);

    $w->pageBreak();
    $w->p([['ตารางเปรียบเทียบหน่วยกิตและเวลาขาดเรียน', ['bold' => true]]], ['alignment' => Jc::CENTER]);
    $rows = [array_merge(['หน่วยกิต', 'จำนวนชั่วโมงทั้งหมด'], array_map(function ($n) use ($th) {
        return 'ครั้งที่ ' . $n . ' (ขาด ' . (int) $th[$n] . '%)';
    }, array_keys($th)))];
    foreach ([0.5, 1.0, 1.5, 2.0] as $credit) {
        $hours = (int) round($credit * $d['hours_per_credit']);
        $row = [trk_format_credit($credit), $hours . ' ชั่วโมง'];
        foreach ($th as $pct) {
            $row[] = trk_case1_threshold_hours($hours, (int) $pct) . ' ชั่วโมง';
        }
        $rows[] = $row;
    }
    $w->grid($rows, [2.6, 3.8, 3.2, 3.2, 3.2]);
    $w->blank();
    $w->p([['หมายเหตุ: ', ['bold' => true, 'size' => 15]], ['หากขาดเกิน 20% ซึ่งจะตรงกับการรายงานครั้งที่ 2 นักเรียนจะหมดสิทธิ์สอบในรายวิชาดังกล่าว ในกรณีที่ขาดเกิน '
        . (int) $th[3] . '% ซึ่งจะตรงกับการรายงานครั้งที่ 3 ให้อยู่ในดุลยพินิจของท่านผู้อำนวยการ', ['size' => 15]]],
        ['alignment' => Jc::THAI_DISTRIBUTE]);

    $w->pageBreak();
    $w->formCode('เอกสารแนบ แบบ ขร.๑');
    $w->p([['วันที่นักเรียนขาดเรียน', ['bold' => true, 'size' => 18]]], ['alignment' => Jc::CENTER]);
    $w->p('รายวิชา ' . $d['subject']['code'] . ' ' . $d['subject']['name'] . ' ชั้นมัธยมศึกษาปีที่ ' . $class
        . ' ภาคเรียนที่ ' . $d['semester']['term'] . '/' . $d['semester']['year'], ['alignment' => Jc::CENTER]);
    $w->p('ข้อมูล ณ วันที่ ' . trk_thai_date($d['issued_date']), ['alignment' => Jc::CENTER]);
    foreach ($d['students'] as $i => $s) {
        $w->blank();
        $w->p([[($i + 1) . '. ' . $s['name'], ['bold' => true]], ' เลขประจำตัว ' . $s['code'] . ' ขาดเรียนสะสม ' . (int) $s['absent_hours'] . ' ชั่วโมง']);
        $rows = [['ที่', 'วันที่', 'คาบที่', 'สถานะ']];
        foreach ($s['details'] as $j => $r) {
            $rows[] = [$j + 1, trk_thai_date($r['date']), (int) $r['period'], TRK_SUBJECT_STATUS_LABELS[(int) $r['status']] ?? ''];
        }
        $w->grid($rows, [1.6, 7.2, 3.2, 3.8]);
    }
}

/** ส่วนที่ 2 ให้ผู้ปกครองส่งคืน (ขร.2, บค.14, ขร.5) */
function trk_docx_parent_slip(TrkDocx $w, array $d, string $fullNo): void
{
    $s = $d['student'];
    $w->blank();
    $w->p([['-------------------- ผู้ปกครองส่งส่วนที่ 2 คืนโรงเรียน --------------------', ['size' => 14]]],
        ['alignment' => Jc::CENTER]);
    $w->body('ข้าพเจ้า ' . TrkDocx::fill($d['guardian'], 45) . ' เป็นผู้ปกครองของ ' . $s['name'] . ' ชั้นมัธยมศึกษาปีที่ ' . $s['level']
        . ' ห้อง ' . $s['room'] . ' เลขประจำตัว ' . $s['code'] . ' ได้รับทราบรายละเอียดตามหนังสือที่ ' . $fullNo
        . ' เข้าใจดีแล้ว และจะดำเนินการว่ากล่าว ตักเตือนนักเรียนให้มาเรียนตามปกติ');
    $w->signatures([['ลงชื่อ ....................................ผู้ปกครอง', '(....................................................)']], 7);
}

function trk_docx_letter_lines(array $d, array $ctx): array
{
    return [$ctx['school'], 'อำเภอ' . $ctx['amphur'], 'จังหวัด' . $ctx['province'] . ' ' . ($d['postcode'] ?? '')];
}

function trk_docx_kr2(TrkDocx $w, array $doc, array $ctx): void
{
    $d = $doc['data'];
    $s = $d['student'];
    $w->formCode('แบบ ขร.๒');
    $w->letterHead($d['doc_no_prefix'], $doc['doc_no'], trk_docx_letter_lines($d, $ctx), trk_thai_date_parts($d['issued_date']));
    $w->p([['เรื่อง ', ['bold' => true]], 'แจ้งพฤติกรรมการเรียนของนักเรียน']);
    $w->blank();
    $w->p([['เรียน ', ['bold' => true]], 'ผู้ปกครอง ' . TrkDocx::fill($d['guardian'], 40)]);
    $w->blank();
    $w->body('เนื่องด้วย ' . $s['name'] . ' นักเรียนชั้น ม.' . $s['level'] . '/' . $s['room'] . ' เลขประจำตัว ' . $s['code']
        . ' ซึ่งเป็นนักเรียนในความปกครองของท่าน มีพฤติกรรมการขาดเรียนที่คาดว่าจะทำให้เกิดปัญหาทางการเรียนตามรายวิชา ดังนี้');
    foreach ($d['subjects'] as $i => $subj) {
        $w->p([$i === 0 ? $w->checkbox(true) : '    ', ($i === 0 ? ' ขาดเรียน ' : '              ')
            . 'รหัสวิชา ' . $subj['code'] . ' รายวิชา ' . $subj['name'] . ' จำนวน ' . (int) $subj['absent_hours'] . ' คาบ'],
            ['indentation' => ['left' => Converter::cmToTwip(1.25)]]);
    }
    $w->body('ซึ่งหากนักเรียนมีเวลาเรียนไม่ถึงร้อยละ 80 ของเวลาเรียนทั้งหมด ตามแนวปฏิบัติการวัดและประเมินผลการเรียนรู้'
        . ' ตามหลักสูตรแกนกลางการศึกษาขั้นพื้นฐาน พุทธศักราช 2551 จะทำให้นักเรียนหมดสิทธิ์ในการเข้าสอบปลายภาครายวิชาดังกล่าว'
        . ' ส่งผลให้ไม่จบการศึกษาตามหลักสูตร');
    $w->p([$w->checkbox(false), ' ขาดส่งงานที่ได้รับมอบหมาย ' . str_repeat('.', 70)], ['indentation' => ['left' => Converter::cmToTwip(1.25)]]);
    $w->p([$w->checkbox(false), ' พฤติกรรมอื่น ๆ ' . str_repeat('.', 85)], ['indentation' => ['left' => Converter::cmToTwip(1.25)]]);
    $w->body('จึงเรียนมาเพื่อทราบ');
    $w->signatures([['ขอแสดงความนับถือ', '', '', '(' . TrkDocx::fill($d['signatories']['director'], 35) . ')', 'ผู้อำนวยการ' . $ctx['school']]], 7);
    trk_docx_parent_slip($w, $d, $d['doc_no_prefix'] . ($doc['doc_no'] ?? ''));
    $w->p([['หมายเหตุ: ', ['bold' => true, 'size' => 14]], ['ในกรณีที่ครูที่ปรึกษาได้รับรายงานจากครูประจำวิชาผ่านแบบฟอร์ม ขร.1 ครูที่ปรึกษาจะต้องทำแบบฟอร์ม ขร.2'
        . ' ส่งให้ผู้ปกครองทราบภายในสัปดาห์ที่ 8 สำหรับเอกสาร ขร.2 ในส่วนที่ 2 ให้ครูที่ปรึกษาเป็นผู้เก็บรวบรวมเอกสารเพื่อเป็นหลักฐาน', ['size' => 14]]],
        ['alignment' => Jc::THAI_DISTRIBUTE]);
}

function trk_docx_bk14(TrkDocx $w, array $doc, array $ctx): void
{
    $d = $doc['data'];
    $s = $d['student'];
    $abs = $d['month_absence'];
    $prefix = $d['doc_no_prefix'] . 'ว';
    $w->formCode('แบบ บค.๑๔');
    $w->p([['แบบหนังสือเตือนให้ผู้ปกครองส่งนักเรียนเข้าเรียน', ['bold' => true]]], ['alignment' => Jc::CENTER]);
    $w->letterHead($prefix, $doc['doc_no'], trk_docx_letter_lines($d, $ctx), trk_thai_date_parts($d['issued_date']));
    $w->p([['เรื่อง ', ['bold' => true]], 'นักเรียนขาดเรียนต่อเนื่อง']);
    $w->blank();
    $w->p([['เรียน ', ['bold' => true]], 'ผู้ปกครอง ' . TrkDocx::fill($d['guardian'], 40)]);
    $w->blank();
    $w->body('ด้วย ' . $s['name'] . ' นักเรียนชั้น ม.' . $s['level'] . '/' . $s['room'] . ' เลขประจำตัว ' . $s['code']
        . ' ซึ่งเป็นนักเรียนในความปกครองของท่าน หยุดเรียนมาแล้วในเดือนนี้ รวม ' . (int) $abs['days'] . ' วัน');
    $w->p([$w->checkbox((int) $abs['max_consecutive'] >= (int) ($d['consecutive_days'] ?? 5)), ' เกิน 5 วันติดต่อกัน            ',
        $w->checkbox((int) $abs['days'] > 7), ' เกินกว่า 7 วัน ในรอบ 1 เดือน'], ['indentation' => ['left' => Converter::cmToTwip(2.5)]]);
    $w->body('โดยไม่ได้รับอนุญาตและไม่แจ้งสาเหตุให้โรงเรียนทราบ ซึ่งก่อให้เกิดผลเสียต่อการเรียนของนักเรียนเป็นอย่างยิ่ง'
        . ' โรงเรียนจึงขอเตือนให้ท่านส่งนักเรียนเข้าเรียนตามปกติโดยด่วน หากฝ่าฝืนโดยปราศจากเหตุผลอันสมควร'
        . ' จะมีความผิดตามมาตรา 15 แห่งพระราชบัญญัติการศึกษาภาคบังคับ พ.ศ. 2545 ต้องระวางโทษปรับไม่เกิน 10,000 บาท (หนึ่งหมื่นบาทถ้วน)');
    $w->body('จึงเรียนมาเพื่อทราบและดำเนินการต่อไป');
    $w->signatures([['ขอแสดงความนับถือ', '', '', '(' . TrkDocx::fill($d['signatories']['director'], 35) . ')', 'ผู้อำนวยการ' . $ctx['school']]], 7);
    trk_docx_parent_slip($w, $d, $prefix . ($doc['doc_no'] ?? ''));
    $w->blank();
    $w->p([['หลักฐานการติดตามนักเรียนขาดเรียน ครั้งที่ ' . (int) $d['round_no'], ['bold' => true]]], ['alignment' => Jc::CENTER]);
    $w->p('วันที่ .......... เดือน ................................ พ.ศ. ..............', ['alignment' => Jc::CENTER]);
    $w->signatures([
        $w->sign('', 'ครูที่ปรึกษา', 24),
        $w->sign('', 'ผู้ปกครอง', 24),
        ['ลงชื่อ ' . str_repeat('.', 24), '(' . $s['name'] . ')', 'นักเรียน'],
    ], 0, 14);
}

function trk_docx_kr3(TrkDocx $w, array $doc, array $ctx): void
{
    $d = $doc['data'];
    $s = $d['student'];
    $f = $d['followup'];
    $isCase1 = (int) $d['case_type'] === 1;
    $days = (int) ($d['consecutive_days'] ?? 5);

    $w->formCode('แบบ ขร.๓');
    $subject = [$isCase1
        ? 'นักเรียนขาดเรียนรายวิชา ' . $d['subject']['code'] . ' ' . $d['subject']['name'] . ' ('
        : 'นักเรียนขาดเรียนนานติดต่อกัน ' . $days . ' วันทำการ ('];
    foreach ([1, 2, 3] as $n) {
        $subject[] = $w->checkbox((int) $d['round_no'] === $n);
        $subject[] = ' ครั้งที่ ' . $n . ($n < 3 ? ' ' : ')');
    }
    $w->memoHeader('กลุ่มบริหารวิชาการ ' . $ctx['school'], $doc['doc_no'], trk_thai_date_parts($d['issued_date']), $subject);
    $w->p([['เรียน ', ['bold' => true]], 'ผู้อำนวยการ' . $ctx['school']]);
    $w->blank();
    $intro = 'ด้วย ' . $s['name'] . ' เลขประจำตัว ' . $s['code'] . ' นักเรียนระดับชั้น ม.' . $s['level'] . ' ห้อง ' . $s['room'];
    if ($isCase1) {
        $intro .= ' ได้ขาดเรียนรายวิชา ' . $d['subject']['code'] . ' ' . $d['subject']['name'] . ' สะสม ' . (int) $d['absent_hours']
            . ' ชั่วโมง จากเวลาเรียนทั้งหมด ' . (int) $d['subject']['total_hours'] . ' ชั่วโมง ตามที่ครูประจำวิชารายงานด้วยแบบ ขร.1 ครั้งที่ ' . (int) $d['round_no'];
    } else {
        $intro .= ' ได้ขาดเรียนติดต่อกัน ' . $days . ' วันทำการ ตั้งแต่วันที่ ' . trk_thai_date($d['absent_from']) . ' ถึงวันที่ ' . trk_thai_date($d['absent_to']);
    }
    $w->body($intro);
    $w->body('คุณครูที่ปรึกษาได้ดำเนินการติดตามนักเรียน (' . $f['method'] . ') เมื่อวันที่ ' . trk_thai_date($f['date'])
        . ' ร่วมกับ ' . $f['visitors'] . ' พบว่า ' . str_replace(["\r\n", "\n"], ' ', $f['findings']));
    $w->body('จึงเรียนมาเพื่อโปรดพิจารณา');
    $w->signatures(array_map(function ($a) use ($w, $d) {
        return $w->sign($a, 'ครูที่ปรึกษา', count($d['advisors']) > 2 ? 24 : 36);
    }, array_slice($d['advisors'] ?: [''], 0, 3)));
    $w->p([['ความเห็นรองผู้อำนวยการกลุ่มบริหารวิชาการ', ['bold' => true]]]);
    $w->dottedLines(2);
    $w->signatures([['(' . TrkDocx::fill($d['signatories']['deputy_academic'], 35) . ')', 'รองผู้อำนวยการกลุ่มบริหารวิชาการ']], 7);
    $w->p([['ความเห็นผู้อำนวยการโรงเรียน', ['bold' => true]]]);
    $w->dottedLines(2);
    $w->signatures([['(' . TrkDocx::fill($d['signatories']['director'], 35) . ')', 'ผู้อำนวยการ' . $ctx['school']]], 7);
    $w->p('"เรียนดี มีคุณธรรม"', ['alignment' => Jc::CENTER]);

    $w->pageBreak();
    $w->formCode('แบบ ขร.๓ (ต่อ)');
    $w->p([['หลักฐานการติดตามนักเรียนขาดเรียน ครั้งที่ ' . (int) $d['round_no'], ['bold' => true, 'size' => 18]]], ['alignment' => Jc::CENTER]);
    $w->p($s['name'] . ' เลขประจำตัว ' . $s['code'] . ' ชั้น ม.' . $s['level'] . '/' . $s['room'], ['alignment' => Jc::CENTER]);
    $w->p('วันที่ติดตาม ' . trk_thai_date($f['date']), ['alignment' => Jc::CENTER]);
    $w->blank();
    $photos = array_values(array_filter($f['photos'], function ($p) {
        return is_file(TRK_ROOT . '/storage/uploads/' . $p);
    }));
    if ($photos) {
        $t = $w->section->addTable(['cellMargin' => Converter::cmToTwip(0.1)]);
        foreach (array_chunk($photos, 2) as $pair) {
            $t->addRow();
            foreach ($pair as $p) {
                $t->addCell(Converter::cmToTwip(8))->addImage(TRK_ROOT . '/storage/uploads/' . $p,
                    ['width' => Converter::cmToPoint(7.6), 'height' => Converter::cmToPoint(5.7), 'alignment' => Jc::CENTER]);
            }
        }
    }
    $w->signatures([
        $w->sign($d['advisors'][0] ?? '', 'ครูที่ปรึกษา', 24),
        $w->sign('', 'ผู้ปกครอง', 24),
        ['ลงชื่อ ' . str_repeat('.', 24), '(' . $s['name'] . ')', 'นักเรียน'],
    ], 0, 15);
    $w->blank();
    $w->p([['หมายเหตุ: ', ['bold' => true, 'size' => 14]], ['การรายงานตามแบบฟอร์ม ขร.3 ให้ครูที่ปรึกษาดำเนินการรายงานครั้งที่ 1–3'
        . ' และใช้เป็นหลักฐานในกรณีติดตามนักเรียนไม่มีตัวตน หรือขาดเรียนนาน และใช้เป็นหลักฐานในการลาออก', ['size' => 14]]],
        ['alignment' => Jc::THAI_DISTRIBUTE]);
}

function trk_docx_kr5(TrkDocx $w, array $doc, array $ctx): void
{
    $d = $doc['data'];
    $s = $d['student'];
    $abs = $d['absence'];
    $w->formCode('แบบ ขร.๕');
    $w->letterHead($d['doc_no_prefix'], $doc['doc_no'], trk_docx_letter_lines($d, $ctx), trk_thai_date_parts($d['issued_date']));
    $w->p([['เรื่อง ', ['bold' => true]], 'นักเรียนขาดเรียน']);
    $w->blank();
    $w->p([['เรียน ', ['bold' => true]], 'ผู้ปกครองของ ' . $s['name'] . ' เลขประจำตัว ' . $s['code']]);
    $w->blank();
    $w->body('เนื่องด้วยนักเรียนในความปกครองของท่าน ชั้น ม.' . $s['level'] . '/' . $s['room'] . ' ไม่มาโรงเรียน ' . (int) $abs['days']
        . ' วันติดต่อกัน ตั้งแต่วันที่ ' . trk_thai_date($abs['from']) . ' ถึงปัจจุบัน จึงขอความกรุณาท่านได้ติดต่อ '
        . TrkDocx::fill(implode(' และ ', array_filter($d['advisors'])), 40) . ' ซึ่งเป็นครูที่ปรึกษาของนักเรียน ได้ที่กลุ่มบริหารวิชาการ โทรศัพท์ '
        . TrkDocx::fill($d['academic_phone'], 20) . ' ในวันและเวลาราชการโดยด่วน');
    $w->body('หากท่านไม่ติดต่อทางโรงเรียนภายในวันที่ ' . trk_thai_date($d['deadline'])
        . ' นักเรียนในความปกครองของท่านจะไม่มีสิทธิ์สอบและไม่จบหลักสูตรการศึกษา โรงเรียนจำเป็นต้องดำเนินการตามระเบียบของโรงเรียนต่อไป');
    $w->body('จึงเรียนมาเพื่อทราบ');
    $w->signatures([['ขอแสดงความนับถือ', '', '', '(' . TrkDocx::fill($d['signatories']['director'], 35) . ')', 'ผู้อำนวยการ' . $ctx['school']]], 7);
    trk_docx_parent_slip($w, $d, $d['doc_no_prefix'] . ($doc['doc_no'] ?? ''));
}

function trk_docx_kr6(TrkDocx $w, array $doc, array $ctx): void
{
    $d = $doc['data'];
    $s = $d['student'];
    $occ = array_values($d['occasions']);
    $w->formCode('แบบ ขร.๖');
    $w->memoHeader('กลุ่มบริหารวิชาการ ' . $ctx['school'], $doc['doc_no'], trk_thai_date_parts($d['issued_date']),
        ['ขอให้จำหน่ายนักเรียนที่ไม่มาโรงเรียนติดต่อกันเป็นเวลานาน']);
    $w->p([['เรียน ', ['bold' => true]], 'ผู้อำนวยการ' . $ctx['school']]);
    $w->blank();
    $w->body('ด้วยข้าพเจ้า ' . $d['advisor']['name'] . ' เป็นครูที่ปรึกษา ม.' . $s['level'] . '/' . $s['room'] . ' ได้ติดตามการมาเรียนของ '
        . $s['name'] . ' เลขประจำตัว ' . $s['code'] . ' ซึ่งไม่มาโรงเรียน ดังนี้');
    foreach ([0, 1, 2] as $i) {
        $o = $occ[$i] ?? null;
        if ($o === null) {
            $text = ' ครั้งที่ ' . ($i + 1) . ' ตั้งแต่วันที่ ................ ถึงวันที่ ................ เป็นเวลารวม ........ วัน';
        } elseif ((int) $o['case_type'] === 2) {
            $text = ' ครั้งที่ ' . ($i + 1) . ' ตั้งแต่วันที่ ' . trk_thai_date($o['from']) . ' ถึงวันที่ ' . trk_thai_date($o['to']) . ' เป็นเวลารวม ' . (int) $o['days'] . ' วัน';
        } else {
            $text = ' ครั้งที่ ' . ($i + 1) . ' ขาดเรียนรายวิชา ' . $o['subject'] . ' สะสม ' . (int) $o['absent_hours'] . ' ชั่วโมง (รายงานตาม ขร.1 ครั้งที่ ' . (int) $o['round_no'] . ')';
        }
        $w->p([$w->checkbox($o !== null), $text], ['indentation' => ['left' => Converter::cmToTwip(1.25)]]);
    }
    $w->body('โดยข้าพเจ้าได้ส่งหนังสือ/ติดตามจำนวน ' . count($occ) . ' ครั้ง ดังนี้');
    foreach ([0, 1, 2] as $i) {
        $o = $occ[$i] ?? null;
        $w->p('ครั้งที่ ' . ($i + 1) . ' เมื่อวันที่ ' . ($o ? trk_thai_date($o['followup_date']) . ' (' . $o['method'] . ')' : '..............................'),
            ['indentation' => ['left' => Converter::cmToTwip(2.5)]]);
    }
    $w->body('รวมการส่งหนังสือ/ติดตาม ' . count($occ) . ' ครั้งแล้ว ผู้ปกครองยังไม่มาติดต่อและไม่ได้นำนักเรียนมาเข้าเรียน'
        . ' (หลังการติดตามครั้งล่าสุดเมื่อวันที่ ' . trk_thai_date($d['last_followup']) . ' นักเรียนไม่มาโรงเรียน ' . (int) $d['after']['checked'] . ' วันทำการ)'
        . ' จึงขอให้โรงเรียนพิจารณาดำเนินการจำหน่าย ' . $s['name'] . ' เลขประจำตัว ' . $s['code'] . ' ออกจากโรงเรียน');
    $w->body('จึงเรียนมาเพื่อโปรดพิจารณา');
    $w->signatures([$w->sign($d['advisor']['name'], 'ครูที่ปรึกษา')], 7);
    $w->p([['ความเห็นรองผู้อำนวยการกลุ่มบริหารวิชาการ', ['bold' => true]]]);
    $w->dottedLines(2);
    $w->signatures([['(' . TrkDocx::fill($d['signatories']['deputy_academic'], 35) . ')', 'รองผู้อำนวยการกลุ่มบริหารวิชาการ']], 7);
    $w->p([['ความเห็นผู้อำนวยการโรงเรียน', ['bold' => true]]]);
    $w->dottedLines(2);
    $w->signatures([['(' . TrkDocx::fill($d['signatories']['director'], 35) . ')', 'ผู้อำนวยการ' . $ctx['school']]], 7);
    $w->p('"เรียนดี มีคุณธรรม"', ['alignment' => Jc::CENTER]);
}
