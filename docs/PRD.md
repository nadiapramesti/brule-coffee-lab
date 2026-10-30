# PRD ringkas — BRÛLÉ Specialty Coffee Lab

> Dokumen ini disusun ringkas sebagai ringkasan kebutuhan produk. Sebagian ditulis setelah prototipe berjalan, jadi berfungsi sebagai acuan dan catatan keputusan, bukan dokumen yang dibuat sebelum satu baris kode pun.

## 1. Latar belakang dan masalah

Kopi di rak toko sudah lama disangrai dan aromanya turun sambil menunggu pembeli. Pembeli tidak bisa memilih level sangrai atau gilingan, dan tidak tahu kapan kopi disangrai atau sampai kapan masih enak.

## 2. Tujuan

- Pembeli bisa memesan kopi yang **disangrai setelah pesanan masuk**, dengan level sangrai, gilingan, dan berat pilihan sendiri.
- Roastery punya antrian kerja yang jelas per tahap.
- Admin bisa memantau penjualan, stok, dan laporan tanpa spreadsheet manual.

## 3. Pengguna

| Peran | Kebutuhan utama |
|---|---|
| Pembeli | Memilih biji, meracik, membayar, dan melacak pesanan |
| Roaster | Melihat antrian, memajukan tahap, mengisi resi |
| Admin | Mengelola produk, stok, voucher, pengguna, dan laporan |

## 4. User story (contoh)

- Sebagai pembeli, saya ingin memilih level sangrai dan melihat profil rasanya, agar kopi sesuai selera.
- Sebagai pembeli, saya ingin melacak tahap pesanan dan melihat tanggal terbaik dinikmati.
- Sebagai roaster, saya ingin melihat rencana sangrai per biji dan level, agar satu batch bisa melayani beberapa pesanan.
- Sebagai admin, saya ingin laporan penjualan bisa diunduh (Excel/PDF), agar mudah dipakai untuk pembukuan.

## 5. Lingkup

**Wajib (MVP):** katalog, roast builder, keranjang, checkout (alamat, voucher, bayar), alur pesanan 6 tahap, antrian roastery, dashboard dan laporan admin, login berbasis peran.

**Tambahan yang sudah ada:** blend dua biji, perlengkapan non-kopi, add-on (kemasan hadiah, kartu ucapan), ulasan terverifikasi, dua bahasa, tema terang/gelap, email transaksi.

**Di luar lingkup saat ini:** integrasi kurir, aplikasi mobile native, multi-cabang, langganan.

## 6. Aturan bisnis penting

- Harga dan stok dihitung ulang di server saat pesanan dibuat.
- Stok biji dikunci saat checkout; stok dikembalikan bila pesanan dibatalkan.
- Alur pesanan: Antrian → Sangrai → Giling → Kemas → Kirim → Selesai (pesanan non-kopi melewati sangrai dan giling).
- Invoice dan label kirim hanya bisa dicetak setelah pesanan lunas.

## 7. Kebutuhan non-fungsional

- Responsif di HP dan desktop; dua bahasa (ID/EN).
- Antrian roastery tetap cepat dengan banyak pesanan (diuji 5.000 pesanan, kurang dari 100 ms untuk hitungan antrian).
- Aturan bisnis utama diuji otomatis dengan Pest.

## 8. Ukuran keberhasilan

- Pembeli bisa menyelesaikan pesanan dari pilih biji sampai bayar tanpa bantuan.
- Roaster bisa memproses satu pesanan sampai kirim dalam beberapa klik.
- Seluruh tes otomatis lulus sebelum perubahan di-push.

## 9. Teknologi

Laravel 13, Livewire 3 (Volt), Tailwind CSS, Alpine.js, D3, Pest, MySQL (sqlite untuk dev/test), Midtrans Snap (sandbox).
