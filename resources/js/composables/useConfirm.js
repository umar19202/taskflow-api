import { reactive, computed } from 'vue'

const state = reactive({
    visible: false,
    title: '',
    message: '',
    danger: false,
    resolvePromise: null,
})

export function useConfirm() {
    function confirm(options = {}) {
        state.title = options.title || 'Are you sure?'
        state.message = options.message || ''
        state.danger = options.danger || false
        state.visible = true
        return new Promise((resolve) => {
            state.resolvePromise = resolve
        })
    }

    function accept() {
        state.visible = false
        state.resolvePromise?.(true)
    }

    function reject() {
        state.visible = false
        state.resolvePromise?.(false)
    }

    return {
        visible: computed(() => state.visible),
        title: computed(() => state.title),
        message: computed(() => state.message),
        danger: computed(() => state.danger),
        confirm,
        accept,
        reject,
    }
}
