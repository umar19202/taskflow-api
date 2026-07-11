<template>
    <div class="min-h-screen flex items-center justify-center px-4 bg-gray-50">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="p-10">
                    <div class="text-center mb-6">
                        <router-link to="/" class="inline-flex items-center mb-4">
                            <img src="/images/logo-icon.svg" alt="" width="36" />
                        </router-link>
                        <h1 class="text-base font-medium mt-4">Create your account</h1>
                    </div>

                    <form @submit.prevent="submit" class="mt-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                            <input v-model="form.name" type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{ 'border-red-500': errors.name }"
                                placeholder="Jane Doe" required autofocus>
                            <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                            <input v-model="form.email" type="email"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{ 'border-red-500': errors.email }"
                                placeholder="name@example.com" required>
                            <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email[0] }}</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input v-model="form.password" type="password"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{ 'border-red-500': errors.password }"
                                placeholder="Min. 8 characters" required>
                            <p v-if="errors.password" class="text-red-500 text-xs mt-1">{{ errors.password[0] }}</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                            <input v-model="form.password_confirmation" type="password"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Repeat password" required>
                        </div>

                        <button type="submit"
                            class="w-full bg-blue-600 text-white font-medium px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                            :disabled="loading">
                            {{ loading ? 'Creating account…' : 'Sign up' }}
                        </button>

                        <p v-if="apiError" class="text-red-500 text-sm text-center mt-3">{{ apiError }}</p>
                    </form>

                    <p class="text-center mt-6 text-sm text-gray-500">
                        Already have an account?
                        <router-link to="/login" class="text-blue-600 hover:underline">Sign in</router-link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const errors = ref({});
const apiError = ref('');
const loading = ref(false);

async function submit() {
    loading.value = true;
    errors.value = {};
    apiError.value = '';

    try {
        await auth.register(form);
        router.push({ name: 'dashboard' });
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};
        } else {
            apiError.value = error.response?.data?.message ?? 'Something went wrong.';
        }
    } finally {
        loading.value = false;
    }
}
</script>
