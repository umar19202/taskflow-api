<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLogo from './AppLogo.vue'

const visible = ref(false)
let timeout = null

function show() {
    timeout = setTimeout(() => { visible.value = true }, 80)
}

function hide() {
    clearTimeout(timeout)
    visible.value = false
}

let removeGuard = null

onMounted(() => {
    const router = useRouter()
    removeGuard = router.beforeEach((to, from) => {
        show()
    })
    router.afterEach(() => {
        hide()
    })
})

onUnmounted(() => {
    removeGuard?.()
    clearTimeout(timeout)
})

defineExpose({ show, hide })
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-150"
        leave-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        leave-to-class="opacity-0"
    >
        <div v-if="visible"
             class="fixed inset-0 z-9999 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm">

            <div class="mb-6 opacity-80">
                <AppLogo :icon-size="28" />
            </div>

            <div class="relative w-12 h-12">
                <svg class="w-12 h-12 -rotate-90" viewBox="0 0 48 48" fill="none">
                    <circle cx="24" cy="24" r="20" stroke="#E66239" stroke-width="3" stroke-opacity="0.12" />
                </svg>
                <svg class="w-12 h-12 -rotate-90 absolute inset-0 animate-spin" viewBox="0 0 48 48" fill="none">
                    <circle cx="24" cy="24" r="20"
                            stroke="#E66239" stroke-width="3"
                            stroke-linecap="round"
                            stroke-dasharray="125.66"
                            stroke-dashoffset="94.25" />
                </svg>
            </div>

            <p class="mt-4 text-xs text-gray-400 tracking-wider uppercase">Loading…</p>
        </div>
    </Transition>
</template>
