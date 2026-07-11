<script setup>
const props = defineProps({
    links: { type: Object, required: true },
})

const emit = defineEmits(['page-change'])

function goToUrl(url) {
    if (!url) return
    const params = new URLSearchParams(url.split('?')[1] ?? '')
    const page = params.get('page') || 1
    emit('page-change', Number(page))
}
</script>

<template>
    <div v-if="links.last_page > 1"
         class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-gray-100 text-sm text-gray-500">

        <span>
            Showing {{ links.from }}–{{ links.to }} of {{ links.total }} results
        </span>

        <div class="flex items-center gap-1">
            <button v-if="links.prev_page_url"
                    @click="goToUrl(links.prev_page_url)"
                    class="px-3 py-1.5 text-sm font-medium bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ti ti-chevron-left text-xs"></i>
            </button>
            <span v-else class="px-3 py-1.5 text-sm bg-gray-50 rounded-lg opacity-40 cursor-default">
                <i class="ti ti-chevron-left text-xs"></i>
            </span>

            <template v-for="link in links.links.slice(1, -1)" :key="link.label">
                <span v-if="link.label === '...'" class="px-1 text-gray-400">…</span>

                <span v-else-if="link.active"
                      class="px-3 py-1.5 text-sm font-medium bg-blue-600 text-white rounded-lg">
                    {{ link.label }}
                </span>

                <button v-else
                        @click="goToUrl(link.url)"
                        class="px-3 py-1.5 text-sm font-medium bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    {{ link.label }}
                </button>
            </template>

            <button v-if="links.next_page_url"
                    @click="goToUrl(links.next_page_url)"
                    class="px-3 py-1.5 text-sm font-medium bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ti ti-chevron-right text-xs"></i>
            </button>
            <span v-else class="px-3 py-1.5 text-sm bg-gray-50 rounded-lg opacity-40 cursor-default">
                <i class="ti ti-chevron-right text-xs"></i>
            </span>
        </div>
    </div>
</template>
