<?php

/*
 * Isi halaman bantuan dan kebijakan. Penanda :flat, :free, :cod, :wa, :email diisi dari Pengaturan toko
 * saat halaman dibuka, jadi tidak perlu ubah teks ini tiap angka berubah.
 */
return [
    'faq' => [
        'title' => 'Bantuan (FAQ)',
        'intro' => 'Jawaban untuk pertanyaan yang paling sering masuk. Kalau belum ketemu, hubungi kami di WhatsApp :wa atau email :email.',
        'groups' => [
            ['title' => 'Memesan', 'items' => [
                ['q' => 'Bagaimana cara memesan?', 'a' => 'Buka Roast builder, pilih kopi, level sangrai, gilingan, dan berat, lalu tekan "Tambah ke keranjang". Tambah kopi lain kalau mau, lalu Checkout. Kamu perlu akun untuk checkout; isi keranjang sebelum login tidak hilang.'],
                ['q' => 'Kenapa kopinya disangrai setelah pesanan masuk?', 'a' => 'Supaya kamu menerima kopi yang segar. Biji disangrai sesuai pilihanmu setelah pesanan masuk antrian, bukan diambil dari stok sangrai lama.'],
                ['q' => 'Berapa lama sampai pesanan berangkat?', 'a' => 'Berangkat dalam 24 jam setelah pesanan masuk antrian sangrai (setelah lunas, atau langsung untuk COD). Pantau tahapannya di halaman Lacak pesanan.'],
                ['q' => 'Berat dan gilingan apa saja yang tersedia?', 'a' => 'Berat 200 g, 500 g, atau 1 kg. Gilingan: biji utuh, V60 / pour over, espresso, French press, tubruk, dan Aeropress.'],
                ['q' => 'Bisa beli beberapa kopi sekaligus?', 'a' => 'Bisa. Masukkan semuanya ke keranjang (maksimal 10 baris, tiap baris maksimal 10 bungkus). Semua dikirim dalam satu paket dengan satu ongkir.'],
            ]],
            ['title' => 'Pembayaran', 'items' => [
                ['q' => 'Metode pembayaran apa yang tersedia?', 'a' => 'Bayar online, atau COD (bayar tunai ke kurir saat paket tiba) :cod'],
                ['q' => 'Bagaimana COD bekerja?', 'a' => 'Pesanan COD langsung masuk antrian sangrai tanpa menunggu pembayaran. Siapkan uang tunai sebesar total saat paket tiba. Jumlah tagihannya tercetak di label paket.'],
                ['q' => 'Bagaimana memakai voucher?', 'a' => 'Ketik kode di kolom voucher pada halaman checkout lalu tekan "Pakai". Satu pesanan bisa memakai satu kode. Syarat seperti minimal belanja atau batas pemakaian akan ditampilkan kalau kodenya belum bisa dipakai.'],
                ['q' => 'Apakah harga bisa berubah setelah checkout?', 'a' => 'Tidak. Harga dihitung ulang oleh sistem saat pesanan dibuat dan tidak berubah setelahnya.'],
            ]],
            ['title' => 'Pengiriman', 'items' => [
                ['q' => 'Berapa ongkos kirimnya?', 'a' => 'Ongkir flat Rp:flat per pesanan. :free'],
                ['q' => 'Bagaimana melacak pesanan?', 'a' => 'Masukkan kode pesanan (format BRL-TANGGAL-NOMOR) di halaman Lacak pesanan. Tahapannya: Masuk Antrian, Disangrai, Digiling, Dikemas, Dikirim, Selesai. Nomor resi muncul begitu paket dikirim.'],
                ['q' => 'Saya salah menulis alamat. Bagaimana?', 'a' => 'Hubungi kami di WhatsApp :wa secepatnya, sebelum pesanan berstatus Dikirim, dengan menyebutkan kode pesananmu.'],
            ]],
            ['title' => 'Pembatalan', 'items' => [
                ['q' => 'Bagaimana membatalkan pesanan?', 'a' => 'Pesanan yang masih Menunggu Pembayaran bisa kamu batalkan sendiri di halaman Lacak pesanan. Setelah masuk antrian, hubungi kami di WhatsApp :wa selama biji belum disangrai. Pesanan yang sudah disangrai tidak bisa dibatalkan karena kopinya dibuat khusus untukmu.'],
                ['q' => 'Stoknya dikembalikan kalau dibatalkan?', 'a' => 'Ya. Biji yang sudah ditahan untuk pesananmu dikembalikan ke stok, dan voucher yang terpakai bisa dipakai lagi.'],
            ]],
            ['title' => 'Soal kopi', 'items' => [
                ['q' => 'Apa bedanya level sangrai?', 'a' => 'Makin terang, makin terasa karakter asli biji (floral, buah, asam segar). Makin gelap, makin dominan rasa cokelat, kacang, dan pahit-manis hasil sangrai. Skor Agtron di builder menunjukkan tingkat kegelapan: angka kecil berarti lebih gelap.'],
                ['q' => 'Bagaimana menyimpan kopi?', 'a' => 'Simpan di kantong atau wadah tertutup rapat, di tempat sejuk dan kering, jauh dari sinar matahari. Hindari kulkas karena lembap. Rasa terbaik biasanya dalam 2 sampai 6 minggu setelah tanggal sangrai.'],
                ['q' => 'Gilingan mana yang cocok untuk alat seduhku?', 'a' => 'V60 / pour over: sedang-halus. Espresso: halus. French press: kasar. Tubruk: halus seperti bubuk. Aeropress: sedang-halus. Tidak punya penggiling? Pilih gilingan sesuai alatmu, atau biji utuh kalau kamu menggiling sendiri.'],
            ]],
        ],
    ],

    'privacy' => [
        'title' => 'Kebijakan Privasi',
        'updated' => 'Terakhir diperbarui: 17 Oktober 2026',
        'intro' => 'Kami hanya mengumpulkan data yang dibutuhkan untuk memproses pesananmu.',
        'sections' => [
            ['h' => 'Data yang kami kumpulkan', 'p' => ['Saat mendaftar dan memesan, kami menyimpan nama, email, nomor WhatsApp, alamat pengiriman, dan riwayat pesananmu. Kalau kamu masuk dengan akun Google, kami menerima nama dan email dari Google; kami tidak menerima kata sandi Google-mu.']],
            ['h' => 'Untuk apa data dipakai', 'li' => ['Memproses, menyangrai, dan mengirim pesananmu.', 'Menghubungimu soal pesanan lewat WhatsApp atau email.', 'Mengirim notifikasi status pesanan di dalam situs.', 'Laporan penjualan internal toko, tanpa menjual datamu ke pihak lain.']],
            ['h' => 'Dengan siapa data dibagikan', 'p' => ['Alamat dan nomor teleponmu diberikan ke kurir hanya agar paket sampai. Kami tidak menjual atau menyewakan datamu.']],
            ['h' => 'Cookie dan penyimpanan di browser', 'p' => ['Kami memakai cookie sesi agar kamu tetap masuk dan keranjangmu tersimpan. Pilihan tema (terang/gelap) dan bahasa disimpan di browsermu. Kami tidak memasang pelacak iklan.']],
            ['h' => 'Keamanan', 'p' => ['Kata sandi disimpan dalam bentuk terenkripsi (hash), bukan teks asli. Akses panel admin dibatasi untuk staf yang berwenang, dan perubahan penting dicatat di log aktivitas.']],
            ['h' => 'Hakmu', 'p' => ['Kamu bisa meminta salinan, perbaikan, atau penghapusan datamu dengan menulis ke :email. Data pesanan yang dibutuhkan untuk pembukuan bisa tetap disimpan sebatas keperluan itu.']],
        ],
    ],

    'terms' => [
        'title' => 'Syarat & Ketentuan',
        'updated' => 'Terakhir diperbarui: 17 Oktober 2026',
        'intro' => 'Dengan membuat pesanan di situs ini, kamu menyetujui syarat berikut.',
        'sections' => [
            ['h' => 'Pesanan dan harga', 'p' => ['Semua harga dalam Rupiah. Harga dihitung ulang oleh sistem saat pesanan dibuat dan tidak berubah setelahnya. Kami berhak menolak atau membatalkan pesanan bila stok tidak mencukupi atau data pesanan tidak lengkap.']],
            ['h' => 'Kopi dibuat sesuai pesanan', 'p' => ['Kopi disangrai dan digiling setelah pesanan masuk antrian sesuai pilihanmu. Karena itu pesanan tidak bisa dibatalkan setelah biji mulai disangrai.']],
            ['h' => 'Pembayaran', 'li' => ['Bayar online: pesanan masuk antrian setelah pembayaran terkonfirmasi.', 'COD: kamu membayar tunai sebesar total tagihan kepada kurir saat paket diterima. Pesanan COD yang ditolak atau tidak ditebus tanpa alasan yang sah dapat membatasi penggunaan COD berikutnya.', ':cod']],
            ['h' => 'Voucher', 'p' => ['Voucher memiliki syarat (misalnya minimal belanja, masa berlaku, atau batas pemakaian) dan tidak bisa ditukar uang. Satu pesanan hanya bisa memakai satu kode.']],
            ['h' => 'Pengiriman', 'p' => ['Ketentuan pengiriman, ongkos kirim, dan klaim kerusakan ada di halaman Pengiriman & Pengembalian.']],
            ['h' => 'Akun', 'p' => ['Kamu bertanggung jawab atas keamanan akunmu dan kebenaran data yang kamu isi. Jangan membagikan kata sandimu.']],
            ['h' => 'Perubahan syarat', 'p' => ['Kami dapat memperbarui syarat ini sewaktu-waktu. Versi terbaru selalu ada di halaman ini; pesanan yang sudah dibuat mengikuti syarat saat pesanan dibuat.']],
            ['h' => 'Kontak', 'p' => ['Pertanyaan soal syarat ini: :email atau WhatsApp :wa.']],
        ],
    ],

    'shipping' => [
        'title' => 'Pengiriman & Pengembalian',
        'updated' => 'Terakhir diperbarui: 17 Oktober 2026',
        'intro' => 'Cara kami mengirim kopimu, dan apa yang bisa dilakukan kalau ada masalah.',
        'sections' => [
            ['h' => 'Proses dan pengiriman', 'p' => ['Pesanan disangrai setelah masuk antrian dan berangkat dalam 24 jam. Nomor resi muncul di halaman Lacak pesanan begitu paket dikirim. Paket dikirim dari :address.']],
            ['h' => 'Ongkos kirim', 'p' => ['Ongkir flat Rp:flat per pesanan, berapa pun jumlah item di dalamnya. :free']],
            ['h' => 'Kalau paket rusak atau salah', 'p' => ['Hubungi kami dalam 2 x 24 jam setelah paket diterima lewat WhatsApp :wa atau email :email. Sertakan kode pesanan dan foto atau video saat paket dibuka. Kalau terbukti rusak saat pengiriman atau item yang dikirim tidak sesuai, kami kirim pengganti atau mengembalikan dana.']],
            ['h' => 'Pengembalian karena selera', 'p' => ['Kopi adalah produk makanan yang dibuat khusus sesuai pesananmu, jadi kami tidak menerima pengembalian karena alasan selera atau salah memilih level sangrai dan gilingan. Kalau hasilnya belum cocok, ceritakan ke kami; kami bantu pilihkan kombinasi yang lebih pas untuk pesanan berikutnya.']],
            ['h' => 'Pembatalan', 'p' => ['Pesanan bisa dibatalkan selama biji belum disangrai. Lihat FAQ untuk caranya.']],
        ],
    ],
];
