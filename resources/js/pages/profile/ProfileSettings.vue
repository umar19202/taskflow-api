<template>
    <AppLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-normal mb-1">Profile Settings</h1>
            <p class="text-gray-500">Manage your account settings.</p>
        </div>

        <div class="max-w-lg">
            <div class="card">
                <div class="card-header">
                    <h4 class="text-base font-medium mb-0">Account Information</h4>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <label class="form-label">Name</label>
                        <p class="text-sm text-gray-800">{{ auth.user?.name }}</p>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Email</label>
                        <p class="text-sm text-gray-800">{{ auth.user?.email }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
</script>
