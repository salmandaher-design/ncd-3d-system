/* NCD 3D Print — front-end interactions (vanilla JS) */
(function () {
    'use strict';

    /* ---------- Theme (light / dark) ---------- */
    const THEME_KEY = 'ncd-theme';
    function applyTheme(t) {
        document.documentElement.setAttribute('data-theme', t);
        const icon = document.querySelector('#themeToggle i');
        if (icon) icon.className = t === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    }
    const saved = localStorage.getItem(THEME_KEY)
        || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    applyTheme(saved);

    document.addEventListener('click', function (e) {
        const t = e.target.closest('#themeToggle');
        if (t) {
            const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            localStorage.setItem(THEME_KEY, next);
            applyTheme(next);
        }
    });

    /* ---------- Mobile sidebar ---------- */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('#sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const backdrop = document.querySelector('.sidebar-backdrop');
        if (btn && sidebar) {
            sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('show');
        }
        if (e.target.closest('.sidebar-backdrop')) {
            if (sidebar) sidebar.classList.remove('open');
            e.target.closest('.sidebar-backdrop').classList.remove('show');
        }
    });

    /* ---------- Lightweight modals ---------- */
    // Open:  <button data-modal="#id">
    // Close: element with [data-close] inside .modal2-backdrop, or click on backdrop
    document.addEventListener('click', function (e) {
        const opener = e.target.closest('[data-modal]');
        if (opener) {
            e.preventDefault();
            const m = document.querySelector(opener.getAttribute('data-modal'));
            if (m) {
                m.classList.add('open');
                // Prefill fields from data-set-* attributes (edit modals)
                Object.keys(opener.dataset).forEach(function (k) {
                    if (k.indexOf('set') === 0) {
                        const field = k.slice(3).toLowerCase();
                        const el = m.querySelector('[name="' + field + '"]');
                        if (el) {
                            if (el.type === 'checkbox') el.checked = opener.dataset[k] === '1';
                            else el.value = opener.dataset[k];
                        }
                    }
                });
                const titleTarget = m.querySelector('[data-modal-title]');
                if (titleTarget && opener.dataset.title) titleTarget.textContent = opener.dataset.title;
            }
        }
        if (e.target.closest('[data-close]') || e.target.classList.contains('modal2-backdrop')) {
            const open = document.querySelector('.modal2-backdrop.open');
            if (open) open.classList.remove('open');
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const open = document.querySelector('.modal2-backdrop.open');
            if (open) open.classList.remove('open');
        }
    });

    /* ---------- Confirm before destructive submit ---------- */
    document.addEventListener('submit', function (e) {
        const form = e.target;
        const msg = form.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) {
            e.preventDefault();
        }
    });

    /* ---------- Auto-submit filters ---------- */
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });

    /* ---------- Upload previews ---------- */
    function fmtSize(b) {
        const u = ['B', 'KB', 'MB', 'GB']; let i = 0;
        while (b >= 1024 && i < u.length - 1) { b /= 1024; i++; }
        return (Math.round(b * 10) / 10) + ' ' + u[i];
    }

    // Project files (multiple)
    const fileInput = document.querySelector('#fileInput');
    const fileList = document.querySelector('#fileList');
    const dz = document.querySelector('#fileDrop');
    if (fileInput && fileList) {
        function renderFiles() {
            // Keep any quantities the user already typed when the list re-renders.
            const prev = Array.from(fileList.querySelectorAll('input[name="quantities[]"]')).map(i => i.value);
            fileList.innerHTML = '';
            Array.from(fileInput.files).forEach(function (f, idx) {
                const div = document.createElement('div');
                div.className = 'file-item';
                div.innerHTML =
                    '<i class="bi bi-file-earmark-zip"></i>' +
                    '<span class="fname">' + f.name.replace(/</g, '&lt;') + '</span>' +
                    '<span class="fsize">' + fmtSize(f.size) + '</span>' +
                    '<label class="fqty" title="Number of prints required of this file">' +
                        '<span>×</span>' +
                        '<input type="number" name="quantities[]" min="1" step="1" value="' + (prev[idx] || 1) + '">' +
                    '</label>';
                fileList.appendChild(div);
            });
            const hint = document.querySelector('#fileQtyHint');
            if (hint) hint.style.display = fileInput.files.length ? 'block' : 'none';
        }
        fileInput.addEventListener('change', renderFiles);
        if (dz) {
            dz.addEventListener('click', function () { fileInput.click(); });
            ['dragover', 'dragenter'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('dragover'); }));
            ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('dragover'); }));
            dz.addEventListener('drop', function (e) { fileInput.files = e.dataTransfer.files; renderFiles(); });
        }
    }

    // Single image preview
    const imgInput = document.querySelector('#imageInput');
    const imgPreview = document.querySelector('#imagePreview');
    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', function () {
            const f = imgInput.files[0];
            if (f) {
                imgPreview.src = URL.createObjectURL(f);
                imgPreview.style.display = 'block';
            }
        });
    }

    /* ---------- Merge requests into a print job ---------- */
    const mergeBar = document.querySelector('#mergeBar');
    if (mergeBar) {
        const picks = () => Array.from(document.querySelectorAll('[data-pick]'));
        const countEl = document.querySelector('#pickCount');
        const titleEl = document.querySelector('#mergeTitle');
        const targetEl = document.querySelector('#mergeTarget');

        function refresh() {
            const n = picks().filter(c => c.checked).length;
            if (countEl) countEl.textContent = n;
            mergeBar.classList.toggle('show', n > 0);
            // The plate name only applies when creating a NEW job.
            if (titleEl && targetEl) titleEl.style.display = targetEl.value === '0' ? '' : 'none';
        }

        document.addEventListener('change', function (e) {
            if (e.target.matches('[data-pick]')) refresh();
            if (e.target === targetEl) refresh();
            if (e.target.id === 'pickAll') {
                picks().forEach(c => { c.checked = e.target.checked; });
                refresh();
            }
        });
        const clearBtn = document.querySelector('#pickClear');
        if (clearBtn) clearBtn.addEventListener('click', function () {
            picks().forEach(c => { c.checked = false; });
            const all = document.querySelector('#pickAll');
            if (all) all.checked = false;
            refresh();
        });
        refresh();
    }

    /* ---------- Wall of Spaghetti: "Press F to pay respects" ---------- */
    const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-respect-url]');
        if (!btn || btn.disabled) return;
        btn.disabled = true;
        fetch(btn.getAttribute('data-respect-url'), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        }).then(r => r.json()).then(function (d) {
            if (d && d.ok) {
                const c = btn.querySelector('.rcount');
                if (c) c.textContent = d.respects;
                btn.classList.add('done', 'pop');
            } else {
                btn.disabled = false;
            }
        }).catch(function () { btn.disabled = false; });
    });
})();
