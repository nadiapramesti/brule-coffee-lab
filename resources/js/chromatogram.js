import * as d3 from 'd3';

/**
 * Jejak kromatogram.
 *
 * Satu garis dasar yang melintasi layar dengan puncak-puncak di
 * posisi retention time tertentu — bentuk yang sama dengan waveform
 * di logo. Tiap puncak diberi nama senyawa aroma yang betul-betul
 * ada di kopi, plus rasa yang terbaca dari senyawa itu.
 *
 * Garisnya menggambar sendiri satu kali saat halaman dibuka.
 * Tidak ada animasi lain di halaman ini.
 */
export default function chromatogram(peaks = []) {
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
            host.innerHTML = '';

            const width  = host.clientWidth || 1200;
            const height = Math.max(190, Math.min(260, width * 0.19));

            const padTop    = 54;   // ruang untuk label puncak
            const baselineY = height - 26;
            const span      = baselineY - padTop;

            const svg = d3.select(host)
                .append('svg')
                .attr('width', '100%')
                .attr('height', height)
                .attr('viewBox', `0 0 ${width} ${height}`)
                .attr('aria-hidden', 'true');

            // --- jejak sinyal: jumlah beberapa kurva gauss ---
            const signal = (x) => this.peaks.reduce((sum, p) => {
                const d = (x - p.at) / (p.width ?? 0.018);
                return sum + p.height * Math.exp(-0.5 * d * d);
            }, 0);

            const samples = d3.range(0, 1.0005, 0.0015).map((t) => [
                t * width,
                baselineY - signal(t) * span,
            ]);

            const line = d3.line().curve(d3.curveBasis)(samples);

            // garis dasar
            svg.append('line')
                .attr('x1', 0).attr('x2', width)
                .attr('y1', baselineY).attr('y2', baselineY)
                .attr('stroke', '#D8C5A6')
                .attr('stroke-opacity', 0.28);

            const path = svg.append('path')
                .attr('d', line)
                .attr('fill', 'none')
                .attr('stroke', '#2A5DB0')
                .attr('stroke-width', 2)
                .attr('stroke-linecap', 'round');

            // --- label tiap puncak ---
            const labels = svg.append('g');

            this.peaks.forEach((p) => {
                const x    = p.at * width;
                const apex = baselineY - signal(p.at) * span;

                if (x < 64 || x > width - 64) return; // jangan terpotong tepi

                const g = labels.append('g').attr('opacity', animate ? 0 : 1);

                g.append('line')
                    .attr('x1', x).attr('x2', x)
                    .attr('y1', apex - 8)
                    .attr('y2', Math.max(16, apex - 26))
                    .attr('stroke', '#D8C5A6')
                    .attr('stroke-opacity', 0.45);

                g.append('text')
                    .attr('x', x)
                    .attr('y', Math.max(10, apex - 34))
                    .attr('text-anchor', 'middle')
                    .attr('fill', '#FFFBF2')
                    .attr('font-family', "'IBM Plex Sans Condensed', sans-serif")
                    .attr('font-size', 14)
                    .attr('font-weight', 600)
                    .text(p.reads);

                g.append('text')
                    .attr('x', x)
                    .attr('y', Math.max(24, apex - 20))
                    .attr('text-anchor', 'middle')
                    .attr('fill', '#D8C5A6')
                    .attr('fill-opacity', 0.75)
                    .attr('font-family', "'IBM Plex Mono', monospace")
                    .attr('font-size', 10.5)
                    .text(`${p.compound}  ${p.rt.toFixed(2)}`);

                if (animate) {
                    g.transition()
                        .delay(360 + p.at * 1500)
                        .duration(420)
                        .attr('opacity', 1);
                }
            });

            if (!animate) return;

            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduced) return;

            const len = path.node().getTotalLength();

            path.attr('stroke-dasharray', `${len} ${len}`)
                .attr('stroke-dashoffset', len)
                .transition()
                .duration(1900)
                .ease(d3.easeSinInOut)
                .attr('stroke-dashoffset', 0)
                .on('end', function () {
                    d3.select(this).attr('stroke-dasharray', null);
                });

            labels.selectAll('g').attr('opacity', 0);
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('chromatogram', chromatogram);
});
