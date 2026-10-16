<?php

// Pesan bahasa Indonesia untuk aturan yang dipakai situs ini; sisanya
// jatuh ke teks bawaan Laravel supaya tidak ada kunci yang hilang.
return array_merge(require __DIR__.'/../en/validation.php', [
    'required'  => ':attribute wajib diisi.',
    'email'     => ':attribute harus berupa alamat email yang valid.',
    'unique'    => ':attribute ini sudah terdaftar.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'lowercase' => ':attribute harus huruf kecil.',
    'regex'     => 'Format :attribute tidak valid.',
    'string'    => ':attribute harus berupa teks.',
    'max'       => ['string' => ':attribute maksimal :max karakter.', 'numeric' => ':attribute maksimal :max.', 'array' => ':attribute maksimal :max item.', 'file' => ':attribute maksimal :max KB.'],
    'min'       => ['string' => ':attribute minimal :min karakter.', 'numeric' => ':attribute minimal :min.', 'array' => ':attribute minimal :min item.', 'file' => ':attribute minimal :min KB.'],
    'password'  => [
        'letters'       => ':attribute harus mengandung minimal satu huruf.',
        'mixed'         => ':attribute harus mengandung huruf besar dan kecil.',
        'numbers'       => ':attribute harus mengandung minimal satu angka.',
        'symbols'       => ':attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':attribute ini pernah bocor di internet. Pilih yang lain.',
    ],
    'current_password' => 'Password salah.',
    'attributes' => [
        'name' => 'Nama', 'email' => 'Email', 'phone' => 'Nomor WhatsApp', 'password' => 'Password',
        'password_confirmation' => 'Konfirmasi password', 'current_password' => 'Password saat ini',
        'form.email' => 'Email', 'form.password' => 'Password',
    ],
]);
