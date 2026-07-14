<template>
    <AppLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-normal mb-1">Profile Settings</h1>
            <p class="text-gray-500">Manage your account settings and security.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="card text-center">
                    <div class="p-6">
                        <div class="mx-auto mb-3 flex items-center justify-center bg-primary/10 text-primary rounded-full" style="width:80px;height:80px">
                            <span class="text-2xl font-semibold">{{ initials }}</span>
                        </div>
                        <h4 class="text-base font-medium mb-0">{{ auth.user?.name }}</h4>
                        <p class="text-sm text-gray-500 mt-0.5">{{ auth.user?.email }}</p>
                        <p v-if="auth.user?.created_at" class="text-xs text-gray-400 mt-3">
                            <i class="ti ti-calendar mr-1"></i>
                            Member since {{ formattedDate }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-base font-medium mb-0">
                            <i class="ti ti-user mr-1.5"></i>
                            Account Information
                        </h4>
                    </div>
                    <form @submit.prevent="updateProfile">
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="form-label">Name</label>
                                <input v-model="form.name" type="text" class="form-control" :class="{ 'border-red-500': errors.name }" placeholder="Your full name" required>
                                <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                            </div>
                            <div>
                                <label class="form-label">Email</label>
                                <input v-model="form.email" type="email" class="form-control" :class="{ 'border-red-500': errors.email }" placeholder="your@email.com" required>
                                <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email[0] }}</p>
                            </div>
                        </div>
                        <div class="flex justify-end px-6 py-4 border-t border-gray-200">
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="saving">
                                {{ saving ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="text-base font-medium mb-0">
                            <i class="ti ti-lock mr-1.5"></i>
                            Change Password
                        </h4>
                    </div>
                    <form @submit.prevent="updatePassword">
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="form-label">Current Password</label>
                                <input v-model="passwordForm.current_password" type="password" class="form-control" :class="{ 'border-red-500': passwordErrors.current_password }" placeholder="Enter current password" required>
                                <p v-if="passwordErrors.current_password" class="text-red-500 text-xs mt-1">{{ passwordErrors.current_password[0] }}</p>
                            </div>
                            <div>
                                <label class="form-label">New Password</label>
                                <input v-model="passwordForm.password" type="password" class="form-control" :class="{ 'border-red-500': passwordErrors.password }" placeholder="Min. 8 characters" required>
                                <p v-if="passwordErrors.password" class="text-red-500 text-xs mt-1">{{ passwordErrors.password[0] }}</p>
                            </div>
                            <div>
                                <label class="form-label">Confirm New Password</label>
                                <input v-model="passwordForm.password_confirmation" type="password" class="form-control" :class="{ 'border-red-500': passwordErrors.password_confirmation }" placeholder="Re-enter new password" required>
                            </div>
                        </div>
                        <div class="flex justify-end px-6 py-4 border-t border-gray-200">
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="savingPassword">
                                {{ savingPassword ? 'Updating...' : 'Update Password' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import { useFlashStore } from '../../stores/flash'

const auth = useAuthStore()
const flash = useFlashStore()

const form = ref({ name: '', email: '' })
const errors = ref({})
const saving = ref(false)

const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' })
const passwordErrors = ref({})
const savingPassword = ref(false)

const initials = computed(() => {
    const name = auth.user?.name ?? ''
    const parts = name.split(' ').filter(Boolean)
    return parts.map(p => p[0]).join('').toUpperCase().slice(0, 2) || '?'
})

const formattedDate = computed(() => {
    if (!auth.user?.created_at) return ''
    const d = new Date(auth.user.created_at)
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
})

async function updateProfile() {
    saving.value = true
    errors.value = {}

    try {
        const { data } = await api.put('/auth/profile', form.value)
        auth.user = data.data
        flash.success('Profile updated successfully.')
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {}
        } else {
            flash.error('Something went wrong.')
        }
    } finally {
        saving.value = false
    }
}

async function updatePassword() {
    savingPassword.value = true
    passwordErrors.value = {}

    try {
        await api.put('/auth/profile', passwordForm.value)
        flash.success('Password updated successfully.')
        passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
    } catch (error) {
        if (error.response?.status === 422) {
            passwordErrors.value = error.response.data.errors ?? {}
        } else {
            flash.error('Something went wrong.')
        }
    } finally {
        savingPassword.value = false
    }
}

onMounted(() => {
    form.value.name = auth.user?.name ?? ''
    form.value.email = auth.user?.email ?? ''
})
</script>
