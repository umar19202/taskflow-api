<template>
    <AppLayout>
        <div>
            <div class="mb-6">
                <router-link to="/projects" class="text-sm text-gray-500 hover:text-primary inline-flex items-center gap-1 mb-2">
                    <i class="ti ti-arrow-left"></i>
                    Back to Projects
                </router-link>
                <h1 class="text-2xl font-normal mb-1">{{ isEdit ? 'Edit Project' : 'New Project' }}</h1>
                <p class="text-gray-500">{{ isEdit ? 'Update your project details.' : 'Create a new workspace for your tasks.' }}</p>
            </div>

            <div v-if="loading" class="flex justify-center py-8">
                <Spinner size="lg" color="#E66239" />
            </div>

            <div v-else class="card">
                <form @submit.prevent="submit">
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="form-label">Project name</label>
                            <input v-model="form.name" type="text"
                                class="form-control"
                                :class="{ 'border-red-500': errors.name }"
                                placeholder="My Awesome Project" required>
                            <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Description</label>
                            <textarea v-model="form.description"
                                class="form-control"
                                :class="{ 'border-red-500': errors.description }"
                                placeholder="Optional description..."
                                rows="4"></textarea>
                            <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description[0] }}</p>
                        </div>

                        <div v-if="isEdit" class="mb-4">
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-select">
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                        <button type="button" @click="$router.push('/projects')" class="btn btn-light btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" :disabled="saving">
                            {{ saving ? 'Saving...' : (isEdit ? 'Update Project' : 'Create Project') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '../../Layouts/AppLayout.vue'
import Spinner from '../../Components/Spinner.vue'
import api from '../../services/api'
import { useFlashStore } from '../../stores/flash'

const route = useRoute()
const router = useRouter()
const flash = useFlashStore()

const isEdit = computed(() => !!route.params.id)

const form = ref({
    name: '',
    description: '',
    status: 'active',
})

const errors = ref({})
const saving = ref(false)
const loading = ref(false)

async function submit() {
    saving.value = true
    errors.value = {}

    try {
        if (isEdit.value) {
            await api.put(`/projects/${route.params.id}`, form.value)
            flash.success('Project updated successfully.')
        } else {
            await api.post('/projects', form.value)
            flash.success('Project created successfully.')
        }
        setTimeout(() => router.push({ name: 'projects.index' }), 500)
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

onMounted(async () => {
    if (isEdit.value) {
        loading.value = true
        const { data } = await api.get(`/projects/${route.params.id}`)
        form.value.name = data.data.name
        form.value.description = data.data.description ?? ''
        form.value.status = data.data.status
        loading.value = false
    }
})
</script>
