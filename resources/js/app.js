import './chromatogram';
import './flavor-wheel';
import './embers';

// Muncul saat masuk layar. Elemen yang sudah terlihat waktu halaman dibuka
// ikut dianimasikan, jadi hero punya pembuka tanpa kode tambahan.
const reveal = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
        if (e.isIntersecting) {
            e.target.classList.add('is-in');
            reveal.unobserve(e.target);
        }
    });
}, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });

const watchReveals = () =>
    document.querySelectorAll('[data-reveal]:not(.is-in)').forEach((el) => reveal.observe(el));

document.addEventListener('DOMContentLoaded', watchReveals);
document.addEventListener('livewire:navigated', watchReveals);

// Sidebar admin yang diciutkan: pulihkan setelah wire:navigate mengganti <html>.
document.addEventListener('livewire:navigated', () => {
    try {
        document.documentElement.classList.toggle('mini', localStorage.getItem('krm-mini') === '1');
    } catch (e) {}
});
