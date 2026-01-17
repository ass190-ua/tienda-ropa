import { defineStore } from 'pinia'
import axios from 'axios'

export const useWishlistStore = defineStore('wishlist', {
    state: () => ({
        items: [],      // <- array de Product (NO WishlistItem)
        loading: false,
        error: null,
        loaded: false,
    }),

    getters: {
        isInWishlist: (state) => (productId) =>
            state.items.some((p) => Number(p?.id) === Number(productId)),

        // Devuelve el primer productId del grupo que esté en wishlist
        findWishlistedProductId: (state) => (productIds = []) => {
            const set = new Set(state.items.map(p => Number(p?.id)))
            for (const id of productIds) {
                if (set.has(Number(id))) return Number(id)
            }
            return null
        },

        isGroupInWishlist: (state) => (productIds = []) => {
            const set = new Set(state.items.map(p => Number(p?.id)))
            for (const id of productIds) {
                if (set.has(Number(id))) return true
            }
            return false
        },
    },

    actions: {
        reset() {
            this.items = []
            this.loading = false
            this.error = null
            this.loaded = false
        },

        async fetchWishlist(force = false) {
            if (this.loaded && !force) return

            this.loading = true
            this.error = null
            try {
                const { data } = await axios.get('/api/wishlist')
                this.items = Array.isArray(data) ? data : []
                this.loaded = true
            } catch (e) {
                this.items = []
                this.loaded = false

                // Importante: si NO está logueado, NO lo tratamos como error
                if (e?.response?.status === 401) {
                    this.error = null
                    return
                }

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
            await this.fetchWishlist(true)
        },

        async remove(productId) {
            this.error = null
            await this.ensureCsrf()
            await axios.delete(`/api/wishlist/${productId}`)
            await this.fetchWishlist(true)
        },
        /**
+         * Toggle a nivel "prenda" (grupo).
+         * - Si cualquier variante del grupo está en wishlist -> elimina esa variante.
+         * - Si ninguna está -> añade una variante representativa (representativeId o primer productId del grupo).
+         *
+         * @param {number[]} productIds - ids de variantes del grupo
+         * @param {number|null} representativeId - id representativo para añadir (opcional)
+         */
        async toggleGroup(productIds = [], representativeId = null) {
            const ids = Array.isArray(productIds)
                ? productIds.map(Number).filter(n => Number.isFinite(n))
                : []

            const rep = Number(representativeId)
            const representative = Number.isFinite(rep) ? rep : (ids[0] ?? null)

            // Si ya hay alguna variante guardada, quitamos la primera que encontremos.
            const existingId = this.findWishlistedProductId(ids)
            if (existingId != null) {
                await this.remove(existingId)
                return { action: 'removed', product_id: existingId }
            }

            // Si no hay ninguna, añadimos una representativa.
            if (representative == null) return { action: 'noop', product_id: null }
            await this.add(representative)
            return { action: 'added', product_id: representative }
        },

        async clear() {
            // como items son Products -> id es el product_id correcto
            const ids = this.items.map(p => Number(p?.id)).filter(n => Number.isFinite(n))
            for (const id of ids) {
                await this.remove(id)
            }
        },
    },
})
