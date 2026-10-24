/**
 * Chart.js はグラフのあるページでだけ読み込む（他のページを軽くするため）。
 * 使う部品だけ登録して、読み込むコードを小さくする
 */
let chartClass = null;

async function loadChart() {
    if (!chartClass) {
        const { CategoryScale, Chart, LinearScale, LineController, LineElement, PointElement, Tooltip } = await import('chart.js');
        Chart.register(CategoryScale, LinearScale, LineController, LineElement, PointElement, Tooltip);
        chartClass = Chart;
    }

    return chartClass;
}

/**
 * 最高重量の推移グラフ（ユーザーページ）
 * series: [{ exercise_id, name, points: [{ date, weight }] }]
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('weightChart', (series) => {
        // Chart のインスタンスは Alpine のリアクティブなデータに入れない（Proxy で包まれると動作が壊れるため）
        let chart = null;

        return {
            series,
            exerciseId: series[0]?.exercise_id ?? null,

            init() {
                this.render();
                this.$watch('exerciseId', () => this.render());
            },

            destroy() {
                chart?.destroy();
            },

            async render() {
                const Chart = await loadChart();
                const current = this.series.find((s) => s.exercise_id === Number(this.exerciseId));
                chart?.destroy();

                if (!current) {
                    return;
                }

                const dark = document.documentElement.classList.contains('dark');
                const textColor = dark ? '#a1a1aa' : '#52525b';
                const gridColor = dark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.08)';

                chart = new Chart(this.$refs.canvas, {
                    type: 'line',
                    data: {
                        labels: current.points.map((p) => p.date.replaceAll('-', '/')),
                        datasets: [{
                            label: current.name,
                            data: current.points.map((p) => p.weight),
                            borderColor: '#f59e0b',
                            backgroundColor: '#f59e0b',
                            tension: 0.2,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            x: { ticks: { color: textColor }, grid: { color: gridColor } },
                            y: { ticks: { color: textColor, callback: (value) => `${value}kg` }, grid: { color: gridColor } },
                        },
                        plugins: {
                            tooltip: { callbacks: { label: (context) => `${context.parsed.y}kg` } },
                        },
                    },
                });
            },
        };
    });
});
