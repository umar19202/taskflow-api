import { defineStore } from 'pinia'

export const useFlashStore = defineStore('flash', {
    state: () => ({
        message: null,
        type: null,
    }),
    actions: {
        success(message) {
            this.message = message
            this.type = 'success'
        },
        error(message) {
            this.message = message
            this.type = 'error'
        },
        clear() {
            this.message = null
            this.type = null
        },
    },
})
