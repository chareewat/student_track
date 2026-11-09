<?php
/**
 * สร้างชุดไฟล์สำหรับขึ้น production ไว้ที่ dist/ (ระดับ root ของโปรเจกต์)
 *
 *   php student_affairs/student_track/tools/build-release.php
 *
 * ได้ 2 ไฟล์:
 *   dist/student_track-YYYYMMDD.zip  แตกไฟล์ที่ <root เว็บ>/student_affairs/ บน server
 *   dist/install-YYYYMMDD.sql        รันใน phpMyAdmin (รันซ้ำได้ ไม่เสียหาย)
 *
 * ก่อนรัน: npm run build:css และ composer install --no-dev ในโฟลเดอร์โมดูล
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

$module = realpath(__DIR__ . '/..');
$root = realpath($module . '/../..');
$dist = $root . '/dist';
if (!is_dir($dist)) {
    mkdir($dist, 0775, true);
}
$stamp = date('Ymd');

foreach (['vendor/autoload.php', 'assets/css/app.css', 'assets/fonts/Sarabun-Regular.ttf'] as $required) {
    if (!is_file($module . '/' . $required)) {
        fwrite(STDERR, "missing $required — run composer install / npm run build:css first\n");
        exit(1);
    }
}

// ---------------------------------------------------------------- zip
// ไม่รวม: ไฟล์ทดสอบ, เครื่องมือพัฒนา, ไฟล์ที่สร้างระหว่างใช้งาน
$exclude = [
    '#^tests/#',
    '#^tools/(?!\.htaccess$)#',
    '#^storage/(tmp|uploads)/(?!\.gitkeep$)#',
    '#^assets/css/input\.css$#',
    '#(^|/)error_log$#',
    '#(^|/)\.DS_Store$#',
];

$zipPath = "$dist/student_track-$stamp.zip";
@unlink($zipPath);
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "cannot create $zipPath\n");
    exit(1);
}
$count = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($module, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($module) + 1));
    foreach ($exclude as $pattern) {
        if (preg_match($pattern, $rel)) {
            continue 2;
        }
    }
    $zip->addFile($file->getPathname(), 'student_track/' . $rel);
    $count++;
}
// โฟลเดอร์ว่างที่ต้องมีบน server
foreach (['storage/tmp', 'storage/uploads'] as $dir) {
    $zip->addEmptyDir('student_track/' . $dir);
}
$zip->close();
printf("%s  (%d files, %.1f MB)\n", $zipPath, $count, filesize($zipPath) / 1048576);

// ---------------------------------------------------------------- install.sql
$files = glob($module . '/migrations/*.sql');
sort($files);
$sql = "-- ระบบติดตามนักเรียน: สร้าง/อัปเดตตาราง (รันซ้ำได้)\n"
    . "-- สร้างเมื่อ " . date('Y-m-d H:i') . " จาก migrations/\n"
    . "-- ไฟล์ 002 เพิ่ม index บนตารางเช็คชื่อเดิม ใช้เวลาสักครู่ ควรรันหลังเลิกเรียน\n\n"
    . "SET NAMES utf8mb4;\n\n";
foreach ($files as $f) {
    $name = basename($f);
    $sql .= "-- ============================================================ $name\n"
        . trim(file_get_contents($f)) . "\n\n";
}
$sql .= "-- ============================================================ บันทึกว่ารันแล้ว\n"
    . "INSERT IGNORE INTO `trk_migrations` (`name`, `applied_at`) VALUES\n"
    . implode(",\n", array_map(function ($f) {
        return "  ('" . basename($f) . "', NOW())";
    }, $files)) . ";\n";
$sqlPath = "$dist/install-$stamp.sql";
file_put_contents($sqlPath, $sql);
echo "$sqlPath\n";
