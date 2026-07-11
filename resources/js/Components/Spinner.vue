<script setup>
defineProps({
    size: { type: String, default: 'sm' }, // sm | md | lg
    color: { type: String, default: 'currentColor' },
})
</script>

<template>
    <svg
        :class="{
            'w-4 h-4': size === 'sm',
            'w-5 h-5': size === 'md',
            'w-6 h-6': size === 'lg',
        }"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        class="animate-spin shrink-0"
        aria-hidden="true"
    >
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.2" />
        <path d="M12 2a10 10 0 0 1 10 10" :stroke="color" stroke-width="3" stroke-linecap="round" />
    </svg>
</template>
