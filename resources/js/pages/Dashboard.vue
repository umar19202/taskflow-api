<template>
    <AppLayout>
        <div v-if="loading" class="flex justify-center py-12">
            <Spinner size="lg" color="#E66239" />
        </div>

        <template v-else>
            <div class="relative mb-8">
                <div class="absolute -top-6 -left-6 w-48 h-48 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -top-4 -right-4 w-36 h-36 bg-info/5 rounded-full blur-3xl pointer-events-none"></div>
                <div class="animate-fade-in">
                    <h1 class="text-xl md:text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">Dashboard</h1>
                    <p class="text-gray-500 mt-1 text-sm">Here's what's happening across your workspace.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div v-for="(card, idx) in statCards" :key="idx"
                    class="relative rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg group animate-slide-up"
                    :class="card.bg"
                    :style="{ animationDelay: idx * 0.08 + 's' }"
                >
                    <div class="absolute -top-6 -right-6 w-20 h-20 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
                    <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-white/5 rounded-full blur-lg pointer-events-none"></div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm">
                            <i :class="card.icon" class="text-white text-base"></i>
                        </span>
                        <span v-if="card.badge !== undefined" class="text-xs font-medium text-white/70 bg-white/10 px-2 py-0.5 rounded-full">{{ card.badge }}</span>
                    </div>
                    <p class="text-2xl md:text-3xl font-bold text-white mb-0.5 tracking-tight animate-number" :style="{ animationDelay: (idx * 0.08 + 0.15) + 's' }">{{ card.value }}</p>
                    <p class="text-xs font-medium text-white/70 uppercase tracking-wider">{{ card.label }}</p>
                </div>
            </div>

            <div class="relative rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm mb-6 animate-slide-up" style="animation-delay: 0.35s">
                <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-info via-purple-400 to-transparent animate-shimmer"></div>
                <div class="px-5 py-3 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-7 h-7 rounded-lg bg-info/10 flex items-center justify-center">
                            <i class="ti ti-trending-up text-info text-xs"></i>
                        </span>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">Task Flow</h4>
                            <p class="text-[11px] text-gray-400">Status distribution across all projects</p>
                        </div>
                    </div>
                </div>
                <div v-if="statusTotal === 0" class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <i class="ti ti-inbox text-3xl mb-2 opacity-40"></i>
                    <p class="text-sm">No tasks yet.</p>
                </div>
                <div v-else class="p-4">
                    <svg :viewBox="`0 0 ${chart.width} ${chart.height}`" class="w-full h-auto" preserveAspectRatio="xMidYMid meet">
                        <defs>
                            <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.2" />
                                <stop offset="100%" stop-color="#3B82F6" stop-opacity="0.02" />
                            </linearGradient>
                            <linearGradient id="lineGrad" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#E66239" />
                                <stop offset="50%" stop-color="#3B82F6" />
                                <stop offset="100%" stop-color="#10B981" />
                            </linearGradient>
                            <filter id="dotShadow">
                                <feDropShadow dx="0" dy="1" stdDeviation="2" flood-color="#3B82F6" flood-opacity="0.3" />
                            </filter>
                        </defs>

                        <line v-for="n in 3" :key="'grid-' + n"
                            :x1="chart.pad.left" :y1="chart.pad.top + (chart.innerH / 3) * n"
                            :x2="chart.width - chart.pad.right" :y2="chart.pad.top + (chart.innerH / 3) * n"
                            stroke="#F3F4F6" stroke-width="1" stroke-dasharray="3,3"
                        />

                        <path :d="chart.areaPath" fill="url(#areaGrad)" opacity="0" class="animate-fade-in" style="animation-delay: 0.6s; animation-fill-mode: forwards" />

                        <path ref="lineRef" :d="chart.linePath" fill="none" stroke="url(#lineGrad)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                            :stroke-dasharray="lineLength" :stroke-dashoffset="lineOffset"
                            class="transition-all duration-1000 ease-out"
                        />

                        <g v-for="(p, i) in chart.points" :key="i" class="animate-fade-in" :style="{ animationDelay: (0.6 + i * 0.1) + 's' }">
                            <circle :cx="p.x" :cy="p.y" r="4" fill="white"
                                :stroke="statusColor(p.label)" stroke-width="2"
                                filter="url(#dotShadow)"
                                class="animate-pulse-dot"
                            />
                            <text :x="p.x" :y="p.y - 11" text-anchor="middle" class="chart-value">{{ p.value }}</text>
                        </g>

                        <text v-for="(p, i) in chart.points" :key="'lbl-' + i"
                            :x="p.x" y="158"
                            :text-anchor="i === 0 ? 'start' : (i === chart.points.length - 1 ? 'end' : 'middle')"
                            class="chart-label"
                            :style="{ animationDelay: (0.8 + i * 0.08) + 's' }"
                        >{{ labelMap[p.label] }}</text>
                    </svg>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="relative rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm animate-slide-up" style="animation-delay: 0.45s">
                    <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-warning via-warning/60 to-transparent animate-shimmer"></div>
                    <div class="px-5 py-3 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-warning/10 flex items-center justify-center">
                                <i class="ti ti-flag text-warning text-xs"></i>
                            </span>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">By Priority</h4>
                                <p class="text-[11px] text-gray-400">Urgency levels</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div v-for="{ key, count } in priorityList" :key="key"
                            class="relative p-3.5 rounded-xl overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                            :class="count === 0 ? 'opacity-50' : ''"
                            :style="{ backgroundColor: priorityBg(key) }"
                        >
                            <div class="absolute -bottom-2 -right-2 w-10 h-10 rounded-full pointer-events-none" :style="{ backgroundColor: priorityColor(key) + '15' }"></div>
                            <div class="relative z-10">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: priorityColor(key) }"></span>
                                    <span class="text-[11px] font-semibold uppercase tracking-wider" :style="{ color: priorityColor(key) }">{{ labelMap[key] }}</span>
                                </div>
                                <p class="text-xl font-bold text-gray-900">{{ count }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5">
                                    {{ priorityTotal > 0 ? Math.round(count / priorityTotal * 100) : 0 }}%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm animate-slide-up" style="animation-delay: 0.55s">
                    <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-gray-400 via-gray-300 to-transparent animate-shimmer"></div>
                    <div class="px-4 py-3 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <i class="ti ti-clock text-gray-500 text-xs"></i>
                                </span>
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-900">Recent Tasks</h4>
                                    <p class="text-[11px] text-gray-400 leading-tight">Latest activity</p>
                                </div>
                            </div>
                            <router-link to="/projects" class="text-[11px] font-medium text-primary hover:text-primary/80 transition-colors flex items-center gap-1">
                                All Projects
                                <i class="ti ti-arrow-right text-[11px]"></i>
                            </router-link>
                        </div>
                    </div>

                    <div v-if="stats.recent_tasks.length === 0" class="flex flex-col items-center justify-center py-8 text-gray-400">
                        <i class="ti ti-inbox text-2xl mb-1.5 opacity-40"></i>
                        <p class="text-xs">No tasks yet.</p>
                        <router-link to="/projects" class="text-primary hover:underline text-[11px] mt-1">Create a project to get started</router-link>
                    </div>

                    <div v-else class="overflow-x-auto -mx-4 px-4">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-50">
                                    <th class="px-4 py-2 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wider">Task</th>
                                    <th class="px-4 py-2 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wider hidden sm:table-cell">Project</th>
                                    <th class="px-4 py-2 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wider hidden md:table-cell">Assignee</th>
                                    <th class="px-4 py-2 text-right text-[11px] font-medium text-gray-400 uppercase tracking-wider hidden lg:table-cell">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(task, i) in stats.recent_tasks" :key="task.id"
                                    class="hover:bg-gray-50/50 transition-colors animate-fade-in"
                                    :style="{ animationDelay: (0.6 + i * 0.06) + 's', animationFillMode: 'backwards' }"
                                >
                                    <td class="px-4 py-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ backgroundColor: priorityColor(task.priority) }"></span>
                                            <span class="text-xs font-medium text-gray-800 truncate max-w-[140px] lg:max-w-[200px]">{{ task.title }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-500 hidden sm:table-cell">{{ task.project_name }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full"
                                            :style="{ backgroundColor: statusColor(task.status) + '15', color: statusColor(task.status) }"
                                        >
                                            <span class="w-1 h-1 rounded-full" :style="{ backgroundColor: statusColor(task.status) }"></span>
                                            {{ statusLabel(task.status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 hidden md:table-cell">
                                        <div v-if="task.assignee_name" class="flex items-center gap-1.5">
                                            <span class="w-5 h-5 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[11px] font-semibold shrink-0">
                                                {{ initials(task.assignee_name) }}
                                            </span>
                                            <span class="text-xs text-gray-600 truncate max-w-[80px]">{{ task.assignee_name }}</span>
                                        </div>
                                        <span v-else class="text-xs text-gray-400 italic">Unassigned</span>
                                    </td>
                                    <td class="px-4 py-2 text-[11px] text-gray-400 text-right hidden lg:table-cell whitespace-nowrap">{{ timeAgo(task.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import AppLayout from '../Layouts/AppLayout.vue'
import Spinner from '../Components/Spinner.vue'
import api from '../services/api'

const loading = ref(true)
const lineRef = ref(null)
const lineLength = ref(0)
const lineOffset = ref(0)

const stats = ref({
    total_projects: 0,
    total_tasks: 0,
    active_tasks: 0,
    overdue_tasks: 0,
    tasks_by_project: [],
    tasks_by_status: {},
    tasks_by_priority: {},
})

const labelMap = {
    open: 'Open',
    in_progress: 'In Progress',
    in_review: 'In Review',
    done: 'Done',
    cancelled: 'Cancelled',
    low: 'Low',
    medium: 'Medium',
    high: 'High',
    urgent: 'Urgent',
}

const statusColor = (s) => ({
    open: '#3B82F6',
    in_progress: '#E66239',
    in_review: '#F59E0B',
    done: '#10B981',
    cancelled: '#EF4444',
}[s] ?? '#9CA3AF')

const priorityColor = (p) => ({
    low: '#3B82F6',
    medium: '#E66239',
    high: '#F59E0B',
    urgent: '#EF4444',
}[p] ?? '#9CA3AF')

const priorityBg = (p) => ({
    low: '#EFF6FF',
    medium: '#FFF1EE',
    high: '#FFFBEB',
    urgent: '#FEF2F2',
}[p] ?? '#F9FAFB')

const statCards = computed(() => [
    { label: 'Projects', value: stats.value.total_projects, icon: 'ti ti-layout-kanban', bg: 'bg-gradient-to-br from-primary to-orange-500', badge: undefined },
    { label: 'All Tasks', value: stats.value.total_tasks, icon: 'ti ti-list-check', bg: 'bg-gradient-to-br from-info to-blue-600', badge: undefined },
    { label: 'Active', value: stats.value.active_tasks, icon: 'ti ti-player-play', bg: 'bg-gradient-to-br from-success to-emerald-600', badge: stats.value.total_tasks ? Math.round(stats.value.active_tasks / stats.value.total_tasks * 100) + '%' : '0%' },
    { label: 'Overdue', value: stats.value.overdue_tasks, icon: 'ti ti-alert-triangle', bg: 'bg-gradient-to-br from-danger to-rose-600', badge: stats.value.active_tasks && stats.value.overdue_tasks ? Math.round(stats.value.overdue_tasks / stats.value.active_tasks * 100) + '% of active' : '0%' },
])

const priorityOrder = ['urgent', 'high', 'medium', 'low']
const statusOrder = ['open', 'in_progress', 'in_review', 'done', 'cancelled']

const statusTotal = computed(() => Object.values(stats.value.tasks_by_status).reduce((a, b) => a + b, 0))
const priorityTotal = computed(() => Object.values(stats.value.tasks_by_priority).reduce((a, b) => a + b, 0))

const priorityList = computed(() =>
    priorityOrder.map(key => ({
        key,
        count: stats.value.tasks_by_priority[key] ?? 0,
    }))
)

const chart = computed(() => {
    const data = statusOrder.map(s => stats.value.tasks_by_status[s] ?? 0)
    const max = Math.max(...data, 1)

    const width = 800
    const height = 180
    const pad = { top: 20, right: 50, bottom: 35, left: 40 }
    const innerW = width - pad.left - pad.right
    const innerH = height - pad.top - pad.bottom

    const points = data.map((v, i) => ({
        x: pad.left + (i / Math.max(data.length - 1, 1)) * innerW,
        y: pad.top + innerH - (v / max) * innerH,
        value: v,
        label: statusOrder[i],
    }))

    function smoothPoints(pts) {
        if (pts.length < 2) return ''
        let d = `M${pts[0].x} ${pts[0].y}`
        for (let i = 0; i < pts.length - 1; i++) {
            const p0 = pts[Math.max(i - 1, 0)]
            const p1 = pts[i]
            const p2 = pts[i + 1]
            const p3 = pts[Math.min(i + 2, pts.length - 1)]
            const cp1x = p1.x + (p2.x - p0.x) / 6
            const cp1y = p1.y + (p2.y - p0.y) / 6
            const cp2x = p2.x - (p3.x - p1.x) / 6
            const cp2y = p2.y - (p3.y - p1.y) / 6
            d += ` C${cp1x.toFixed(1)} ${cp1y.toFixed(1)}, ${cp2x.toFixed(1)} ${cp2y.toFixed(1)}, ${p2.x.toFixed(1)} ${p2.y.toFixed(1)}`
        }
        return d
    }

    const linePath = smoothPoints(points)
    const last = points[points.length - 1]
    const first = points[0]
    const areaPath = linePath + ` L${last.x} ${pad.top + innerH} L${first.x} ${pad.top + innerH} Z`

    return { points, linePath, areaPath, max, width, height, pad, innerW, innerH }
})

function statusLabel(status) {
    return labelMap[status] ?? status
}

function initials(name) {
    if (!name) return '?'
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

function timeAgo(iso) {
    if (!iso) return ''
    const diff = Date.now() - new Date(iso).getTime()
    const mins = Math.floor(diff / 60000)
    if (mins < 1) return 'just now'
    if (mins < 60) return `${mins}m ago`
    const hours = Math.floor(mins / 60)
    if (hours < 24) return `${hours}h ago`
    const days = Math.floor(hours / 24)
    return `${days}d ago`
}

onMounted(async () => {
    try {
        const { data } = await api.get('/dashboard/stats')
        stats.value = { ...stats.value, ...data.data }
    } finally {
        loading.value = false
        await nextTick()
        await nextTick()
        if (lineRef.value) {
                const len = lineRef.value.getTotalLength()
                lineLength.value = len
                lineOffset.value = len
                await nextTick()
                requestAnimationFrame(() => {
                    lineOffset.value = 0
                })
            }
    }
})
</script>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pulseDot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
@keyframes numberPop {
    0% { transform: scale(0.5); opacity: 0; }
    60% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}
.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}
.animate-slide-up {
    animation: slideUp 0.5s ease-out forwards;
}
.animate-pulse-dot {
    animation: pulseDot 2s ease-in-out infinite;
}
.animate-number {
    animation: numberPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
}
.animate-shimmer {
    background-size: 200% 100%;
    animation: shimmer 3s linear infinite;
}
.chart-value {
    font-size: 11px;
    font-weight: 700;
    fill: #1F2937;
    animation: fadeIn 0.4s ease-out forwards;
}
.chart-label {
    font-size: 10px;
    font-weight: 500;
    fill: #6B7280;
    opacity: 0;
    animation: fadeIn 0.4s ease-out forwards;
}
</style>
