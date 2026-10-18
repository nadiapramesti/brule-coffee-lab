# Skenario demo BRÛLÉ (±8 menit)

Tujuan: tunjukkan satu siklus penuh, dari pembeli memilih kopi sampai roastery mengirim dan pembeli mengulas. Hafalkan urutannya, bukan kalimatnya.

## H-1 / sebelum mulai

1. `git pull`, `composer install`, `php artisan migrate`, `npm run build`, `php artisan view:clear`.
2. Data bersih: `php artisan migrate:fresh --seed` (menghapus data lama, aman untuk demo). Lalu `php artisan storage:link`.
3. Midtrans: pastikan `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` (mode **Sandbox**) terisi di `.env`, lalu `php artisan config:clear`. Tanpa key, tombol bayar jatuh ke mode simulasi, tetap aman untuk demo.
4. Jalankan server dengan beberapa worker supaya halaman tidak macet saat popup pembayaran: di PowerShell `$env:PHP_CLI_SERVER_WORKERS=4; php artisan serve --port=8000`. Buka lewat `http://localhost:8000` (bukan `127.0.0.1`), karena login Google terdaftar untuk alamat itu.
5. Admin → Pengaturan: isi IG/WA/email asli (bukan placeholder), cek batas gratis ongkir.
6. Opsional tapi bagus: unggah foto produk di Admin → Kelola kopi untuk 2–3 kopi.
7. Siapkan 3 tab browser sudah login: **Pembeli**, **Admin**, **Roaster** (pakai jendela incognito berbeda supaya sesi tidak bentrok).
8. Cek di jaringan yang akan dipakai: halaman utama kebuka, gambar muncul, tidak ada error di Console.
9. `MAIL_MAILER=log` aman; buka `storage/logs/laravel.log` di editor kalau mau tunjukkan email.
10. Cadangan: rekam layar satu kali jalan penuh. Kalau internet/server bermasalah, putar rekaman.

## Alur (ikuti urutan ini)

| # | Tab | Yang dilakukan | Poin yang disebut |
|---|---|---|---|
| 1 | Pembeli (belum login) | Landing → scroll ke katalog. Ketik "gayo" di pencarian, ubah sortir ke harga terendah. Klik **EN** di header sebentar, lalu kembali ke **ID**. | "Katalog punya harga, stok, rating, cari, dan sortir. Situs dua bahasa." |
| 2 | Pembeli | Klik **Racik kopi ini** → geser level sangrai, perhatikan flavor wheel dan kromatogram berubah. | "Ini pembeda kami: biji disangrai setelah pesanan masuk." |
| 3 | Pembeli | **Tambah ke keranjang**, tambah satu racikan lagi (kopi/gilingan lain), buka keranjang. | "Keranjang multi-item, qty dan gilingan bisa diubah." |
| 4 | Pembeli | Checkout (login `customer@brule.test`, atau **Masuk dengan Google**), alamat "Rumah" sudah terpilih otomatis (data seed), pakai voucher `KOPIBARU10`. | "Harga dihitung ulang di server, voucher divalidasi." |
| 5 | Pembeli | Pilih **Bayar online** → buat pesanan → **Bayar sekarang**. Dengan key Midtrans terisi, popup Snap terbuka (kartu uji `4811 1111 1111 1114`, CVV `123`, OTP `112233`); tanpa key, tombolnya simulasi. Setelah bayar, kalau status belum berubah, klik **Cek status pembayaran**. | "Pembayaran lewat Midtrans sandbox; status divalidasi di server, bukan dari browser. Di laptop tidak ada webhook publik, jadi tombol cek status yang menarik statusnya." |
| 6 | Roaster | Antrian roastery → majukan: Sangrai → Giling → Kemas → isi resi → Kirim. | "Tiap bungkus punya jurnal sangrai sendiri." |
| 7 | Pembeli | Halaman **Lacak** ikut berubah; buka notifikasi lonceng. | "Pembeli melihat tahapnya real-time." |
| 8 | Admin | Pesanan → buka pesanan itu → **Cetak invoice** (hanya bisa setelah lunas). | "Invoice berformat form kirim, ada label COD / NON-COD." |
| 9 | Admin | Dashboard, Laporan (ekspor Excel/PDF), Stok (stok berkurang otomatis). | "Semua tercatat di log aktivitas." |
| 10 | Roaster/Admin → Pembeli | Tandai pesanan **Selesai**. Di Akun saya → Pesanan, tulis **ulasan**; ulasan muncul di kartu katalog. | "Ulasan hanya dari pembeli terverifikasi; admin bisa moderasi." |

