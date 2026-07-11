<script setup>
import { computed } from 'vue'

const props = defineProps({
    groupedPermissions: Object, // { module: [{ name, label }] }
    modelValue: Array,          // selected permission names
    readonly: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const selected = computed({
    get: () => props.modelValue ?? [],
    set: (val) => emit('update:modelValue', val),
})

function isChecked(name) {
    return selected.value.includes(name)
}

function toggle(name) {
    if (props.readonly) return
    const next = isChecked(name)
        ? selected.value.filter(p => p !== name)
        : [...selected.value, name]
    selected.value = next
}

function modulePermissions(module) {
    return props.groupedPermissions[module] ?? []
}

function isModuleAllChecked(module) {
    return modulePermissions(module).every(p => isChecked(p.name))
}

function isModulePartialChecked(module) {
    const perms = modulePermissions(module)
    const count = perms.filter(p => isChecked(p.name)).length
    return count > 0 && count < perms.length
}

function toggleModule(module) {
    if (props.readonly) return
    const perms = modulePermissions(module).map(p => p.name)
    if (isModuleAllChecked(module)) {
        selected.value = selected.value.filter(p => !perms.includes(p))
    } else {
        const merged = new Set([...selected.value, ...perms])
        selected.value = [...merged]
    }
}

function moduleLabel(module) {
    return module.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function selectAll() {
    const all = Object.values(props.groupedPermissions).flat().map(p => p.name)
    selected.value = all
}

function clearAll() {
    selected.value = []
}
</script>

<template>
    <div>
        <!-- Bulk actions -->
        <div v-if="!readonly" class="flex items-center gap-3 mb-4 text-sm">
            <button type="button" @click="selectAll" class="text-primary hover:underline">Select all</button>
            <span class="text-gray-300">|</span>
            <button type="button" @click="clearAll" class="text-gray-500 hover:underline">Clear all</button>
            <span class="ml-auto text-gray-400">{{ selected.length }} selected</span>
        </div>

        <!-- Permission groups -->
        <div class="space-y-4">
            <div v-for="(perms, module) in groupedPermissions" :key="module"
                 class="border border-gray-200 rounded overflow-hidden">

                <!-- Module header -->
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <input v-if="!readonly"
                           type="checkbox"
                           class="rounded border-gray-300 accent-primary"
                           :checked="isModuleAllChecked(module)"
                           :indeterminate="isModulePartialChecked(module)"
                           @change="toggleModule(module)">
                    <span class="font-medium text-sm text-gray-700">{{ moduleLabel(module) }}</span>
                    <span class="ml-auto text-xs text-gray-400">
                        {{ perms.filter(p => isChecked(p.name)).length }}/{{ perms.length }}
                    </span>
                </div>

                <!-- Permission rows -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-0">
                    <label v-for="perm in perms" :key="perm.name"
                           :class="[
                               'flex items-center gap-2 px-4 py-2 text-sm border-b border-r border-gray-100',
                               !readonly ? 'cursor-pointer hover:bg-gray-50' : '',
                               isChecked(perm.name) ? 'bg-primary/5 text-primary' : 'text-gray-600',
                           ]">
                        <input type="checkbox"
                               class="rounded border-gray-300 accent-primary shrink-0"
                               :checked="isChecked(perm.name)"
                               :disabled="readonly"
                               @change="toggle(perm.name)">
                        {{ perm.label }}
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>
