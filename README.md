# BRÛLÉ — Specialty Coffee Lab

Toko kopi specialty **roast-on-demand**: biji baru disangrai setelah pesanan masuk. Pembeli memilih biji, level sangrai, ukuran, dan gilingan lewat *roast builder*; roastery dan admin mengurus antrian, stok, dan laporan dari panel internal. Dikerjakan sebagai tugas kuliah web e-commerce.

**Stack:** Laravel 13 · Livewire 3 (+ Volt untuk halaman auth) · Tailwind CSS · Alpine.js · D3 · Pest · MySQL (sqlite untuk dev/test).

## Tampilan

**Toko**

![Landing](docs/screenshots/01-landing.jpg)

| | |
|---|---|
| ![Katalog](docs/screenshots/02-katalog.jpg)<br>**Katalog**: harga, stok, rating, cari, sortir | ![Roast builder](docs/screenshots/03-builder.jpg)<br>**Roast builder**: level sangrai, flavor wheel |
| ![Keranjang](docs/screenshots/04-keranjang.jpg)<br>**Keranjang** multi-item | ![Checkout](docs/screenshots/05-checkout.jpg)<br>**Checkout**: alamat tersimpan, voucher, online / COD |
| ![Lacak](docs/screenshots/06-lacak.jpg)<br>**Lacak pesanan** per tahap produksi | ![Akun](docs/screenshots/07-akun.jpg)<br>**Akun saya**: riwayat, Beli lagi, ulasan |
| ![Perlengkapan](docs/screenshots/14-perlengkapan.jpg)<br>**Perlengkapan**: alat seduh, merchandise, aksesori | |

**Panel admin** (tema gelap)

| | |
|---|---|
| ![Dashboard](docs/screenshots/08-admin-dashboard.jpg)<br>**Dashboard** | ![Laporan](docs/screenshots/09-admin-laporan.jpg)<br>**Laporan** (ekspor Excel / PDF) |
| ![Pesanan](docs/screenshots/10-admin-pesanan.jpg)<br>**Pesanan** | ![Invoice](docs/screenshots/11-invoice.jpg)<br>**Invoice / label kirim** (COD vs NON-COD) |

**Mobile**

<p>
<img src="docs/screenshots/12-mobile-landing.jpg" width="260" alt="Landing mobile">
<img src="docs/screenshots/13-mobile-builder.jpg" width="260" alt="Builder mobile">
<img src="docs/screenshots/15-mobile-perlengkapan.jpg" width="260" alt="Perlengkapan mobile">
</p>

## Fitur

**Toko (pembeli)**
- Landing dengan katalog kartu produk: harga mulai dari, status stok, rating, pencarian, sortir, dan filter proses.
- Roast builder dengan flavor wheel dan kromatogram aroma; kantong kemasan dirender SVG per kopi.
- Keranjang multi-item (tamu di session, user login di database; keranjang tamu digabung saat login).
- Roast builder: 14 biji demo (6 proses, termasuk Carbonic Maceration), 4 level sangrai, 6 gilingan, berat 100 g / 200 g / 250 g / 500 g / 1 kg.
- **Blend builder**: campur dua biji dengan level sangrai sama (70/30, 60/40, 50/50); harga dan stok dihitung per biji.
- **Perlengkapan** (`/perlengkapan`): 10 produk non-kopi (alat seduh, penggiling, filter, merchandise, aksesori) dengan foto per varian, detail produk, badge stok, dan filter kategori. Pesanan yang hanya berisi barang non-kopi melewati tahap sangrai dan giling.
- Checkout dengan add-on opsional (kemasan hadiah, kartu ucapan; harga diatur admin, disimpan per pesanan), voucher, ongkir (gratis di atas batas tertentu), bayar online (simulasi) atau COD (bisa dimatikan / dibatasi nominalnya).
- Lacak pesanan per tahap produksi, riwayat pesanan + "Beli lagi", buku alamat, ulasan pembeli terverifikasi.
- Email transaksi (pesanan diterima, lunas, dikirim + resi, dibatalkan), notifikasi dalam aplikasi, login Google/Facebook (opsional).
- FAQ dan kebijakan (ID/EN), tema terang/gelap, dua bahasa ID/EN, halaman error bermerek, SEO dasar + sitemap.

