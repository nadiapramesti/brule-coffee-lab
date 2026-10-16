// Bara kecil yang naik pelan dari bawah hero. Dekorasi murni:
// berhenti kalau tab tidak terlihat, mati total bila pengguna
// meminta gerak dikurangi.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('embers', () => ({
        init() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            const canvas = this.$refs.canvas;
            const ctx = canvas.getContext('2d');
            let w = 0, h = 0, raf = 0, visible = true;
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            const count = window.innerWidth < 768 ? 22 : 46;

            const spawn = (initial = false) => ({
                x: Math.random() * w,
                y: initial ? Math.random() * h : h + 10,
                r: 0.6 + Math.random() * 1.7,
                vy: 0.18 + Math.random() * 0.5,
                sway: Math.random() * Math.PI * 2,
                swaySpeed: 0.004 + Math.random() * 0.01,
                life: 0,
                max: 280 + Math.random() * 360,
            });

            let parts = [];
            const resize = () => {
                w = canvas.clientWidth; h = canvas.clientHeight;
                canvas.width = w * dpr; canvas.height = h * dpr;
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                parts = Array.from({ length: count }, () => spawn(true));
            };
            resize();
            window.addEventListener('resize', resize);

            new IntersectionObserver(([e]) => { visible = e.isIntersecting; }).observe(canvas);

            const tick = () => {
                raf = requestAnimationFrame(tick);
                if (!visible || document.hidden) return;
                ctx.clearRect(0, 0, w, h);
                for (let i = 0; i < parts.length; i++) {
                    const p = parts[i];
                    p.life++; p.y -= p.vy; p.sway += p.swaySpeed;
                    p.x += Math.sin(p.sway) * 0.35;
                    const t = p.life / p.max;
                    if (t >= 1 || p.y < -10) { parts[i] = spawn(); continue; }
                    const a = Math.sin(Math.PI * t) * 0.75;
                    const g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 5);
                    g.addColorStop(0, `rgba(255,190,110,${a})`);
                    g.addColorStop(0.35, `rgba(232,120,40,${a * 0.45})`);
                    g.addColorStop(1, 'rgba(232,120,40,0)');
                    ctx.fillStyle = g;
                    ctx.beginPath(); ctx.arc(p.x, p.y, p.r * 5, 0, Math.PI * 2); ctx.fill();
                }
            };
            tick();
        },
    }));

    // Jam sangrai palsu tapi jujur secara bentuk: suhu naik mengikuti
    // kurva, lalu berhenti di titik "first crack" dan mengulang.
    window.Alpine.data('roastClock', () => ({
        temp: 196, sec: 0,
        init() {
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.sec = 9 * 60 + 42; this.temp = 205.6;
            if (reduced) return;
            setInterval(() => {
                this.sec = this.sec >= 12 * 60 ? 8 * 60 + 30 : this.sec + 1;
                const wobble = (Math.sin(this.sec / 3) + Math.sin(this.sec / 7)) * 0.9;
                this.temp = Math.round((196 + ((this.sec - 510) / 210) * 28 + wobble * 0.4) * 10) / 10;
            }, 1000);
        },
        get clock() {
            return String(Math.floor(this.sec / 60)).padStart(2, '0') + ':' + String(this.sec % 60).padStart(2, '0');
        },
    }));
});
