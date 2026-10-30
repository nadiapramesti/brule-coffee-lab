# Deploy BRÛLÉ ke Render (gratis, tanpa laptop menyala)

Satu layanan Docker melayani web dan API (`/api/...`). Data contoh diisi otomatis saat pertama menyala.

## Yang perlu dipahami dulu (paket gratis)

| Hal | Akibatnya |
|---|---|
| Tidur setelah 15 menit sepi | Request pertama setelah itu butuh sekitar 1 menit untuk bangun. Buka sekali sebelum demo. |
| Penyimpanan sementara | Database SQLite dan foto unggahan **kembali ke data contoh** tiap layanan bangun atau deploy ulang. Pesanan, akun baru, dan foto yang dibuat saat demo akan hilang, dan sesi login di app ikut terputus. |
| Cocok untuk | Demo dan penilaian tugas. Bukan untuk data sungguhan. |

Kalau perlu data awet, ganti ke database Postgres/MySQL terkelola (berbayar atau tier gratis dengan batas waktu) atau hosting cPanel.

## Langkah

1. Pastikan perubahan sudah di-push ke branch `feature/mobile-api` (file `Dockerfile`, `docker/entrypoint.sh`, `render.yaml`).
2. Buat kunci aplikasi di laptop (folder API):
   ```
   php artisan key:generate --show
   ```
   Salin hasilnya (diawali `base64:`).
3. Buka <https://dashboard.render.com>, masuk dengan GitHub.
4. **New +** lalu **Blueprint**, pilih repo `brule-coffee-lab`, branch `feature/mobile-api`.
5. Isi `APP_KEY` dengan kunci tadi. `MIDTRANS_*` boleh dikosongkan dulu. Klik **Apply**.
6. Tunggu build selesai (sekitar 5-10 menit pertama). Log ada di tab **Logs**.
7. Cek di browser (ganti dengan alamat layananmu):
   - `https://brule-api.onrender.com/up` harus 200
   - `https://brule-api.onrender.com/api/coffees` harus berisi data kopi
   - `https://brule-api.onrender.com/` tampil web BRÛLÉ

## Menyambungkan app Flutter

Alamat server diisi **tanpa** `/api` di belakangnya.

APK dengan alamat bawaan:
```
flutter build apk --release --dart-define=API_BASE=https://brule-api.onrender.com
```
Atau tanpa build ulang: di app, tap **UBAH ALAMAT SERVER** lalu isi `https://brule-api.onrender.com`.

## Hal lain

- **Midtrans:** isi `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` (sandbox) di **Environment**, lalu daftarkan URL notifikasi `https://brule-api.onrender.com/midtrans/notification` di dashboard Midtrans.
- **Login Google/Facebook:** isi `GOOGLE_*` / `FACEBOOK_*` di Environment dan daftarkan redirect `https://brule-api.onrender.com/auth/google/callback` (dan `facebook`) di consolenya. Karena sudah berupa domain HTTPS, login ini akhirnya bisa dipakai di HP asli.
- **Akun demo:** sama seperti lokal (`admin@brule.test`, `roaster@brule.test`, `customer@brule.test`, password `password`). **Ganti kalau dipakai selain demo.**
- **Update kode:** push ke `feature/mobile-api`, Render deploy ulang otomatis.
- **Jalan di Docker sendiri:** `docker build -t brule .` lalu `docker run -p 10000:10000 -e APP_KEY=base64:... -e SEED_DEMO=1 brule`.
