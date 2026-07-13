<template>
    <div ref="bellRef" class="relative">
        <button @click="toggleDropdown" class="relative p-1.5 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none">
            <i class="ti ti-bell text-lg text-gray-600"></i>
            <span v-if="store.unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-primary text-white text-[10px] font-bold leading-none px-1 shadow-sm">
                {{ store.unreadCount > 99 ? '99+' : store.unreadCount }}
            </span>
        </button>

        <Teleport to="body">
            <div v-if="dropdownOpen" class="fixed inset-0 z-[1]" @click="dropdownOpen = false"></div>
        </Teleport>

        <div v-show="dropdownOpen" class="dropdown-menu dropdown-menu-md mt-1 absolute right-0 z-[1060] max-h-[420px] flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 shrink-0">
                <h4 class="text-sm font-semibold">Notifications</h4>
                <button v-if="store.unreadCount > 0" @click="markAllRead"
                    class="text-xs text-primary hover:text-primary/80 font-medium transition-colors cursor-pointer">
                    Mark all read
                </button>
            </div>

            <div class="overflow-y-auto flex-1">
                <div v-if="store.items.length === 0" class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <i class="ti ti-bell-off text-2xl mb-2 opacity-40"></i>
                    <p class="text-sm">No notifications yet</p>
                </div>

                <template v-else>
                    <div v-for="n in store.items" :key="n.id"
                        class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer border-b border-gray-50 last:border-0"
                        @click="handleClick(n)">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 mt-0.5"
                            :class="notifIcon(n).bg">
                            <i :class="['ti', notifIcon(n).icon, notifIcon(n).color]"></i>
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm text-gray-700 leading-snug" v-html="notifMessage(n)"></p>
                                <div class="flex items-center gap-1.5 shrink-0 mt-1">
                                    <span v-if="!n.read_at" class="w-2 h-2 rounded-full bg-primary shrink-0"></span>
                                    <span class="text-[11px] text-gray-400 whitespace-nowrap">{{ timeAgo(n.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '../stores/notifications'
import { useClickOutside } from '../composables/useClickOutside'

const store = useNotificationStore()
const router = useRouter()

const dropdownOpen = ref(false)
const bellRef = ref(null)

useClickOutside(bellRef, () => { dropdownOpen.value = false })

onMounted(() => {
    store.startPolling()
    store.startFocusListener()
})

onUnmounted(() => {
    store.stopPolling()
    store.stopFocusListener()
})

function toggleDropdown() {
    dropdownOpen.value = !dropdownOpen.value
    if (dropdownOpen.value) {
        store.fetchNotifications()
    }
}

function handleClick(n) {
    if (!n.read_at) {
        store.markRead(n.id)
    }
    const payload = typeof n.data === 'string' ? JSON.parse(n.data) : (n.data || {})
    if (payload.project_id) {
        router.push({ name: 'projects.show', params: { id: payload.project_id } })
    }
    dropdownOpen.value = false
}

function markAllRead() {
    store.markAllRead()
}

function notifIcon(n) {
    const type = n.type || ''
    const payload = typeof n.data === 'string' ? JSON.parse(n.data) : (n.data || {})
    if (type.includes('TaskAssigned') || payload.type === 'task_assigned') {
        return { icon: 'ti-user-check', color: 'text-primary', bg: 'bg-primary/10' }
    }
    if (type.includes('CommentPosted') || payload.type === 'comment_posted') {
        return { icon: 'ti-message', color: 'text-info', bg: 'bg-info/10' }
    }
    return { icon: 'ti-bell', color: 'text-gray-500', bg: 'bg-gray-100' }
}

function notifMessage(n) {
    const payload = typeof n.data === 'string' ? JSON.parse(n.data) : (n.data || {})
    return payload.message || 'You have a new notification'
}

function timeAgo(iso) {
    if (!iso) return ''
    const diff = Date.now() - new Date(iso).getTime()
    const mins = Math.floor(diff / 60000)
    if (mins < 1) return 'now'
    if (mins < 60) return `${mins}m`
    const hours = Math.floor(mins / 60)
    if (hours < 24) return `${hours}h`
    const days = Math.floor(hours / 24)
    if (days < 30) return `${days}d`
    return `${Math.floor(days / 30)}mo`
}
</script>
