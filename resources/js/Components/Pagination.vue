<script setup>
import { computed } from 'vue'

const props = defineProps({
    links: { type: Object, required: true },
})

const emit = defineEmits(['page-change'])

const pages = computed(() => {
    const total = props.links.last_page
    const current = props.links.current_page
    const range = []
    const delta = 2

    for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
            range.push(i)
        } else if (range[range.length - 1] !== '...') {
            range.push('...')
        }
    }

    return range
})

function goToPage(page) {
    if (page < 1 || page > props.links.last_page || page === props.links.current_page) return
    emit('page-change', page)
}
</script>

<template>
    <div
         class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-gray-100 text-sm text-gray-500">

        <span>
            Showing {{ (links.current_page - 1) * links.per_page + 1 }}–{{ Math.min(links.current_page * links.per_page, links.total) }} of {{ links.total }} results
        </span>

        <div class="flex items-center gap-1">
            <button v-if="links.current_page > 1"
                    @click="goToPage(links.current_page - 1)"
                    class="px-3 py-1.5 text-sm font-medium bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ti ti-chevron-left text-xs"></i>
            </button>
            <span v-else class="px-3 py-1.5 text-sm bg-gray-50 rounded-lg opacity-40 cursor-default">
                <i class="ti ti-chevron-left text-xs"></i>
            </span>

            <template v-for="page in pages" :key="page">
                <span v-if="page === '...'" class="px-1 text-gray-400">...</span>

                <span v-else-if="page === links.current_page"
                      class="px-3 py-1.5 text-sm font-medium bg-primary text-white rounded-lg">
                    {{ page }}
                </span>

                <button v-else
                        @click="goToPage(page)"
                        class="px-3 py-1.5 text-sm font-medium bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    {{ page }}
                </button>
            </template>

            <button v-if="links.current_page < links.last_page"
                    @click="goToPage(links.current_page + 1)"
                    class="px-3 py-1.5 text-sm font-medium bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ti ti-chevron-right text-xs"></i>
            </button>
            <span v-else class="px-3 py-1.5 text-sm bg-gray-50 rounded-lg opacity-40 cursor-default">
                <i class="ti ti-chevron-right text-xs"></i>
            </span>
        </div>
    </div>
</template>
