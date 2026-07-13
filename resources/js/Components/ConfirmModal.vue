<script setup>
import { watch } from 'vue'
import { useConfirm } from '../composables/useConfirm'

const { visible, title, message, danger, accept, reject } = useConfirm()

watch(visible, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="visible"
                 class="fixed inset-0 z-[1070] flex items-center justify-center p-4"
                 style="background: rgba(0,0,0,0.45)"
                 @click.self="reject">

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div v-if="visible" class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                                 :class="danger ? 'bg-red-50' : 'bg-amber-50'">
                                <i class="ti text-xl"
                                   :class="danger ? 'ti-alert-circle text-red-600' : 'ti-alert-triangle text-amber-600'"></i>
                            </div>
                            <h5 class="font-semibold text-base mb-0">{{ title }}</h5>
                        </div>

                        <p class="text-sm text-gray-600 mb-6">{{ message }}</p>

                        <div class="flex gap-2 justify-end">
                            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors" @click="reject">
                                Cancel
                            </button>
                            <button :class="['px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors', danger ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700']"
                                    @click="accept">
                                Confirm
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
