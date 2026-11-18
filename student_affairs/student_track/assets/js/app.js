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

// ลิงก์ "ยกเลิก" (#cancel): เปิดกล่องยกเลิกในหน้าเอกสารให้ทันทีและโฟกัสช่องเหตุผล
(function () {
  if (location.hash !== '#cancel') return;
  var box = document.getElementById('cancel');
  if (!box || box.tagName !== 'DETAILS') return;
  box.open = true;
  var reason = box.querySelector('input[name="reason"]');
  if (reason) reason.focus({ preventScroll: true });
  box.scrollIntoView({ block: 'center' });
})();

// ครอปรูปก่อนอัปโหลด: <input type="file" data-photo-crop="<id ของกล่องแสดงตัวครอป>" data-max="2" data-ratios="4:3,3:4,16:9,9:16">
// แต่ละรูปเลือกอัตราส่วน ลากเพื่อจัดตำแหน่ง เลื่อนแถบเพื่อซูม ตอนส่งฟอร์มจะแทนไฟล์เดิมด้วยรูปที่ครอปแล้ว (JPG ด้านยาว 1600px)
// เบราว์เซอร์ที่ไม่รองรับจะส่งรูปเดิม (server ย่อขนาดให้อยู่แล้ว)
(function () {
  var input = document.querySelector('input[type="file"][data-photo-crop]');
  if (!input || !window.DataTransfer || !HTMLCanvasElement.prototype.toBlob || !window.URL) return;
  var box = document.getElementById(input.getAttribute('data-photo-crop'));
  var form = input.form;
  if (!box || !form) return;
  var max = parseInt(input.getAttribute('data-max'), 10) || 2;
  var ratios = (input.getAttribute('data-ratios') || '4:3').split(',');
  var editors = [];
  var ready = false;

  function ratioValue(r) {
    var p = r.split(':');
    return parseFloat(p[0]) / parseFloat(p[1]);
  }

  function makeEditor(file, index) {
    var ed = { file: file, ratio: ratios[0], zoom: 1, x: 0, y: 0, img: new Image() };
    var card = document.createElement('div');
    card.className = 'rounded-2xl border border-sand/70 bg-canvas/60 p-3';
    var head = document.createElement('div');
    head.className = 'flex flex-wrap items-center gap-2';
    head.innerHTML = '<span class="text-sm font-medium">รูปที่ ' + (index + 1) + '</span><span class="text-xs text-ink/60">อัตราส่วน</span>';
    var buttons = [];
    ratios.forEach(function (r) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn btn-sm btn-white';
      b.textContent = r;
      b.setAttribute('aria-pressed', 'false');
      b.addEventListener('click', function () { ed.ratio = r; ed.zoom = 1; ed.x = 0; ed.y = 0; zoom.value = '1'; mark(); layout(); });
      buttons.push(b);
      head.appendChild(b);
    });
    var stage = document.createElement('div');
    stage.className = 'mt-3 flex justify-center';
    var canvas = document.createElement('canvas');
    canvas.className = 'rounded-xl bg-white shadow-soft touch-none cursor-move';
    canvas.setAttribute('aria-label', 'ลากเพื่อจัดตำแหน่งรูปที่ ' + (index + 1));
    stage.appendChild(canvas);
    var zoomRow = document.createElement('label');
    zoomRow.className = 'mt-3 flex items-center gap-3 text-sm';
    zoomRow.innerHTML = '<span class="text-ink/60">ซูม</span>';
    var zoom = document.createElement('input');
    zoom.type = 'range'; zoom.min = '1'; zoom.max = '3'; zoom.step = '0.01'; zoom.value = '1';
    zoom.className = 'w-full';
    zoom.addEventListener('input', function () { ed.zoom = parseFloat(zoom.value); clamp(); draw(); });
    zoomRow.appendChild(zoom);
    card.appendChild(head); card.appendChild(stage); card.appendChild(zoomRow);
    box.appendChild(card);

    function mark() {
      buttons.forEach(function (b) {
        var on = b.textContent === ed.ratio;
        b.className = 'btn btn-sm ' + (on ? 'btn-navy' : 'btn-white');
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
    }
    // ขนาดกรอบบนจอ: กว้างไม่เกิน 480px และไม่เกินความกว้างกล่อง, สูงไม่เกิน 420px
    function layout() {
      var r = ratioValue(ed.ratio);
      var w = Math.min(480, Math.max(200, box.clientWidth - 24));
      var h = w / r;
      if (h > 420) { h = 420; w = h * r; }
      canvas.width = Math.round(w); canvas.height = Math.round(h);
      canvas.style.width = canvas.width + 'px'; canvas.style.height = canvas.height + 'px';
      clamp(); draw();
    }
    // สเกลขั้นต่ำให้รูปเต็มกรอบ (cover) × ซูม
    function scale() {
      return Math.max(canvas.width / ed.img.naturalWidth, canvas.height / ed.img.naturalHeight) * ed.zoom;
    }
    function clamp() {
      if (!ed.img.naturalWidth) return;
      var s = scale();
      var maxX = Math.max(0, (ed.img.naturalWidth * s - canvas.width) / 2);
      var maxY = Math.max(0, (ed.img.naturalHeight * s - canvas.height) / 2);
      ed.x = Math.max(-maxX, Math.min(maxX, ed.x));
      ed.y = Math.max(-maxY, Math.min(maxY, ed.y));
    }
    function paint(ctx, w, h, k) {
      var s = scale() * k;
      var iw = ed.img.naturalWidth * s, ih = ed.img.naturalHeight * s;
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, w, h);
      ctx.drawImage(ed.img, (w - iw) / 2 + ed.x * k, (h - ih) / 2 + ed.y * k, iw, ih);
    }
    function draw() {
      if (!ed.img.naturalWidth) return;
      paint(canvas.getContext('2d'), canvas.width, canvas.height, 1);
    }
    var drag = null;
    canvas.addEventListener('pointerdown', function (e) { drag = { x: e.clientX, y: e.clientY }; canvas.setPointerCapture(e.pointerId); });
    canvas.addEventListener('pointermove', function (e) {
      if (!drag) return;
      ed.x += e.clientX - drag.x; ed.y += e.clientY - drag.y;
      drag = { x: e.clientX, y: e.clientY };
      clamp(); draw();
    });
    canvas.addEventListener('pointerup', function () { drag = null; });
    canvas.addEventListener('pointercancel', function () { drag = null; });

    ed.toFile = function () {
      return new Promise(function (resolve) {
        var r = ratioValue(ed.ratio);
        var w = r >= 1 ? 1600 : Math.round(1600 * r);
        var h = r >= 1 ? Math.round(1600 / r) : 1600;
        var out = document.createElement('canvas');
        out.width = w; out.height = h;
        paint(out.getContext('2d'), w, h, w / canvas.width);
        out.toBlob(function (blob) {
          var name = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
          resolve(new File([blob], name, { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.88);
      });
    };
    ed.img.onload = function () { mark(); layout(); };
    ed.img.src = URL.createObjectURL(file);
    return ed;
  }

  input.addEventListener('change', function () {
    box.innerHTML = '';
    ready = false;
    var files = Array.prototype.slice.call(input.files || []);
    if (files.length > max) {
      var warn = document.createElement('p');
      warn.className = 'text-sm text-rose-600';
      warn.textContent = 'เลือกได้ไม่เกิน ' + max + ' รูป ระบบใช้ ' + max + ' รูปแรก';
      box.appendChild(warn);
      files = files.slice(0, max);
    }
    editors = files.map(makeEditor);
  });

  // ส่งฟอร์ม: แทนไฟล์ด้วยรูปที่ครอปแล้ว แล้วส่งใหม่อีกครั้ง (ครั้งที่สองผ่านไปตามปกติ)
  form.addEventListener('submit', function (e) {
    if (ready || !editors.length) return;
    e.preventDefault();
    Promise.all(editors.map(function (ed) { return ed.toFile(); })).then(function (files) {
      var dt = new DataTransfer();
      files.forEach(function (f) { dt.items.add(f); });
      input.files = dt.files;
      ready = true;
      if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
    });
  });
})();
