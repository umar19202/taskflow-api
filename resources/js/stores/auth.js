import { defineStore } from 'pinia';
import api from '../services/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        async login(credentials) {
            const { data } = await api.post('/auth/login', credentials);
            this.token = data.data.token;
            this.user = data.data.user;
            localStorage.setItem('token', this.token);
        },
        async register(payload) {
            const { data } = await api.post('/auth/register', payload);
            this.token = data.data.token;
            this.user = data.data.user;
            localStorage.setItem('token', this.token);
        },
        async fetchProfile() {
            const { data } = await api.get('/auth/profile');
            this.user = data.data;
        },
        async logout() {
            try {
                await api.post('/auth/logout');
            } finally {
                this.token = null;
                this.user = null;
                localStorage.removeItem('token');
            }
        },
    },
});
