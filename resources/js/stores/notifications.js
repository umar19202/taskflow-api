import { defineStore } from 'pinia'
import api from '../services/api'

export const useNotificationStore = defineStore('notifications', {
    state: () => ({
        items: [],
        unreadCount: 0,
        loading: false,
        polling: null,
    }),

    actions: {
        async fetchNotifications() {
            this.loading = true
            try {
                const { data } = await api.get('/notifications')
                this.items = data.data
                this.unreadCount = data.data.filter(n => !n.read_at).length
            } catch {
                // silently fail
            } finally {
                this.loading = false
            }
        },

        async markRead(id) {
            try {
                await api.patch(`/notifications/${id}/read`)
                const n = this.items.find(n => n.id === id)
                if (n) {
                    n.read_at = new Date().toISOString()
                    this.unreadCount = Math.max(0, this.unreadCount - 1)
                }
            } catch {
                // silently fail
            }
        },

        async markAllRead() {
            try {
                await api.patch('/notifications/read-all')
                this.items.forEach(n => { n.read_at = new Date().toISOString() })
                this.unreadCount = 0
            } catch {
                // silently fail
            }
        },

        startPolling(interval = 10000) {
            this.stopPolling()
            this.fetchNotifications()
            this.polling = setInterval(() => this.fetchNotifications(), interval)
        },

        stopPolling() {
            if (this.polling) {
                clearInterval(this.polling)
                this.polling = null
            }
        },

        startFocusListener() {
            this.stopFocusListener()
            this._boundVisibilityChange = () => {
                if (document.visibilityState === 'visible') this.fetchNotifications()
            }
            this._boundFocus = () => this.fetchNotifications()
            window.addEventListener('visibilitychange', this._boundVisibilityChange)
            window.addEventListener('focus', this._boundFocus)
        },

        stopFocusListener() {
            if (this._boundVisibilityChange) {
                window.removeEventListener('visibilitychange', this._boundVisibilityChange)
            }
            if (this._boundFocus) {
                window.removeEventListener('focus', this._boundFocus)
            }
        },
    },
})
