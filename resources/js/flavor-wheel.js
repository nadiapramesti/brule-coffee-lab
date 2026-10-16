import * as d3 from 'd3';

/**
 * Flavor Wheel - radar chart 6 sumbu.
 * Nilai tiap rasa 0-100, digambar sebagai poligon di atas grid melingkar.
 */
const AXES = [
    { key: 'citrus',    label: 'Jeruk' },
    { key: 'floral',    label: 'Bunga' },
    { key: 'berry',     label: 'Beri' },
    { key: 'chocolate', label: 'Cokelat' },
    { key: 'nutty',     label: 'Kacang' },
    { key: 'spice',     label: 'Rempah' },
];

const BLUE     = '#2A5DB0';
const KRAFT    = 'rgb(var(--c-muted))';
const LABWHITE = 'rgb(var(--c-fg))';

export default function flavorWheel(initialValues = {}, labels = {}) {
    // Label sumbu mengikuti bahasa halaman; nilai awalnya Indonesia.
    AXES.forEach((a) => { a.label = labels[a.key] ?? a.label; });

    return {
        values: { ...initialValues },
        svg: null,
        shape: null,
        dots: null,
        radius: 0,
        center: 0,

        init() {
            this.draw();

            // Livewire mengirim nilai baru tiap slider/kopi berubah.
            window.addEventListener('flavor-updated', (e) => {
                this.values = e.detail.values ?? e.detail[0]?.values ?? this.values;
                this.update();
            });

            window.addEventListener('resize', () => {
                d3.select(this.$refs.wheel).select('svg').remove();
                this.draw();
            });
        },

        // ---- geometri ----

        angle(i) {
            // mulai dari atas, searah jarum jam
            return (Math.PI * 2 * i) / AXES.length - Math.PI / 2;
        },

        point(i, value) {
            const r = (Math.max(0, Math.min(100, value)) / 100) * this.radius;
            return [
                this.center + r * Math.cos(this.angle(i)),
                this.center + r * Math.sin(this.angle(i)),
            ];
        },

        polygon(values) {
            return AXES
                .map((a, i) => this.point(i, values[a.key] ?? 0).join(','))
                .join(' ');
        },

        // ---- render ----

        draw() {
            const el   = this.$refs.wheel;
            if (!el) return;
            const size = Math.min(el.clientWidth || 420, 420);

            this.center = size / 2;
            this.radius = size / 2 - 44; // sisakan ruang buat label

            const svg = d3.select(el)
                .append('svg')
                .attr('viewBox', `0 0 ${size} ${size}`)
                .attr('width', '100%')
                .style('overflow', 'visible');

            const g = svg.append('g');

            // grid melingkar
            [20, 40, 60, 80, 100].forEach((lvl) => {
                g.append('polygon')
                    .attr('points', this.polygon(
                        Object.fromEntries(AXES.map((a) => [a.key, lvl]))
                    ))
                    .attr('fill', 'none')
                    .attr('stroke', KRAFT)
                    .attr('stroke-opacity', lvl === 100 ? 0.55 : 0.22)
                    .attr('stroke-width', 1);
            });

            // sumbu + label
            AXES.forEach((axis, i) => {
                const [x, y] = this.point(i, 100);

                g.append('line')
                    .attr('x1', this.center).attr('y1', this.center)
                    .attr('x2', x).attr('y2', y)
                    .attr('stroke', KRAFT)
                    .attr('stroke-opacity', 0.3);

                // Label ditaruh mengikuti arah sumbunya: yang di kanan
                // rata kiri, yang di kiri rata kanan. Kalau semuanya
                // dibuat rata tengah, tulisannya menimpa ujung sumbu.
                const [lx, ly] = this.point(i, 112);
                const atTop    = i === 0;
                const atBottom = i === 3;
                const onRight  = i === 1 || i === 2;

                const anchor = atTop || atBottom ? 'middle' : (onRight ? 'start' : 'end');
                const dx     = atTop || atBottom ? 0 : (onRight ? 10 : -10);
                const dy     = atTop ? -14 : (atBottom ? 12 : -4);

                g.append('text')
                    .attr('x', lx + dx)
                    .attr('y', ly + dy)
                    .attr('text-anchor', anchor)
                    .attr('dominant-baseline', 'middle')
                    .attr('fill', LABWHITE)
                    .attr('font-size', 13)
                    .attr('font-weight', 600)
                    .attr('font-family', "'IBM Plex Sans Condensed', sans-serif")
                    .text(axis.label);

                // Angkanya ikut ditulis: radar tanpa skala cuma bentuk,
                // dengan angka jadi bacaan alat ukur.
                g.append('text')
                    .attr('class', 'krm-axis-value')
                    .attr('data-key', axis.key)
                    .attr('x', lx + dx)
                    .attr('y', ly + dy + 15)
                    .attr('text-anchor', anchor)
                    .attr('dominant-baseline', 'middle')
                    .attr('fill', 'rgb(var(--c-bluetext))')
                    .attr('font-size', 11)
                    .attr('font-family', "'IBM Plex Mono', monospace")
                    .text(Math.round(this.values[axis.key] ?? 0));
            });

            // poligon rasa
            this.shape = g.append('polygon')
                .attr('points', this.polygon(this.values))
                .attr('fill', BLUE)
                .attr('fill-opacity', 0.28)
                .attr('stroke', BLUE)
                .attr('stroke-width', 2)
                .attr('stroke-linejoin', 'round');

            // titik di tiap sumbu
            this.dots = g.selectAll('circle.krm-dot')
                .data(AXES)
                .enter()
                .append('circle')
                .attr('class', 'krm-dot')
                .attr('r', 3.5)
                .attr('fill', BLUE)
                .attr('stroke', LABWHITE)
                .attr('stroke-width', 1.5)
                .attr('cx', (a, i) => this.point(i, this.values[a.key] ?? 0)[0])
                .attr('cy', (a, i) => this.point(i, this.values[a.key] ?? 0)[1]);

            this.svg = svg;
        },

        update() {
            if (!this.shape) return;

            this.shape.transition()
                .duration(420)
                .ease(d3.easeCubicOut)
                .attr('points', this.polygon(this.values));

            this.dots.transition()
                .duration(420)
                .ease(d3.easeCubicOut)
                .attr('cx', (a, i) => this.point(i, this.values[a.key] ?? 0)[0])
                .attr('cy', (a, i) => this.point(i, this.values[a.key] ?? 0)[1]);

            const self = this;
            this.svg.selectAll('text.krm-axis-value')
                .transition()
                .duration(420)
                .tween('angka', function () {
                    const key  = this.getAttribute('data-key');
                    const from = +this.textContent || 0;
                    const to   = Math.round(self.values[key] ?? 0);
                    const i    = d3.interpolateNumber(from, to);
                    return (t) => { this.textContent = Math.round(i(t)); };
                });
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('flavorWheel', flavorWheel);
});
