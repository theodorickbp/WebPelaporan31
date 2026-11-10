/* SI-PASAR - perilaku kecil yang dipakai di banyak halaman. */

// Daftarkan service worker (shell CSS/JS saja, bukan data) supaya bisa dipasang di HP (PWA).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Toast hilang sendiri.
    document.querySelectorAll('[data-toast]').forEach((el) => {
        setTimeout(() => el.classList.add('is-leaving'), 5000);
        setTimeout(() => el.remove(), 5400);
    });

    // Tampilkan / sembunyikan kata sandi.
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Sembunyikan' : 'Tampilkan';
            btn.setAttribute('aria-pressed', String(show));
        });
    });

    // Konfirmasi sebelum aksi yang tidak bisa dibatalkan.
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    // Tombol "Tolak" / "Selesaikan" / "Kembalikan" wajib disertai catatan.
    document.querySelectorAll('[data-requires-note]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const note = btn.form && btn.form.querySelector('textarea[name="note"]');
            if (note && !note.value.trim()) {
                e.preventDefault();
                note.setCustomValidity(btn.dataset.noteMsg || 'Tulis alasan penolakan supaya siswa tahu.');
                note.reportValidity();
                note.addEventListener('input', () => note.setCustomValidity(''), { once: true });
            }
        });
    });

    // Cegah kirim ganda (mis. koneksi lambat di HP), tombol menampilkan spinner (lihat .btn[aria-busy]).
    document.querySelectorAll('form[data-once]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (e.defaultPrevented) return;
            const btn = form.querySelector('[type="submit"]');
            if (!btn) return;
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            if (btn.dataset.busy) btn.setAttribute('aria-label', btn.dataset.busy);
        });
    });

    // Skeleton shimmer untuk gambar (thumbnail tiket, galeri, thumbnail draft unggahan)
    // sampai gambarnya benar-benar selesai dimuat. Kelas .is-loading dipasang manual
    // sebagai fallback untuk browser lama yang belum dukung CSS :has().
    const SKELETON = '.ticket-thumb, .gallery-item, .thumb-open';
    const markLoaded = (img) => {
        img.setAttribute('data-loaded', '');
        img.closest(SKELETON)?.classList.remove('is-loading');
    };
    document.querySelectorAll('.ticket-thumb img, .gallery-item img, .thumb-open img').forEach((img) => {
        // Gambar tidak pernah disembunyikan; skeleton hanya latar. Jadi apa pun yang terjadi
        // pada event load, foto tetap terlihat begitu browser selesai mengunduhnya.
        if (img.complete && img.naturalWidth > 0) { markLoaded(img); return; }
        img.closest(SKELETON)?.classList.add('is-loading');
        img.addEventListener('load', () => markLoaded(img), { once: true });
        img.addEventListener('error', () => markLoaded(img), { once: true });
        setTimeout(() => markLoaded(img), 15000); // jaga-jaga: jangan biarkan shimmer berjalan selamanya
    });

    initPwaBanner();
});

// Pasang aplikasi (PWA). Android/desktop Chrome & Edge memberi tombol pasang langsung lewat event
// beforeinstallprompt, jadi event-nya ditangkap di sini sejak awal. iOS Safari tidak punya prompt
// otomatis, jadi cuma dikasih petunjuk. Tombol [data-pwa-open] (menu samping + halaman Profil) selalu
// ada untuk Admin, Toolman, dan Siswa; kalau prompt browser belum tersedia, muncul dialog petunjuk.
let pwaPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    pwaPrompt = e;
    document.dispatchEvent(new Event('pwa-ready'));
});

function initPwaBanner() {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    const hideInstallUi = () => {
        document.querySelectorAll('[data-pwa-open], [data-pwa-section]').forEach((el) => { el.hidden = true; });
        const b = document.getElementById('pwa-banner');
        if (b) b.hidden = true;
    };
    if (isStandalone) { hideInstallUi(); return; }

    const banner = document.getElementById('pwa-banner');
    const dialog = document.getElementById('pwa-help');
    const DISMISS_KEY = 'sipasar_pwa_dismissed';
    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;

    const dismissBanner = () => {
        if (banner) banner.hidden = true;
        try { localStorage.setItem(DISMISS_KEY, '1'); } catch (e) {}
    };
    const bannerDismissed = () => { try { return !!localStorage.getItem(DISMISS_KEY); } catch (e) { return false; } };

    const showBanner = () => {
        if (!banner || bannerDismissed()) return;
        if (isIos) {
            banner.querySelector('[data-text-ios]').hidden = false;
            banner.querySelector('[data-pwa-install]').hidden = true;
        } else {
            banner.querySelector('[data-text-prompt]').hidden = false;
        }
        banner.hidden = false;
    };
    if (isIos) showBanner();
    document.addEventListener('pwa-ready', showBanner);

    const openHelp = () => {
        if (!dialog) return;
        dialog.querySelector('[data-help-ios]').hidden = !isIos;
        dialog.querySelector('[data-help-other]').hidden = isIos;
        if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', '');
    };

    const install = async () => {
        if (!pwaPrompt) { openHelp(); return; }
        pwaPrompt.prompt();
        const choice = await pwaPrompt.userChoice;
        pwaPrompt = null;
        if (choice.outcome === 'accepted') { hideInstallUi(); dismissBanner(); }
    };

    window.addEventListener('appinstalled', () => { hideInstallUi(); dismissBanner(); });

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-pwa-install], [data-pwa-open]')) install();
        else if (e.target.closest('[data-pwa-close]')) dismissBanner();
        else if (e.target.closest('[data-help-close]') && dialog) dialog.close();
    });
}
