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

// จอเล็ก: เมนูด้านบนเลื่อนแนวนอนได้ ให้เลื่อนเมนูที่เลือกอยู่มาให้เห็นเสมอ
(function () {
  var active = document.querySelector('nav[aria-label="เมนูหลัก"] [aria-current="page"]');
  if (!active) return;
  var nav = active.parentElement;
  if (nav.scrollWidth > nav.clientWidth) {
    nav.scrollLeft = active.offsetLeft - (nav.clientWidth - active.offsetWidth) / 2;
  }
})();
