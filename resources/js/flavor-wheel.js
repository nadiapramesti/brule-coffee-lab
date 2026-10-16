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
const KRAFT    = '#D8C5A6';
const LABWHITE = '#FFFBF2';

export default function flavorWheel(initialValues = {}) {
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
            const size = Math.min(el.clientWidth || 420, 460);

            this.center = size / 2;
            this.radius = size / 2 - 46; // sisakan ruang buat label

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

                const [lx, ly] = this.point(i, 122);

                g.append('text')
                    .attr('x', lx)
                    .attr('y', ly)
                    .attr('text-anchor', 'middle')
                    .attr('dominant-baseline', 'middle')
                    .attr('fill', LABWHITE)
                    .attr('font-size', 13)
                    .attr('font-weight', 600)
                    .attr('font-family', "'IBM Plex Sans Condensed', sans-serif")
                    .text(axis.label);
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
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('flavorWheel', flavorWheel);
});
