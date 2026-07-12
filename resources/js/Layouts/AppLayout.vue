<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useClickOutside } from '../composables/useClickOutside'
import PageLoader from '../Components/PageLoader.vue'
import FlashMessage from '../Components/FlashMessage.vue'
import ConfirmModal from '../Components/ConfirmModal.vue'
import AppLogo from '../Components/AppLogo.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const sidebarCollapsed = ref(false)
const mobileSidebarOpen = ref(false)
const userMenuOpen = ref(false)
const userMenuRef = ref(null)

useClickOutside(userMenuRef, () => { userMenuOpen.value = false })

async function logout() {
    await auth.logout()
    router.push({ name: 'landing' })
}

const navLinks = [
    { href: '/dashboard', label: 'Dashboard', icon: 'ti-home' },
    { href: '/projects', label: 'Projects', icon: 'ti-layout-kanban' },
]

const isAdmin = computed(() => false)

function isActive(href) {
    return route.path.startsWith(href)
}

function toggleUserMenu() {
    userMenuOpen.value = !userMenuOpen.value
}

const userInitials = computed(() => {
    if (!auth.user?.name) return 'U'
    return auth.user.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
})
</script>

<template>
    <PageLoader />
    <FlashMessage />
    <ConfirmModal />

    <!-- Mobile overlay -->
    <div class="overlay" :class="{ show: mobileSidebarOpen }" @click="mobileSidebarOpen = false"></div>

    <!-- ── TOPBAR ── -->
    <nav class="topbar" :class="{ full: sidebarCollapsed }">
        <div class="flex items-center gap-2">
            <button class="btn btn-light btn-icon btn-sm hidden lg:inline-flex"
                    @click="sidebarCollapsed = !sidebarCollapsed">
                <i class="ti ti-layout-sidebar-left-expand"></i>
            </button>
            <button class="btn btn-light btn-icon btn-sm lg:hidden"
                    @click="mobileSidebarOpen = true">
                <i class="ti ti-layout-sidebar-left-expand"></i>
            </button>
        </div>

        <div class="flex items-center gap-1">
            <div ref="userMenuRef" class="relative">
                <button @click="toggleUserMenu" class="focus:outline-none">
                    <span v-if="auth.user?.avatar"
                          class="avatar avatar-sm rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold cursor-pointer">
                        <img :src="auth.user.avatar" :alt="auth.user?.name" class="rounded-full object-cover w-full h-full"/>
                    </span>
                    <span v-else
                          class="avatar avatar-sm rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold cursor-pointer">
                        {{ userInitials }}
                    </span>
                </button>
                <div v-show="userMenuOpen" class="dropdown-menu mt-1 p-0" style="min-width:200px;">
                    <div class="flex gap-3 items-center border-b border-dashed border-gray-200 px-4 py-3">
                        <span v-if="!auth.user?.avatar"
                              class="avatar avatar-md rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm font-semibold shrink-0">
                            {{ userInitials }}
                        </span>
                        <img v-else :src="auth.user.avatar" :alt="auth.user?.name"
                             class="avatar avatar-md rounded-full object-cover"/>
                        <div>
                            <h4 class="text-sm font-medium mb-0">{{ auth.user?.name }}</h4>
                            <p class="text-xs text-gray-500 mb-0">{{ auth.user?.email }}</p>
                        </div>
                    </div>
                    <div class="p-4 flex flex-col gap-1 text-sm leading-loose">
                        <router-link to="/profile" class="text-gray-700 hover:text-primary">
                            <i class="ti ti-user mr-2 text-gray-400"></i>My Profile
                        </router-link>
                        <hr class="my-1 border-gray-100">
                        <button @click="logout" class="text-left text-danger hover:opacity-80 cursor-pointer">
                            <i class="ti ti-logout mr-2"></i>Sign out
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar" :class="{ collapsed: sidebarCollapsed, 'mobile-show': mobileSidebarOpen }">
        <div class="logo-area">
            <router-link to="/dashboard">
                <AppLogo :icon-size="24" />
            </router-link>
        </div>

        <ul class="list-none m-0 p-0 mt-2">
            <li class="px-4 py-2">
                <small class="text-gray-400 text-xs font-medium uppercase tracking-wide">Main</small>
            </li>
            <li v-for="link in navLinks" :key="link.href">
                <router-link :to="link.href" class="sidebar-link"
                      :class="{ active: isActive(link.href) }"
                      @click="mobileSidebarOpen = false">
                    <i :class="['ti', link.icon]"></i>
                    <span class="nav-text">{{ link.label }}</span>
                </router-link>
            </li>

            <li class="px-4 pt-6 pb-2">
                <small class="text-gray-400 text-xs font-medium uppercase tracking-wide">Account</small>
            </li>
            <li>
                <button class="sidebar-link w-full text-left cursor-pointer" @click="logout">
                    <i class="ti ti-logout"></i>
                    <span class="nav-text">Sign out</span>
                </button>
            </li>
        </ul>
    </aside>

    <!-- ── CONTENT ── -->
    <main class="content" :class="{ full: sidebarCollapsed }">
        <div class="px-4 md:px-6">
            <slot />
        </div>
    </main>
</template>
