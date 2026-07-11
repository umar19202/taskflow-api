import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    { path: '/', name: 'landing', component: () => import('../pages/Landing.vue'), meta: { guest: true } },
    { path: '/login', name: 'login', component: () => import('../pages/auth/Login.vue'), meta: { guest: true } },
    { path: '/register', name: 'register', component: () => import('../pages/auth/Register.vue'), meta: { guest: true } },
    { path: '/dashboard', name: 'dashboard', component: () => import('../pages/Dashboard.vue'), meta: { requiresAuth: true } },
    { path: '/profile', name: 'profile', component: () => import('../pages/profile/ProfileSettings.vue'), meta: { requiresAuth: true } },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../pages/NotFound.vue') },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const auth = useAuthStore();
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        next({ name: 'landing' });
    } else if (to.meta.guest && auth.isAuthenticated) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
