<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from './stores/auth';

const auth = useAuthStore();

onMounted(async () => {
    if (auth.token && !auth.user) {
        try {
            await auth.fetchProfile();
        } catch {
            auth.token = null;
            auth.user = null;
            localStorage.removeItem('token');
        }
    }
});
</script>