Opsional (±1 menit, kalau ada waktu): **Admin → Pengguna**. Tunjukkan tab **Diblokir**, blokir satu pelanggan dengan alasan (pelanggan itu otomatis keluar dari sesinya), lalu **Buka blokir**. Staf bisa diedit atau dihapus dari baris yang sama; admin tidak bisa menghapus dirinya sendiri atau admin terakhir. Pembeli bisa menghapus akunnya sendiri di **Akun saya**: data pribadi dibuang, riwayat pesanan tetap utuh.

Cadangan singkat kalau waktu mepet: lewati #9 dan tunjukkan cuma Laporan.

Variasi COD (30 detik, kalau ditanya): checkout → pilih COD → pesanan langsung masuk antrian, label invoice bertanda COD. Admin bisa mematikan COD atau membatasi nominalnya di Pengaturan.

## Pertanyaan yang mungkin muncul

- **Kenapa Livewire?** Satu bahasa (PHP) untuk server dan interaksi; cocok untuk tim satu orang. Interaksi ringan memakai Alpine.
- **Bagaimana mencegah stok minus?** Stok biji mentah dikunci per kopi di dalam transaksi database saat pesanan dibuat; pembatalan mengembalikan stok.
- **Apakah pembayaran sungguhan?** Terhubung ke Midtrans Snap mode **sandbox**: alurnya nyata (token dibuat di server, popup, status dicek ke Midtrans), tapi uangnya uang uji. Untuk produksi tinggal ganti key dan `MIDTRANS_IS_PRODUCTION=true`.
- **Bagaimana memastikan pembayaran tidak dipalsukan?** Browser tidak dipercaya. Status diambil dari server Midtrans, dan notifikasi webhook diverifikasi dengan tanda tangan SHA-512 serta dicocokkan dengan nominal pesanan.
- **Kenapa tidak ada webhook di demo?** Webhook butuh URL publik; di localhost digantikan tombol **Cek status pembayaran**. Setelah di-deploy, webhook jalan otomatis.
- **Apa yang terjadi kalau akun dihapus atau diblokir?** Hapus akun = data pribadi dianonimkan (soft delete), pesanan dan ulasan tetap ada untuk laporan. Akun diblokir otomatis keluar dari sesinya dan tidak bisa masuk lagi sampai dibuka blokirnya.
- **Keamanan?** Validasi di server, peran admin/roaster/pembeli lewat middleware, harga tidak dipercaya dari browser, `.env` tidak di-commit.
- **Berapa tes?** `php artisan test` (205 tes, semua hijau saat terakhir dijalankan). Jalankan sekali sebelum presentasi supaya yakin hijau.
- **Yang belum ada / rencana:** Midtrans mode produksi, webhook otomatis setelah deploy, ongkir per kota/kurir, ulasan dengan foto.

## Kalau ada yang error saat demo

| Gejala | Tindakan cepat |
|---|---|
| Halaman putih / error 500 | `php artisan view:clear` lalu refresh. Cek `storage/logs/laravel.log`. |
| Tampilan berantakan, gaya hilang | `npm run build` lalu Ctrl+F5. |
| Gambar produk tidak muncul | `php artisan storage:link`. |
| Pesanan tidak bisa dibuat | Stok kopi habis? Tambah di Admin → Stok. |
| Tombol tidak merespons | Hard refresh (Ctrl+F5); kalau masih, buka tab baru. |
| Halaman menggantung / mental balik saat popup bayar | Server satu worker. Restart dengan `$env:PHP_CLI_SERVER_WORKERS=4; php artisan serve --port=8000`. |
| "Gateway pembayaran sedang tidak bisa dihubungi" | Key Midtrans salah/kosong, atau internet putus. Cek `.env`, jalankan `php artisan config:clear`. Cadangan: kosongkan key untuk mode simulasi. |
| Status bayar belum berubah setelah popup selesai | Klik **Cek status pembayaran** di halaman Lacak. |
| Login Google gagal (`redirect_uri_mismatch`) | Buka lewat `http://localhost:8000`, bukan `127.0.0.1`, dan pastikan sama dengan yang didaftarkan di Google Cloud. |
| Semua kacau | Putar rekaman cadangan, lanjutkan penjelasan dari slide. |

## Tips presentasi

- Jangan menjelaskan kode; jelaskan **masalah yang dipecahkan** di tiap langkah.
- Satu kalimat pembuka: "BRÛLÉ menjual kopi yang baru disangrai setelah kamu memesan."
- Kalau lupa urutan, kembali ke tabel di atas; itu sudah urut.
- Kalau ditanya sesuatu yang belum ada, jawab jujur: "Belum, rencananya …" Itu lebih baik daripada mengarang.