**Panel internal (admin & roastery)**
- Dashboard, pesanan (cetak invoice/label kirim, hanya setelah lunas), jurnal sangrai.
- Antrian roastery siap banyak pesanan: hitungan lewat query agregat (diuji 5.000 pesanan, <100 ms), batas kartu per kolom, cari, filter terlambat (>24 jam), rencana sangrai per biji + level, centang banyak pesanan untuk dilanjutkan sekaligus dan cetak label massal, serta stok biji mentah yang bisa dicari.
- Kelola kopi dan perlengkapan (termasuk foto), stok dengan riwayat pergerakan, voucher, pelanggan, ulasan (moderasi), pengguna & peran, log aktivitas, pengaturan toko.
- Laporan penjualan dengan ekspor Excel dan PDF (7 sheet, termasuk "Per perlengkapan").

## Menjalankan di lokal

Butuh PHP 8.3+, Composer, Node 20+, dan MySQL (Laragon cukup).

```bash
git clone https://github.com/nadiapramesti/brule-coffee-lab.git
cd brule-coffee-lab
composer install
npm install

cp .env.example .env
php artisan key:generate
# edit .env: DB_CONNECTION=mysql, DB_DATABASE=brule, DB_USERNAME, DB_PASSWORD
# buat database kosong "brule" dulu

php artisan migrate --seed
php artisan storage:link      # supaya foto produk yang diupload tampil
npm run build                 # atau: npm run dev
php artisan serve
```

Setelah `git pull` berikutnya: `php artisan migrate`, `php artisan db:seed --class=ProductSeeder` (bila foto/produk berubah), `npm run build`, `php artisan view:clear`, lalu hard refresh (Ctrl+F5). Folder `public/build` tidak ikut git, jadi `npm run build` wajib di setiap mesin.

