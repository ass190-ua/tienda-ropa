import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
    },

    actions: {
        // Obtener el usuario actual (útil al recargar la página)
        async fetchUser() {
            try {
                const { data } = await axios.get('/api/user')
                this.user = data
            } catch (error) {
                this.user = null
            }
        },

        async login(credentials) {
            // 1. Pedir cookie CSRF (seguridad obligatoria de Laravel)
            await axios.get('/sanctum/csrf-cookie')
            // 2. Hacer login
            await axios.post('/api/login', credentials)
            // 3. Obtener datos del usuario
            await this.fetchUser()
        },

        async register(userData) {
            await axios.get('/sanctum/csrf-cookie')
            await axios.post('/api/register', userData)
            await this.fetchUser()
        },

        async logout() {
            await axios.post('/api/logout')
            this.user = null
        }
    }
})
