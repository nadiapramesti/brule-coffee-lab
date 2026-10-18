# Skenario demo BRÛLÉ (±8 menit)

Tujuan: tunjukkan satu siklus penuh, dari pembeli memilih kopi sampai roastery mengirim dan pembeli mengulas. Hafalkan urutannya, bukan kalimatnya.

## H-1 / sebelum mulai

1. `git pull`, `composer install`, `php artisan migrate`, `npm run build`, `php artisan view:clear`.
2. Data bersih: `php artisan migrate:fresh --seed` (menghapus data lama, aman untuk demo). Lalu `php artisan storage:link`.
3. Admin → Pengaturan: isi IG/WA/email asli (bukan placeholder), cek batas gratis ongkir.
4. Opsional tapi bagus: unggah foto produk di Admin → Kelola kopi untuk 2–3 kopi.
5. Siapkan 3 tab browser sudah login: **Pembeli**, **Admin**, **Roaster** (pakai jendela incognito berbeda supaya sesi tidak bentrok).
6. Cek di jaringan yang akan dipakai: halaman utama kebuka, gambar muncul, tidak ada error di Console.
7. `MAIL_MAILER=log` aman; buka `storage/logs/laravel.log` di editor kalau mau tunjukkan email.
8. Cadangan: rekam layar satu kali jalan penuh. Kalau internet/server bermasalah, putar rekaman.

## Alur (ikuti urutan ini)

| # | Tab | Yang dilakukan | Poin yang disebut |
|---|---|---|---|
| 1 | Pembeli (belum login) | Landing → scroll ke katalog. Ketik "gayo" di pencarian, ubah sortir ke harga terendah. | "Katalog punya harga, stok, rating, cari, dan sortir." |
| 2 | Pembeli | Klik **Racik kopi ini** → geser level sangrai, perhatikan flavor wheel dan kromatogram berubah. | "Ini pembeda kami: biji disangrai setelah pesanan masuk." |
| 3 | Pembeli | **Tambah ke keranjang**, tambah satu racikan lagi (kopi/gilingan lain), buka keranjang. | "Keranjang multi-item, qty dan gilingan bisa diubah." |
| 4 | Pembeli | Checkout (login `customer@brule.test`), alamat "Rumah" sudah terpilih otomatis (data seed), pakai voucher `KOPIBARU10`. | "Harga dihitung ulang di server, voucher divalidasi." |
| 5 | Pembeli | Pilih **Bayar online** → buat pesanan → **Bayar sekarang**. Dengan key Midtrans terisi, popup Snap terbuka (kartu uji `4811 1111 1111 1114`, CVV `123`, OTP `112233`); tanpa key, tombolnya simulasi. | "Pembayaran lewat Midtrans sandbox; status divalidasi di server, bukan dari browser." |
| 6 | Roaster | Antrian roastery → majukan: Sangrai → Giling → Kemas → isi resi → Kirim. | "Tiap bungkus punya jurnal sangrai sendiri." |
| 7 | Pembeli | Halaman **Lacak** ikut berubah; buka notifikasi lonceng. | "Pembeli melihat tahapnya real-time." |
| 8 | Admin | Pesanan → buka pesanan itu → **Cetak invoice** (hanya bisa setelah lunas). | "Invoice berformat form kirim, ada label COD / NON-COD." |
| 9 | Admin | Dashboard, Laporan (ekspor Excel/PDF), Stok (stok berkurang otomatis). | "Semua tercatat di log aktivitas." |
| 10 | Roaster/Admin → Pembeli | Tandai pesanan **Selesai**. Di Akun saya → Pesanan, tulis **ulasan**; ulasan muncul di kartu katalog. | "Ulasan hanya dari pembeli terverifikasi; admin bisa moderasi." |

Cadangan singkat kalau waktu mepet: lewati #9 dan tunjukkan cuma Laporan.

Variasi COD (30 detik, kalau ditanya): checkout → pilih COD → pesanan langsung masuk antrian, label invoice bertanda COD. Admin bisa mematikan COD atau membatasi nominalnya di Pengaturan.

## Pertanyaan yang mungkin muncul

- **Kenapa Livewire?** Satu bahasa (PHP) untuk server dan interaksi; cocok untuk tim satu orang. Interaksi ringan memakai Alpine.
- **Bagaimana mencegah stok minus?** Stok biji mentah dikunci per kopi di dalam transaksi database saat pesanan dibuat; pembatalan mengembalikan stok.
- **Apakah pembayaran sungguhan?** Belum, simulasi. Struktur status pesanan sudah siap dihubungkan ke gateway.
- **Keamanan?** Validasi di server, peran admin/roaster/pembeli lewat middleware, harga tidak dipercaya dari browser, `.env` tidak di-commit.
- **Berapa tes?** `php artisan test` (±170 tes). Jalankan sekali sebelum presentasi supaya yakin hijau.
- **Yang belum ada / rencana:** payment gateway nyata, ongkir per kota/kurir, ulasan dengan foto.

## Kalau ada yang error saat demo

| Gejala | Tindakan cepat |
|---|---|
| Halaman putih / error 500 | `php artisan view:clear` lalu refresh. Cek `storage/logs/laravel.log`. |
| Tampilan berantakan, gaya hilang | `npm run build` lalu Ctrl+F5. |
| Gambar produk tidak muncul | `php artisan storage:link`. |
| Pesanan tidak bisa dibuat | Stok kopi habis? Tambah di Admin → Stok. |
| Tombol tidak merespons | Hard refresh (Ctrl+F5); kalau masih, buka tab baru. |
| Semua kacau | Putar rekaman cadangan, lanjutkan penjelasan dari slide. |

## Tips presentasi

- Jangan menjelaskan kode; jelaskan **masalah yang dipecahkan** di tiap langkah.
- Satu kalimat pembuka: "BRÛLÉ menjual kopi yang baru disangrai setelah kamu memesan."
- Kalau lupa urutan, kembali ke tabel di atas; itu sudah urut.
- Kalau ditanya sesuatu yang belum ada, jawab jujur: "Belum, rencananya …" Itu lebih baik daripada mengarang.