### Akun demo (dari seeder)

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@brule.test` | `password` |
| Roaster | `roaster@brule.test` | `password` |
| Pembeli | `customer@brule.test` | `password` |

Voucher contoh: `KOPIBARU10`. Ganti semua password dan kontak toko (Admin → Pengaturan) sebelum dipakai di luar demo.

### Konfigurasi opsional (`.env`)

- **Email:** `MAIL_MAILER=log` menulis email ke `storage/logs/laravel.log`. Untuk kirim sungguhan isi `MAIL_*` dengan SMTP, dan pastikan `APP_URL` benar karena tombol di email memakai alamat itu.
- **Login sosial:** `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` (dan Facebook). Jangan pernah commit `.env`.

## Tes

```bash
php artisan test
```

Suite Pest (277 tes) mencakup alur pesanan, blend, perlengkapan, keranjang, pembayaran COD/online, stok, voucher, akun dan alamat, ulasan, email, halaman statis, hak akses admin, header keamanan, dan pemantauan galat.

**Tes browser end-to-end dan aksesibilitas** (Playwright + axe-core, folder `e2e/`) berjalan terhadap server yang hidup:

```bash
npm install
npx playwright install chromium     # sekali saja (CI memakai Chrome bawaan runner)
php artisan serve                   # terminal lain, dengan data seeder
npm run e2e                         # smoke flow + aksesibilitas
npm run e2e:a11y                    # aksesibilitas saja
```

`e2e/smoke.spec.js` menguji alur racik, checkout, buat pesanan, akses admin vs pembeli, dan halaman 404. `e2e/a11y.spec.js` memeriksa WCAG 2.1 A/AA (kontras, label, atribut ARIA) di beranda, roast builder, perlengkapan, lacak pesanan, dan login, masing-masing di tema terang dan gelap; tes gagal bila ada pelanggaran berdampak *serious* atau *critical*.

**CI** (`.github/workflows/ci.yml`) menjalankan di tiap push dan pull request: Pest, audit dependensi (`composer audit`, `npm audit`), dan tes Playwright. Dependabot memeriksa pembaruan dependensi tiap minggu.

## Struktur singkat

```
app/Livewire/            halaman toko dan panel (satu komponen per halaman)
app/Livewire/Admin/      modul admin
app/Models/              Order (logika pesanan), Coffee (stok), Product (perlengkapan)
app/Support/             Cart (keranjang), Blend (campuran dua biji), SalesReport
public/products/         foto produk perlengkapan
app/Mail/ + Support/     email transaksi, keranjang
resources/views/         blade (layouts/brule = toko, layouts/panel = admin)
lang/                    terjemahan ID/EN (en.json, pages.php untuk halaman statis)
docs/screenshots/        gambar untuk README
```

## Catatan keputusan

- Harga dan stok selalu dihitung ulang di server saat pesanan dibuat; keranjang hanya menyimpan pilihan, bukan harga.
- Stok biji mentah dikunci per kopi saat checkout supaya dua pembeli tidak bisa memesan sisa yang sama.
- Pembayaran online memakai Midtrans Snap (sandbox) bila `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` diisi di `.env`. Tanpa kunci, tombol kembali ke mode simulasi. Webhook: `POST /midtrans/notification` (butuh URL publik, mis. ngrok; di localhost pakai tombol "Cek status pembayaran").
- **Tripline** (dashboard pemantau login, repo terpisah `tripline-intrusion-alert`) bisa dipasang opsional lewat `TRIPLINE_URL` + `TRIPLINE_INGEST_TOKEN` di `.env` -- login email/password dan login sosial melapor tiap percobaan ke sana, dan ditolak otomatis kalau IP-nya lagi dianggap berisiko (brute-force/spraying). Kosongkan salah satu variabel = nonaktif, login tetap jalan normal. Lihat `app/Support/Tripline.php`.

## Perlengkapan (produk non-kopi)

Halaman `/perlengkapan` menjual alat seduh, penggiling, filter, merchandise, dan aksesori. Stok dalam unit, tiap produk bisa punya pilihan (mis. ukuran kaos). Pesanan yang hanya berisi barang non-kopi melewati tahap sangrai dan giling (Antri → Dikemas → Dikirim). Pesanan campuran tetap lewat alur sangrai lengkap. Admin mengelola di **Admin → Perlengkapan**; laporan penjualan punya tabel "Per perlengkapan" terpisah. Data demo: `php artisan db:seed --class=ProductSeeder`.

## Blend builder

Di Roast builder, bagian "Campur dengan biji lain" menggabungkan dua biji dengan level sangrai yang sama, porsi 70/30, 60/40, atau 50/50. Harga dihitung per biji sesuai gramnya, stok biji mentah dipotong per biji, dan dikembalikan ke keduanya bila pesanan dibatalkan. Radar rasa dan catatan rasa ikut dirata-ratakan. Ulasan boleh ditulis untuk kedua biji.

## Proses pengembangan

### Metode yang dipakai

Pengerjaan memakai pendekatan **iteratif-inkremental** (gaya Agile, dikerjakan satu orang): fitur dibangun per irisan kecil yang langsung bisa dicoba, lalu diperbaiki dari hasil uji dan masukan. Bukan Scrum penuh, karena tidak ada tim, sprint, atau product owner terpisah.

| Praktik | Penerapan di proyek ini |
|---|---|
| Kebutuhan | PRD ringkas dan user story per peran (pembeli, roaster, admin), lihat [docs/PRD.md](docs/PRD.md) |
| Pemodelan | Alur pesanan berupa state machine 6 tahap, tabel dan relasi dirancang sebelum migrasi |
| Pengujian | Pest (Feature test) untuk aturan bisnis, Playwright untuk E2E, axe-core untuk aksesibilitas, semuanya dijalankan di CI |
| Versi kode | Git, commit kecil per perubahan, riwayat di GitHub |
| Dokumentasi | README, PRD, catatan keputusan, tangkapan layar |
| Pengiriman | Rilis bertahap; tiap tahap dicoba lewat akun demo (admin, roaster, pembeli) |

### Tahapan membuat aplikasi (web maupun apk)

Urutan yang lazim di industri, dan yang dijadikan acuan di sini:

1. **Ide dan masalah.** Siapa penggunanya, masalah apa yang dipecahkan, dan kenapa aplikasi ini perlu ada. Di sini: kopi di rak sudah lama disangrai dan pembeli tidak bisa memilih.
2. **Riset dan pembanding.** Lihat aplikasi sejenis, cari yang kurang, tentukan pembeda (roast-on-demand).
3. **Kebutuhan (PRD).** Tujuan, pengguna, user story, fitur wajib dan opsional, batasan, dan ukuran berhasil.
4. **Ruang lingkup (MVP).** Pilih fitur minimum yang sudah berguna; sisanya masuk daftar nanti.
5. **Perancangan.** Alur pengguna, data (ERD), arsitektur, pilihan teknologi, dan aturan bisnis (mis. stok dikunci saat checkout).
6. **Desain UI/UX.** Wireframe, gaya visual, lalu prototipe yang diuji ke calon pengguna.
7. **Implementasi.** Menulis kode per fitur kecil, dengan version control.
8. **Pengujian.** Unit/feature test, uji manual per peran, uji tampilan HP, uji beban untuk bagian berat.
9. **Rilis (deploy).** Server atau hosting, domain, HTTPS, konfigurasi `.env`, backup, dan data awal.
10. **Pemeliharaan.** Pantau galat, dengarkan masukan pengguna, perbaiki, dan rilis berkala.

Langkah 1 sampai 6 sengaja dikerjakan sebelum kode ditulis, karena memperbaiki salah paham di kertas jauh lebih murah daripada di kode.

### Kelemahan yang diketahui

- **Pembayaran** baru diuji di sandbox Midtrans; belum ada uji transaksi sungguhan dan belum ada rekonsiliasi otomatis dengan laporan Midtrans.
- **Ongkir** memakai tarif flat dengan batas gratis ongkir, belum terhubung ke API kurir (tarif per kota/berat, cek resi otomatis).
- **Satu lokasi roastery** dan satu gudang; belum ada banyak cabang.
- **Email dan notifikasi** berjalan di proses yang sama dengan aplikasi; belum ada antrian khusus (queue worker) untuk beban besar.
- **CI baru berupa tes dan audit**; belum ada deploy otomatis (CD) ke server.
- **Pemantauan galat** baru berupa notifikasi webhook (Slack/Discord) dengan dedupe; belum ada dasbor, agregasi, atau pelacakan performa (APM).
- **Tes E2E** baru satu browser (Chromium) dan alur utama; belum ada Firefox/Safari, tes visual, atau tes tiap peran sampai tuntas.
- **Aksesibilitas** diperiksa otomatis dengan axe di lima halaman publik; belum diuji manual dengan pembaca layar dan halaman panel admin belum tercakup.
- **Keamanan** punya praktik dasar (validasi, harga dihitung ulang di server, peran dan izin, header keamanan, audit dependensi otomatis); belum ada audit atau pentest oleh pihak independen, dan belum ada Content-Security-Policy.
- **Foto perlengkapan** dibuat dengan AI dan data demo fiktif, jadi belum mewakili katalog nyata.
- **Belum ada aplikasi mobile**; yang tersedia web responsif.

### Pengembangan lebih lanjut

- Integrasi API kurir untuk tarif dan pelacakan resi otomatis.
- Pembayaran produksi (Midtrans live) dan beberapa metode tambahan.
- Langganan kopi (kirim berkala) dan program loyalitas.
- Queue worker dan cache untuk email, laporan, dan beban tinggi.
- Deploy otomatis (CD) dan analisis statis (Larastan) di CI.
- Sentry atau APM untuk agregasi galat dan performa di produksi.
- Content-Security-Policy dan pentest oleh pihak independen.
- Perluas E2E ke Firefox/WebKit, halaman admin, dan uji manual dengan pembaca layar.
- Prediksi kebutuhan stok biji dari riwayat pesanan.
- PWA atau aplikasi mobile agar notifikasi status pesanan bisa langsung ke HP.
- Dukungan banyak roastery/cabang dengan stok terpisah.

## Lisensi

Copyright © 2026 Nadia Pramesti. All Rights Reserved. Repositori ini dipublikasikan sebagai portofolio; kode, desain, logo, nama, dan foto BRÛLÉ tidak boleh digunakan, disalin, dimodifikasi, didistribusikan, atau dijual tanpa izin tertulis. Detail di [LICENSE](LICENSE).

## Kredit

Dikerjakan sendiri oleh Nadia Pramesti sebagai tugas kuliah. Foto produk perlengkapan dan merchandise dibuat dengan AI generatif, dan data demo (nama petani, koperasi, Q-grader) adalah contoh fiktif.
