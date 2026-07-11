<script setup>
import { ref, watch } from 'vue'
import { useFlashStore } from '../stores/flash'

const flash = useFlashStore()
const visible = ref(false)
const current = ref({ type: null, message: null })
let timer = null

watch(
    () => [flash.message, flash.type],
    ([message, type]) => {
        if (!message) return

        current.value = { type, message }
        visible.value = true

        clearTimeout(timer)
        timer = setTimeout(() => { visible.value = false }, 4000)
    }
)

function dismiss() {
    visible.value = false
    clearTimeout(timer)
    flash.clear()
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div v-if="visible && current.message"
             class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm font-medium max-w-sm"
             :class="current.type === 'success'
                 ? 'bg-green-600 text-white'
                 : 'bg-red-600 text-white'">
            <i :class="current.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'" class="text-base shrink-0"></i>
            <span class="flex-1">{{ current.message }}</span>
            <button @click="dismiss" class="shrink-0 opacity-75 hover:opacity-100 ml-1">
                <i class="ti ti-x text-xs"></i>
            </button>
        </div>
    </Transition>
</template>
