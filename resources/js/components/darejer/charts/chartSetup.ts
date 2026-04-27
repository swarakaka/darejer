// Chart.js global registration. Imported once by every chart component
// so tree-shaking still drops unused controllers.
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    LineElement,
    PointElement,
    BarElement,
    CategoryScale,
    LinearScale,
    Filler,
} from 'chart.js'

ChartJS.register(
    Title, Tooltip, Legend, Filler,
    ArcElement, LineElement, PointElement, BarElement,
    CategoryScale, LinearScale,
)

export const brand = {
    primary:    '#2563eb',
    primarySoft:'rgba(37, 99, 235, 0.15)',
    success:    '#16a34a',
    successSoft:'rgba(22, 163, 74, 0.15)',
    warning:    '#d97706',
    warningSoft:'rgba(217, 119, 6, 0.18)',
    danger:     '#dc2626',
    dangerSoft: 'rgba(220, 38, 38, 0.15)',
    muted:      '#6b7280',
    mutedSoft:  'rgba(107, 114, 128, 0.15)',
    palette: ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#65a30d'],
}

export const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: true, position: 'bottom' as const, labels: { boxWidth: 10, padding: 12 } },
        tooltip: { backgroundColor: '#111827', padding: 8, cornerRadius: 4 },
    },
}
