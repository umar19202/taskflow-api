<script setup>
import { computed } from 'vue'

const props = defineProps({
    iconSize: { type: [Number, String], default: 28 },
    showText: { type: Boolean, default: true },
})
</script>

<template>
    <span class="inline-flex items-center gap-2">
        <img src="/images/logo-icon.svg" alt="" :width="iconSize">
        <span v-if="showText" class="font-bold text-base text-gray-900">TaskFlow</span>
    </span>
</template>
