import { defineStore } from 'pinia'
import axios from 'axios'

export const useWishlistStore = defineStore('wishlist', {
    state: () => ({
        items: [],
        loading: false,
        error: null,
    }),

    getters: {
        isInWishlist: (state) => (productId) =>
            state.items.some((p) => Number(p.id) === Number(productId)),
    },

    actions: {
        async fetchWishlist() {
            this.loading = true
            this.error = null
            try {
                const { data } = await axios.get('/api/wishlist')
                this.items = Array.isArray(data) ? data : []
            } catch (e) {
                this.items = []
                this.error = 'No se pudo cargar la wishlist'
                throw e
            } finally {
                this.loading = false
            }
        },

        async ensureCsrf() {
            await axios.get('/sanctum/csrf-cookie')
        },

        async add(productId) {
            this.error = null
            await this.ensureCsrf()
            await axios.post('/api/wishlist', { product_id: productId })
            await this.fetchWishlist()
        },

        async remove(productId) {
            this.error = null
            await this.ensureCsrf()
            await axios.delete(`/api/wishlist/${productId}`)
            await this.fetchWishlist()
        },

        async clear() {
            const ids = this.items.map(p => p.id)
            for (const id of ids) {
                await this.remove(id)
            }
        }

    },
})
