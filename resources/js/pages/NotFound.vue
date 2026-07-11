<script setup>
import AppLogo from '../Components/AppLogo.vue'
</script>

<template>
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md text-center">
            <div class="mb-6">
                <router-link to="/" class="inline-flex items-center mb-4">
                    <AppLogo :icon-size="36" />
                </router-link>
            </div>

            <h1 class="text-8xl font-bold text-primary mb-2">404</h1>
            <h2 class="text-xl font-medium mb-3">Page Not Found</h2>
            <p class="text-gray-500 mb-6">Sorry, the page you're looking for doesn't exist or has been moved.</p>

            <router-link to="/dashboard" class="btn btn-primary">Go to Dashboard</router-link>
        </div>
    </div>
</template>
