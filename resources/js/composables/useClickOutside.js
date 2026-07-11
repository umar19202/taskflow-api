import { onMounted, onUnmounted } from 'vue'

export function useClickOutside(targetRef, handler) {
    function onClick(event) {
        if (targetRef.value && !targetRef.value.contains(event.target)) {
            handler(event)
        }
    }

    onMounted(() => document.addEventListener('click', onClick))
    onUnmounted(() => document.removeEventListener('click', onClick))
}
