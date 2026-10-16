@php
    $code = 403;
    $heading = __('Halaman ini bukan untukmu');
    $body = __('Akunmu tidak punya izin untuk membuka halaman ini. Kalau menurutmu ini keliru, hubungi kami lewat kontak di beranda.');
@endphp
@include('errors.layout')
