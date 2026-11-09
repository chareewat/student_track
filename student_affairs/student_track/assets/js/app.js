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
