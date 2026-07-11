<template>
    <AppLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-normal mb-1">Dashboard</h1>
            <p class="text-gray-500">Welcome back! Here's what's happening.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="rounded-xl border bg-white border-gray-200 p-5">
                <p class="mb-1 text-xs text-gray-500 font-medium uppercase tracking-wide">Total Projects</p>
                <p class="font-bold text-xl mb-0">--</p>
            </div>
            <div class="rounded-xl border bg-white border-gray-200 p-5">
                <p class="mb-1 text-xs text-gray-500 font-medium uppercase tracking-wide">Tasks</p>
                <p class="font-bold text-xl mb-0">--</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../Layouts/AppLayout.vue';
</script>
