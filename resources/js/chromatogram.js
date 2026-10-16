import * as d3 from 'd3';

/**
 * Jejak kromatogram.
 *
 * Bentuknya mengikuti cara alat sungguhan menggambar: puncak tajam
 * dengan lebar yang berbeda-beda, ekor miring ke kanan karena senyawa
 * tidak keluar sekaligus, puncak-puncak kecil yang tidak diberi nama,
 * dan garis dasar yang naik pelan sepanjang pengukuran.
 *
 * Tanpa itu semua yang tergambar hanya deretan gundukan seragam —
 * terlihat seperti hiasan, bukan hasil ukur.
 */

// Puncak kecil tanpa nama. Tetap sama tiap kali digambar.
const MINOR = [
    [0.035, .05, .0035], [0.118, .09, .0040], [0.152, .06, .0030],
    [0.246, .13, .0045], [0.268, .07, .0032], [0.355, .05, .0030],
    [0.392, .10, .0038], [0.487, .08, .0036], [0.515, .14, .0042],
    [0.628, .06, .0030], [0.662, .11, .0040], [0.745, .09, .0038],
    [0.782, .05, .0028], [0.818, .12, .0044], [0.905, .07, .0034],
    [0.938, .04, .0030], [0.962, .08, .0036],
];

export default function chromatogram(peaks = [], text = {}) {
    return {
        peaks,

        init() {
            this.render();

            let frame;
            window.addEventListener('resize', () => {
                cancelAnimationFrame(frame);
                frame = requestAnimationFrame(() => this.render(false));
            });
        },

        render(animate = true) {
            const host = this.$refs.trace;
            if (!host) return; // listener resize bisa hidup setelah wire:navigate
            host.innerHTML = '';

            const width  = host.clientWidth || 1200;
            const narrow = width < 700;
            const height = Math.round(Math.max(230, Math.min(340, width * 0.235)));

            const padTop    = narrow ? 58 : 76;
            const baselineY = height - 48;
            const span      = baselineY - padTop;

            const svg = d3.select(host)
                .append('svg')
                .attr('width', '100%')
                .attr('height', height)
                .attr('viewBox', `0 0 ${width} ${height}`)
                .attr('role', 'img')
                .attr('aria-label',
                      (text.aria ?? 'Kromatogram aroma kopi: ') +
                      this.peaks.map((p) => p.reads).join(', '));

            const all = [
                ...this.peaks.map((p) => [p.at, p.height, p.width ?? 0.006]),
                ...MINOR,
            ];

            // Puncak nyata tidak simetris: sisi kanannya melandai lebih
            // panjang. Lebarnya dilipat di sebelah kanan puncak.
            const signal = (x) => {
                let s = 0.028 + 0.055 * x * x;        // garis dasar naik pelan
                for (const [at, h, w] of all) {
                    const d = (x - at) / (w * (x > at ? 2.1 : 1));
                    s += h * Math.exp(-0.5 * d * d);
                }
                return s;
            };

            const samples = d3.range(0, 1.0004, 0.0008)
                .map((t) => [t * width, baselineY - signal(t) * span]);

            // Sumbu waktu di bawah garis dasar
            const maxRt = 13;
            const axis = svg.append('g');
            for (let rt = 2; rt <= 12; rt += 2) {
                const x = (rt / maxRt) * width;
                axis.append('line')
                    .attr('x1', x).attr('x2', x)
                    .attr('y1', baselineY).attr('y2', baselineY + 5)
                    .attr('stroke', 'rgb(var(--c-muted))').attr('stroke-opacity', .3);
                if (!narrow) {
                    axis.append('text')
                        .attr('x', x).attr('y', baselineY + 20)
                        .attr('text-anchor', 'middle')
                        .attr('fill', 'rgb(var(--c-muted))').attr('fill-opacity', .45)
                        .attr('font-family', "'IBM Plex Mono', monospace")
                        .attr('font-size', 10)
                        .text(rt);
                }
            }

            svg.append('line')
                .attr('x1', 0).attr('x2', width)
                .attr('y1', baselineY).attr('y2', baselineY)
                .attr('stroke', 'rgb(var(--c-muted))').attr('stroke-opacity', .3);

            if (!narrow) {
                svg.append('text')
                    .attr('x', 24).attr('y', baselineY + 38)
                    .attr('text-anchor', 'start')
                    .attr('fill', 'rgb(var(--c-muted))').attr('fill-opacity', .45)
                    .attr('font-family', "'IBM Plex Mono', monospace")
                    .attr('font-size', 10)
                    .text(text.axis ?? 'waktu retensi (menit)');
            }

            const path = svg.append('path')
                .attr('d', d3.line().curve(d3.curveLinear)(samples))
                .attr('fill', 'none')
                .attr('stroke', '#2A5DB0')
                .attr('stroke-width', 1.8)
                .attr('stroke-linejoin', 'round')
                .attr('stroke-linecap', 'round');

            // ── label ──
            // Dipilih dari yang tertinggi, tapi yang terlalu berdempetan
            // dilewati supaya tulisannya tidak saling tindih.
            const minGap = narrow ? 104 : 132;
            const chosen = [];
            for (const p of [...this.peaks].sort((a, b) => b.height - a.height)) {
                const x = p.at * width;
                const edge = narrow ? 56 : 112;
                if (x < edge || x > width - edge) continue;
                if (chosen.every((c) => Math.abs(c.at * width - x) >= minGap)) {
                    chosen.push(p);
                }
            }

            const labels = svg.append('g');

            chosen.forEach((p) => {
                const x    = p.at * width;
                const apex = baselineY - signal(p.at) * span;
                const g    = labels.append('g').attr('opacity', animate ? 0 : 1);

                g.append('line')
                    .attr('x1', x).attr('x2', x)
                    .attr('y1', apex - 7)
                    .attr('y2', Math.max(26, apex - 24))
                    .attr('stroke', 'rgb(var(--c-muted))').attr('stroke-opacity', .4);

                g.append('text')
                    .attr('x', x).attr('y', Math.max(18, apex - 32))
                    .attr('text-anchor', 'middle')
                    .attr('fill', 'rgb(var(--c-fg))')
                    .attr('font-family', "'IBM Plex Sans Condensed', sans-serif")
                    .attr('font-size', narrow ? 13 : 15)
                    .attr('font-weight', 600)
                    .text(p.reads);

                if (!narrow) {
                    g.append('text')
                        .attr('x', x).attr('y', Math.max(32, apex - 18))
                        .attr('text-anchor', 'middle')
                        .attr('fill', 'rgb(var(--c-muted))').attr('fill-opacity', .7)
                        .attr('font-family', "'IBM Plex Mono', monospace")
                        .attr('font-size', 10.5)
                        .text(`${p.compound} · ${p.rt.toFixed(2)}`);
                }
            });

            if (!animate) return;

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                labels.selectAll('g').attr('opacity', 1);
                return;
            }

            const len = path.node().getTotalLength();
            path.attr('stroke-dasharray', `${len} ${len}`)
                .attr('stroke-dashoffset', len)
                .transition().duration(2100).ease(d3.easeSinInOut)
                .attr('stroke-dashoffset', 0)
                .on('end', function () { d3.select(this).attr('stroke-dasharray', null); });

            labels.selectAll('g')
                .transition()
                .delay((d, i) => 300 + chosen[i].at * 1700)
                .duration(400)
                .attr('opacity', 1);
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('chromatogram', chromatogram);
});
