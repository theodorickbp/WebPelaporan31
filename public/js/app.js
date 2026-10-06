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

    // Tombol "Tolak" wajib disertai catatan.
    document.querySelectorAll('[data-requires-note]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const note = btn.form && btn.form.querySelector('textarea[name="note"]');
            if (note && !note.value.trim()) {
                e.preventDefault();
                note.setCustomValidity('Tulis alasan penolakan supaya siswa tahu.');
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
    const markLoaded = (img) => {
        img.setAttribute('data-loaded', '');
        img.closest('.ticket-thumb, .gallery-item, .thumb-open')?.classList.remove('is-loading');
    };
    document.querySelectorAll('.ticket-thumb img, .gallery-item img, .thumb-open img').forEach((img) => {
        img.closest('.ticket-thumb, .gallery-item, .thumb-open')?.classList.add('is-loading');
        if (img.complete && img.naturalWidth > 0) markLoaded(img);
        else {
            img.addEventListener('load', () => markLoaded(img), { once: true });
            img.addEventListener('error', () => markLoaded(img), { once: true }); // jangan macet shimmer kalau gagal
        }
    });

    initPwaBanner();
});

// Banner "Pasang aplikasi" (PWA). Android/desktop Chrome & Edge dapat tombol pasang langsung
// (lewat event beforeinstallprompt). iOS Safari tidak punya prompt otomatis, jadi cuma dikasih petunjuk.
function initPwaBanner() {
    const banner = document.getElementById('pwa-banner');
    if (!banner) return;

    const DISMISS_KEY = 'sipasar_pwa_dismissed';
    if (localStorage.getItem(DISMISS_KEY)) return;

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    if (isStandalone) return;

    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
    let deferredPrompt = null;

    const dismiss = () => { banner.hidden = true; localStorage.setItem(DISMISS_KEY, '1'); };

    if (isIos) {
        banner.querySelector('[data-text-ios]').hidden = false;
        banner.querySelector('[data-pwa-install]').hidden = true;
        banner.hidden = false;
    } else {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            banner.querySelector('[data-text-prompt]').hidden = false;
            banner.hidden = false;
        });
        window.addEventListener('appinstalled', dismiss);
    }

    banner.addEventListener('click', async (e) => {
        if (e.target.closest('[data-pwa-install]') && deferredPrompt) {
            deferredPrompt.prompt();
            const choice = await deferredPrompt.userChoice;
            deferredPrompt = null;
            if (choice.outcome !== 'accepted') return; // biarkan banner tetap ada, mungkin coba lagi nanti
            dismiss();
        }
        if (e.target.closest('[data-pwa-close]')) dismiss();
    });
}
