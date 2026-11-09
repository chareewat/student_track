  </main>
  <footer class="mx-auto max-w-6xl px-4 pb-8 pt-2 text-center text-xs text-ink/40">ระบบติดตามนักเรียน · <?= e($GLOBALS['school_name'] ?? '') ?></footer>
  <script src="<?= e(trk_url('assets/js/app.js')) ?>?v=<?= e((string) @filemtime(TRK_ROOT . '/assets/js/app.js')) ?>"></script>
</body>
</html>
