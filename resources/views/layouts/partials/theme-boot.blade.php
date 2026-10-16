{{-- Pasang tema sebelum cat pertama supaya tidak ada kedipan terang/gelap.
     Pilihan pengunjung (localStorage) menang; kalau belum memilih, ikut sistem. --}}
<script>
(function () {
    try {
        var t = localStorage.getItem('krm-theme');
        if (t !== 'light' && t !== 'dark') t = matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        document.documentElement.dataset.theme = t;
    } catch (e) {}
})();
</script>
