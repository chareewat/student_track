<?php
/**
 * รัน migration ที่ยังไม่เคยรัน ตามลำดับชื่อไฟล์
 *
 *   php student_affairs/student_track/migrations/migrate.php
 *
 * บน production ที่ไม่มี shell: เปิดไฟล์ .sql ทีละไฟล์ใน phpMyAdmin แล้วเพิ่มชื่อไฟล์ลง trk_migrations เอง
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require __DIR__ . '/../../../global_config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
$db->set_charset('utf8mb4');

$db->query("CREATE TABLE IF NOT EXISTS `trk_migrations` (
  `name` varchar(100) NOT NULL, `applied_at` datetime NOT NULL, PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$applied = [];
$res = $db->query('SELECT name FROM trk_migrations');
while ($row = $res->fetch_assoc()) {
    $applied[$row['name']] = true;
}

$files = glob(__DIR__ . '/*.sql');
sort($files);
$count = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (isset($applied[$name])) {
        continue;
    }
    echo "applying $name ... ";
    $db->multi_query(file_get_contents($file));
    do {
        if ($r = $db->store_result()) {
            $r->free();
        }
    } while ($db->more_results() && $db->next_result());

    $stmt = $db->prepare('INSERT INTO trk_migrations (name, applied_at) VALUES (?, NOW())');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    echo "ok\n";
    $count++;
}
echo $count ? "done ($count applied)\n" : "nothing to apply\n";
