<template>
    <Teleport to="body">
        <div v-if="show" class="overlay show" @click.self="$emit('close')">
            <div class="fixed inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4" @click.stop>
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-medium">{{ isEdit ? 'Edit Task' : 'New Task' }}</h3>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-lg leading-none">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="p-6 space-y-4 relative">
                            <div v-if="loadingUsers" class="absolute inset-0 bg-white/80 flex items-center justify-center z-10 rounded">
                                <svg class="animate-spin h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </div>

                            <div>
                                <label class="form-label">Title</label>
                                <input v-model="form.title" type="text"
                                    class="form-control"
                                    :class="{ 'border-red-500': errors.title }"
                                    placeholder="Task title" required>
                                <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
                            </div>

                            <div>
                                <label class="form-label">Description</label>
                                <textarea v-model="form.description"
                                    class="form-control"
                                    :class="{ 'border-red-500': errors.description }"
                                    placeholder="Optional description..."
                                    rows="3"></textarea>
                                <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description[0] }}</p>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="form-label">Priority</label>
                                    <select v-model="form.priority" class="form-select">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label class="form-label">Due Date</label>
                                    <input v-model="form.due_date" type="date" class="form-control" :min="isEdit ? undefined : today">
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Assign To</label>
                                <select v-model="form.assigned_to" class="form-select">
                                    <option :value="null">Unassigned</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                            </div>

                            <div v-if="isEdit">
                                <label class="form-label">Status</label>
                                <select v-model="form.status" class="form-select">
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="in_review">In Review</option>
                                    <option value="done">Done</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                            <button type="button" @click="$emit('close')" class="btn btn-light btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="saving">
                                {{ saving ? 'Saving...' : (isEdit ? 'Update Task' : 'Create Task') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import api from '../services/api'
import { useFlashStore } from '../stores/flash'

const props = defineProps({
    show: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
    task: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])
const flash = useFlashStore()

const isEdit = computed(() => !!props.task)

const form = ref({
    title: '',
    description: '',
    priority: 'medium',
    due_date: '',
    assigned_to: null,
    status: 'open',
})

const errors = ref({})
const saving = ref(false)
const users = ref([])
const loadingUsers = ref(false)
const today = new Date().toISOString().split('T')[0]

watch(() => props.show, async (val) => {
    if (val) {
        loadingUsers.value = true
        errors.value = {}

        if (props.task) {
            form.value = {
                title: props.task.title,
                description: props.task.description ?? '',
                priority: props.task.priority,
                due_date: props.task.due_date ?? '',
                assigned_to: props.task.assignee?.id ?? null,
                status: props.task.status.value,
            }
        } else {
            form.value = { title: '', description: '', priority: 'medium', due_date: '', assigned_to: null, status: 'open' }
        }

        const { data } = await api.get('/users')
        users.value = data.data
        loadingUsers.value = false
    }
})

async function submit() {
    saving.value = true
    errors.value = {}

    try {
        const payload = { ...form.value }
        if (!payload.due_date) delete payload.due_date
        if (!payload.description) payload.description = null
        if (!payload.assigned_to) payload.assigned_to = null

        if (isEdit.value) {
            await api.put(`/tasks/${props.task.id}`, payload)
            flash.success('Task updated successfully.')
        } else {
            await api.post(`/projects/${props.projectId}/tasks`, payload)
            flash.success('Task created successfully.')
        }

        emit('saved')
        emit('close')
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
</script>
