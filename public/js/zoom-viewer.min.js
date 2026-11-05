/*
 * Pratinjau foto dengan zoom.
 * - Sentuh: cubit untuk zoom, geser untuk menggerakkan, ketuk dua kali untuk perbesar/kecilkan,
 *   geser ke samping (saat tidak di-zoom) untuk pindah foto.
 * - Mouse/trackpad: scroll atau cubit untuk zoom, seret untuk menggerakkan, klik dua kali untuk zoom.
 * - Keyboard: Esc tutup, panah kiri/kanan pindah foto, + / - / 0 untuk zoom.
 * Elemen yang membuka viewer: apa pun dengan data-zoom-src (opsional data-zoom-group, data-zoom-alt).
 */
(() => {
    const viewer = document.getElementById('viewer');
    if (!viewer) return;

    const stage = viewer.querySelector('[data-stage]');
    const img = viewer.querySelector('[data-img]');
    const counter = viewer.querySelector('[data-counter]');
    const prevBtn = viewer.querySelector('[data-action="prev"]');
    const nextBtn = viewer.querySelector('[data-action="next"]');
    const closeBtn = viewer.querySelector('[data-action="close"]');

    const MIN = 1;
    const MAX = 6;
    let list = [];
    let index = 0;
    let opener = null;
    let scale = 1;
    let tx = 0;
    let ty = 0;
    const pts = new Map();
    let pinchDist = 0;
    let pan = null;
    let lastTapAt = 0;
    let lastTapPos = null;

    const apply = () => {
        img.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
        stage.classList.toggle('is-zoomed', scale > 1.01);
    };

    // Ukuran gambar yang benar-benar tampil (object-fit: contain) di dalam stage.
    const rendered = () => {
        const r = stage.getBoundingClientRect();
        const nw = img.naturalWidth || r.width;
        const nh = img.naturalHeight || r.height;
        const fit = Math.min(r.width / nw, r.height / nh);
        return { w: nw * fit, h: nh * fit, sw: r.width, sh: r.height };
    };

    const limitPan = () => {
        const { w, h, sw, sh } = rendered();
        const mx = Math.max(0, (w * scale - sw) / 2);
        const my = Math.max(0, (h * scale - sh) / 2);
        tx = Math.min(mx, Math.max(-mx, tx));
        ty = Math.min(my, Math.max(-my, ty));
    };

    // Zoom dengan titik (clientX, clientY) tetap berada di bawah jari/kursor.
    const zoomAt = (next, clientX, clientY) => {
        const r = stage.getBoundingClientRect();
        const cx = clientX - (r.left + r.width / 2);
        const cy = clientY - (r.top + r.height / 2);
        next = Math.min(MAX, Math.max(MIN, next));
        const k = next / scale;
        tx = cx - (cx - tx) * k;
        ty = cy - (cy - ty) * k;
        scale = next;
        if (scale === MIN) { tx = 0; ty = 0; }
        limitPan();
        apply();
    };

    const zoomCenter = (factor) => {
        const r = stage.getBoundingClientRect();
        zoomAt(scale * factor, r.left + r.width / 2, r.top + r.height / 2);
    };

    const reset = () => { scale = 1; tx = 0; ty = 0; apply(); };

    const show = (i) => {
        index = (i + list.length) % list.length;
        reset();
        const el = list[index];
        img.alt = el.dataset.zoomAlt || `Foto ${index + 1}`;
        img.src = el.dataset.zoomSrc;
        counter.textContent = list.length > 1 ? `${index + 1} / ${list.length}` : '';
        prevBtn.hidden = nextBtn.hidden = list.length < 2;
    };

    const step = (d) => { if (list.length > 1) show(index + d); };

    const open = (el) => {
        const group = el.dataset.zoomGroup;
        list = group ? Array.from(document.querySelectorAll(`[data-zoom-group="${group}"]`)) : [el];
        opener = el;
        viewer.hidden = false;
        document.body.classList.add('no-scroll');
        show(Math.max(0, list.indexOf(el)));
        closeBtn.focus();
    };

    const close = () => {
        viewer.hidden = true;
        document.body.classList.remove('no-scroll');
        img.removeAttribute('src');
        pts.clear();
        pan = null;
        if (opener && typeof opener.focus === 'function') opener.focus();
    };

    img.addEventListener('load', () => { limitPan(); apply(); });

    document.addEventListener('click', (e) => {
        const el = e.target.closest('[data-zoom-src]');
        if (el) { e.preventDefault(); open(el); }
    });

    viewer.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        switch (btn.dataset.action) {
            case 'close': close(); break;
            case 'prev': step(-1); break;
            case 'next': step(1); break;
            case 'zoom-in': zoomCenter(1.5); break;
            case 'zoom-out': zoomCenter(1 / 1.5); break;
            case 'reset': reset(); break;
        }
    });

    const dist = () => {
        const [a, b] = Array.from(pts.values());
        return Math.hypot(a.x - b.x, a.y - b.y);
    };
    const mid = () => {
        const [a, b] = Array.from(pts.values());
        return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
    };

    const handleTap = (e) => {
        const now = performance.now();
        if (lastTapPos && now - lastTapAt < 320 &&
            Math.hypot(e.clientX - lastTapPos.x, e.clientY - lastTapPos.y) < 30) {
            zoomAt(scale > 1.01 ? 1 : 2.5, e.clientX, e.clientY);
            lastTapAt = 0;
            lastTapPos = null;
        } else {
            lastTapAt = now;
            lastTapPos = { x: e.clientX, y: e.clientY };
        }
    };

    stage.addEventListener('pointerdown', (e) => {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        stage.setPointerCapture(e.pointerId);
        pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
        if (pts.size === 1) {
            pan = { x: e.clientX, y: e.clientY, tx, ty, moved: false, t: performance.now() };
        } else if (pts.size === 2) {
            pinchDist = dist();
            pan = null;
        }
    });

    stage.addEventListener('pointermove', (e) => {
        if (!pts.has(e.pointerId)) return;
        pts.set(e.pointerId, { x: e.clientX, y: e.clientY });

        if (pts.size === 2) {
            const d = dist();
            const m = mid();
            if (pinchDist > 0) zoomAt(scale * (d / pinchDist), m.x, m.y);
            pinchDist = d;
        } else if (pan) {
            const dx = e.clientX - pan.x;
            const dy = e.clientY - pan.y;
            if (Math.hypot(dx, dy) > 6) pan.moved = true;
            if (scale > 1) {
                tx = pan.tx + dx;
                ty = pan.ty + dy;
                limitPan();
                apply();
            }
        }
    });

    const end = (e) => {
        if (!pts.has(e.pointerId)) return;
        const wasSingle = pts.size === 1;
        pts.delete(e.pointerId);

        if (wasSingle && pan && e.type === 'pointerup') {
            const dx = e.clientX - pan.x;
            const dy = e.clientY - pan.y;
            const dt = performance.now() - pan.t;
            if (!pan.moved && dt < 350) {
                handleTap(e);
            } else if (scale === MIN && Math.abs(dx) > 60 && Math.abs(dy) < 80) {
                step(dx < 0 ? 1 : -1);
            }
            pan = null;
        } else if (pts.size === 1) {
            // Selesai mencubit: lanjutkan menggeser dengan jari yang tersisa.
            const [p] = Array.from(pts.values());
            pan = { x: p.x, y: p.y, tx, ty, moved: true, t: 0 };
            pinchDist = 0;
        } else if (pts.size === 0) {
            pan = null;
        }
    };
    stage.addEventListener('pointerup', end);
    stage.addEventListener('pointercancel', end);

    stage.addEventListener('wheel', (e) => {
        e.preventDefault();
        const f = Math.exp(-e.deltaY * (e.ctrlKey ? 0.01 : 0.0025));
        zoomAt(scale * f, e.clientX, e.clientY);
    }, { passive: false });

    document.addEventListener('keydown', (e) => {
        if (viewer.hidden) return;
        switch (e.key) {
            case 'Escape': close(); break;
            case 'ArrowLeft': step(-1); break;
            case 'ArrowRight': step(1); break;
            case '+': case '=': zoomCenter(1.5); break;
            case '-': zoomCenter(1 / 1.5); break;
            case '0': reset(); break;
            case 'Tab': {
                const f = Array.from(viewer.querySelectorAll('button:not([hidden])'));
                if (!f.length) break;
                const first = f[0];
                const last = f[f.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                break;
            }
        }
    });

    window.addEventListener('resize', () => { if (!viewer.hidden) { limitPan(); apply(); } });
})();
