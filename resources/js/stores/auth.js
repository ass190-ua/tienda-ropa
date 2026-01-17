import { defineStore } from 'pinia'
import axios from 'axios'
import { useCartStore } from './cart'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        initialized: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
    },

    actions: {
        // Al arrancar la app, solo pedimos el usuario.
        // Axios enviará la cookie automáticamente.
        async fetchUser() {
            try {
                console.log('[AUTH] Verificando sesión...');
                const { data, status } = await axios.get('/api/user')

                if (status === 204 || !data) {
                    this.user = null
                } else {
                    this.user = data
                    console.log('[AUTH] Usuario detectado:', data.name)
                }

                // Cargar carrito del usuario
                const cart = useCartStore()
                if (this.user) {
                    cart.setOwner(this.user.id)
                    try { await cart.pullFromBackend?.() } catch { }
                }

            } catch (error) {
                // Si da 401 es que no hay sesión, no pasa nada
                this.user = null
                const cart = useCartStore()
                cart.setOwner(null)
            } finally {
                this.initialized = true
            }
        },

        async login(credentials) {
            await axios.get('/sanctum/csrf-cookie')
            await axios.post('/api/login', credentials)
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
            const cart = useCartStore()
            cart.setOwner(null)
            window.location.href = '/login' // Recarga limpia para borrar cookies
        }
    }
})
