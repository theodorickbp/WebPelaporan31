/*
 * Uploader foto bukti.
 * Sumber foto: kamera (HP: aplikasi kamera; laptop: kamera browser), galeri/berkas, seret & lepas, tempel (Ctrl+V).
 * Setiap foto dikecilkan (maks 1600px, JPEG) sebelum diunggah supaya hemat kuota dan tidak melewati batas PHP.
 * Foto disinkronkan ke <input type="file" name="photos[]"> sungguhan, jadi form tetap dikirim seperti biasa.
 */
(() => {
    const root = document.querySelector('[data-uploader]');
    if (!root) return;

    const MAX = Number(root.dataset.max || 3);
    const MAX_BYTES = Number(root.dataset.maxMb || 8) * 1024 * 1024;
    const SIDE = 1600;

    const real = root.querySelector('[data-real]');
    const camInput = root.querySelector('[data-camera-input]');
    const galInput = root.querySelector('[data-gallery-input]');
    const drop = root.querySelector('[data-drop]');
    const list = root.querySelector('[data-thumbs]');
    const count = root.querySelector('[data-count]');
    const status = root.querySelector('[data-status]');
    const cam = root.querySelector('[data-camera]');
    const video = cam.querySelector('video');
    const camCount = cam.querySelector('[data-camera-count]');
    const form = root.closest('form');

    const items = [];
    let stream = null;

    const say = (msg, error = false) => {
        status.textContent = msg;
        status.classList.toggle('is-error', error);
    };

    const sync = () => {
        try {
            const dt = new DataTransfer();
            items.forEach((it) => dt.items.add(it.file));
            real.files = dt.files;
        } catch (err) {
            say('Browser ini belum mendukung unggah foto di halaman ini. Coba perbarui browser.', true);
        }
    };

    const render = () => {
        const nodes = items.map((it, i) => {
            const li = document.createElement('li');
            li.className = 'thumb';

            const open = document.createElement('button');
            open.type = 'button';
            open.className = 'thumb-open';
            open.dataset.zoomSrc = it.url;
            open.dataset.zoomGroup = 'draft';
            open.dataset.zoomAlt = `Foto bukti ${i + 1}`;
            open.setAttribute('aria-label', `Perbesar foto bukti ${i + 1}`);
            const image = document.createElement('img');
            image.src = it.url;
            image.alt = `Foto bukti ${i + 1}`;
            open.append(image);

            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'thumb-remove';
            rm.setAttribute('aria-label', `Hapus foto bukti ${i + 1}`);
            rm.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
            rm.addEventListener('click', () => remove(i));

            li.append(open, rm);
            return li;
        });
        list.replaceChildren(...nodes);
        count.textContent = `${items.length} dari ${MAX} foto`;
        if (camCount) camCount.textContent = `${items.length} / ${MAX}`;
        const full = items.length >= MAX;
        root.querySelectorAll('[data-action="camera"], [data-action="gallery"]').forEach((b) => { b.disabled = full; });
    };

    const remove = (i) => {
        const [gone] = items.splice(i, 1);
        if (gone) URL.revokeObjectURL(gone.url);
        sync();
        render();
        say('');
    };

    const loadImage = (file) => new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const image = new Image();
        image.onload = () => { URL.revokeObjectURL(url); resolve(image); };
        image.onerror = () => { URL.revokeObjectURL(url); reject(new Error('decode')); };
        image.src = url;
    });

    const compress = async (file) => {
        try {
            const image = await loadImage(file);
            const ratio = Math.min(1, SIDE / Math.max(image.naturalWidth, image.naturalHeight));
            const w = Math.max(1, Math.round(image.naturalWidth * ratio));
            const h = Math.max(1, Math.round(image.naturalHeight * ratio));
            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, w, h);
            ctx.drawImage(image, 0, 0, w, h);
            const blob = await new Promise((res) => canvas.toBlob(res, 'image/jpeg', 0.82));
            if (!blob) return file;
            if (ratio === 1 && file.type === 'image/jpeg' && blob.size >= file.size) return file;
            const base = (file.name || 'foto').replace(/\.[^.]+$/, '');
            return new File([blob], `${base}.jpg`, { type: 'image/jpeg', lastModified: Date.now() });
        } catch (err) {
            // Tidak bisa didekode: pakai file asli hanya jika formatnya didukung server.
            if (/^image\/(jpeg|png|webp)$/.test(file.type)) return file;
            throw err;
        }
    };

    const addFiles = async (fileList) => {
        const files = Array.from(fileList || []).filter(
            (f) => f.type.startsWith('image/') || /\.(jpe?g|png|webp|heic|heif)$/i.test(f.name)
        );
        if (!files.length) { say('Hanya file gambar yang bisa dipakai sebagai bukti.', true); return; }

        root.classList.add('is-busy');
        say('Menyiapkan foto…');
        let problem = '';

        for (const f of files) {
            if (items.length >= MAX) { problem = `Maksimal ${MAX} foto per laporan.`; break; }
            try {
                const out = await compress(f);
                if (out.size > MAX_BYTES) { problem = 'Foto terlalu besar. Coba ambil ulang.'; continue; }
                items.push({ file: out, url: URL.createObjectURL(out) });
            } catch (err) {
                problem = 'Foto tidak bisa dibaca. Coba foto lain.';
            }
        }

        root.classList.remove('is-busy');
        sync();
        render();
        say(problem, Boolean(problem));
    };

    // ----- Kamera browser (laptop / desktop) -----
    const openCamera = async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false,
            });
        } catch (err) {
            say('Kamera tidak bisa dibuka. Izinkan akses kamera di browser, atau seret foto ke kotak ini.', true);
            return;
        }
        video.srcObject = stream;
        cam.hidden = false;
        document.body.classList.add('no-scroll');
        try { await video.play(); } catch (err) { /* autoplay dicegah: abaikan */ }
        cam.querySelector('[data-action="snap"]').focus();
    };

    const closeCamera = () => {
        if (stream) stream.getTracks().forEach((t) => t.stop());
        stream = null;
        video.srcObject = null;
        cam.hidden = true;
        document.body.classList.remove('no-scroll');
    };

    const snap = () => {
        if (!video.videoWidth || items.length >= MAX) return;
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        canvas.toBlob(async (blob) => {
            if (!blob) return;
            await addFiles([new File([blob], `kamera-${Date.now()}.jpg`, { type: 'image/jpeg' })]);
            if (items.length >= MAX) closeCamera();
        }, 'image/jpeg', 0.9);
    };

    const canUseBrowserCamera = () => !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    const isTouch = () => window.matchMedia('(pointer: coarse)').matches;

    root.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        switch (btn.dataset.action) {
            case 'camera':
                if (isTouch() || !canUseBrowserCamera()) camInput.click(); else openCamera();
                break;
            case 'gallery': galInput.click(); break;
            case 'snap': snap(); break;
            case 'close-camera': closeCamera(); break;
        }
    });

    [camInput, galInput].forEach((input) => {
        input.addEventListener('change', async () => {
            await addFiles(input.files);
            input.value = '';
        });
    });

    // ----- Seret & lepas, tempel -----
    ['dragenter', 'dragover'].forEach((t) => drop.addEventListener(t, (e) => {
        e.preventDefault();
        drop.classList.add('is-over');
    }));
    ['dragleave', 'drop'].forEach((t) => drop.addEventListener(t, (e) => {
        e.preventDefault();
        drop.classList.remove('is-over');
    }));
    drop.addEventListener('drop', (e) => addFiles(e.dataTransfer && e.dataTransfer.files));
    // Cegah browser membuka foto bila terlepas di luar kotak.
    ['dragover', 'drop'].forEach((t) => window.addEventListener(t, (e) => e.preventDefault()));

    document.addEventListener('paste', (e) => {
        const files = Array.from((e.clipboardData && e.clipboardData.files) || []);
        if (files.length) addFiles(files);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !cam.hidden) closeCamera();
    });

    // ----- Kirim form -----
    if (form) {
        form.addEventListener('submit', (e) => {
            if (!items.length || real.files.length !== items.length) {
                e.preventDefault();
                say(items.length ? 'Foto belum siap. Coba lagi.' : 'Tambahkan minimal 1 foto bukti.', true);
                drop.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        });
    }

    render();
})();
