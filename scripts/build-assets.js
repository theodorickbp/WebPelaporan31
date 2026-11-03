/*
 * Build aset produksi SI-PASAR.
 * Sengaja TIDAK ada bundler/framework - ini cuma minifikasi file yang sudah ada
 * (terser untuk JS, clean-css untuk CSS) supaya lebih ringan dikirim ke HP siswa.
 * File sumber (yang dibaca manusia, tanpa "min") tetap yang diedit untuk pengembangan.
 * Jalankan: npm install && npm run build
 */
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..', 'public');
const bin = (name) => path.join(__dirname, '..', 'node_modules', '.bin', name);

const JS_FILES = ['js/app.js', 'js/zoom-viewer.js', 'js/photo-uploader.js'];
const CSS_FILES = ['css/sipasar.css'];

function size(p) { return fs.statSync(p).size; }
function fmt(n) { return (n / 1024).toFixed(1) + ' KB'; }

console.log('== Minifikasi JS (terser) ==');
for (const rel of JS_FILES) {
    const src = path.join(ROOT, rel);
    const out = src.replace(/\.js$/, '.min.js');
    execFileSync(bin('terser'), [src, '-c', '-m', '--comments', 'false', '-o', out]);
    console.log(`${rel} -> ${path.basename(out)}  ${fmt(size(src))} -> ${fmt(size(out))}`);
}

console.log('\n== Minifikasi CSS (clean-css) ==');
for (const rel of CSS_FILES) {
    const src = path.join(ROOT, rel);
    const out = src.replace(/\.css$/, '.min.css');
    execFileSync(bin('cleancss'), ['-O2', src, '-o', out]);
    console.log(`${rel} -> ${path.basename(out)}  ${fmt(size(src))} -> ${fmt(size(out))}`);
}
console.log('\nSelesai. File .min.* di public/ sudah dipakai oleh Blade (lihat layouts/base.blade.php dan @push scripts).');
