  </main>
  <footer class="mx-auto max-w-6xl px-4 pb-8 pt-2 text-center text-xs text-ink/60">ระบบติดตามนักเรียน · <?= e($GLOBALS['school_name'] ?? '') ?></footer>
  <?php // แถบโหมดสาธิต: มีเฉพาะบนเครื่องพัฒนาที่มีโฟลเดอร์ demo/ (ไม่อยู่ในชุดไฟล์ขึ้น production)
  if (is_file(TRK_LEGACY_ROOT . '/demo/bar.php')) {
      include TRK_LEGACY_ROOT . '/demo/bar.php';
  } ?>
  <script src="<?= e(trk_url('assets/js/app.js')) ?>?v=<?= e((string) @filemtime(TRK_ROOT . '/assets/js/app.js')) ?>"></script>
</body>
</html>
