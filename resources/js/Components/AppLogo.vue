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
        <img v-if="showText" src="/images/logo.svg" alt="TaskFlow" height="18">
    </span>
</template>
