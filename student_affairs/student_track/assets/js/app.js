// ตัวกรองตาราง: <input data-filter-target="#tableId"> กรองแถว <tr data-filter-text="...">
document.addEventListener('input', function (event) {
  var input = event.target;
  if (!input.matches('[data-filter-target]')) return;
  var table = document.querySelector(input.getAttribute('data-filter-target'));
  if (!table) return;
  var q = input.value.trim().toLowerCase();
  table.querySelectorAll('tr[data-filter-text]').forEach(function (row) {
    row.hidden = q !== '' && row.getAttribute('data-filter-text').toLowerCase().indexOf(q) === -1;
  });
});

// ทำเครื่องหมายว่าแถวถูกแก้ไขแล้ว เพื่อให้ผู้ใช้รู้ว่ายังไม่ได้บันทึก
document.addEventListener('change', function (event) {
  var row = event.target.closest('tr[data-dirty-track]');
  if (row) row.classList.add('bg-amber-50');
});

// เมนู hamburger บนจอเล็ก: <button data-nav-toggle="#main-nav"> เปิด/ปิดเมนูหลัก
(function () {
  var button = document.querySelector('[data-nav-toggle]');
  if (!button) return;
  var nav = document.querySelector(button.getAttribute('data-nav-toggle'));
  if (!nav) return;
  function setOpen(open) {
    nav.classList.toggle('hidden', !open);
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
    button.querySelector('[data-nav-icon="open"]').hidden = open;
    button.querySelector('[data-nav-icon="close"]').hidden = !open;
  }
  button.addEventListener('click', function () {
    setOpen(button.getAttribute('aria-expanded') !== 'true');
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      button.focus();
    }
  });
})();

// ส่งฟอร์ม POST: ปุ่มที่กดแสดงวงหมุน + ปิดชั่วคราว กันกดซ้ำระหว่างรอ (เช่น สร้างเอกสาร)
document.addEventListener('submit', function (event) {
  var form = event.target;
  if (event.defaultPrevented || (form.getAttribute('method') || '').toLowerCase() !== 'post') return;
  var button = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
  if (!button) return;
  if (button.getAttribute('aria-busy') === 'true') {
    event.preventDefault();
    return;
  }
  // รอให้เบราว์เซอร์เก็บค่าของปุ่มลงฟอร์มก่อน แล้วค่อยปิดปุ่ม
  setTimeout(function () {
    button.setAttribute('aria-busy', 'true');
    button.disabled = true;
    button.insertAdjacentHTML('afterbegin', '<span class="spinner" aria-hidden="true" data-busy-spinner></span>');
  }, 0);
});

// กลับมาหน้านี้ด้วยปุ่มย้อนกลับ: คืนปุ่มให้กดได้
window.addEventListener('pageshow', function () {
  document.querySelectorAll('button[aria-busy="true"]').forEach(function (button) {
    button.removeAttribute('aria-busy');
    button.disabled = false;
    var spinner = button.querySelector('[data-busy-spinner]');
    if (spinner) spinner.remove();
  });
});
