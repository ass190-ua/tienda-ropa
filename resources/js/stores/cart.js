import { defineStore } from 'pinia'

function lineKey(productId, size, color) {
    return `${productId}__${size ?? ''}__${color ?? ''}`
}

const CART_KEY_BASE = 'tiendamoda_cart'

const LEGACY_KEYS = [
    CART_KEY_BASE,
    `${CART_KEY_BASE}_v1`,
    'urbano_cart',
    'urbano_cart_v1'
]

function storageKeyFor(userId) {
    return userId ? `${CART_KEY_BASE}__u${userId}` : `${CART_KEY_BASE}__guest`
}

function loadCart(key) {
    try {
        const raw = localStorage.getItem(key)
        if (!raw) return []
        const parsed = JSON.parse(raw)
        return Array.isArray(parsed) ? parsed : []
    } catch {
        return []
    }
}

function saveCart(key, items) {
    try {
        localStorage.setItem(key, JSON.stringify(items))
    } catch {
        // si falla storage, no rompemos la app
    }
}

export const useCartStore = defineStore('cart', {
    state: () => {
        const initialKey = storageKeyFor(null)
        let items = loadCart(initialKey)

        // ✅ Migración opcional: si el carrito guest nuevo está vacío,
        // intenta recuperar uno antiguo para no perderlo.
        if (items.length === 0) {
            for (const k of LEGACY_KEYS) {
                const legacyItems = loadCart(k)
                if (legacyItems.length) {
                    items = legacyItems
                    saveCart(initialKey, legacyItems)
                    try { localStorage.removeItem(k) } catch { }
                    break
                }
            }
        }

        return {
            userId: null,
            storageKey: initialKey,
            items, // [{ key, product, qty, size, color }]
        }
    },

    getters: {
        // compat: por si en algún sitio usas cart.cartItems
        cartItems: (s) => s.items,

        totalItems: (s) => s.items.reduce((acc, it) => acc + (it.qty ?? 1), 0),

        subtotal: (s) =>
            s.items.reduce((acc, it) => {
                const price = Number(it.product?.price ?? it.price ?? 0)
                return acc + price * Number(it.qty ?? 1)
            }, 0),

        isEmpty: (s) => s.items.length === 0,
    },

    actions: {
        // ✅ Cambia el “dueño” del carrito y recarga desde su storage
        setOwner(userId) {
            this.userId = userId ?? null
            this.storageKey = storageKeyFor(this.userId)
            this.items = loadCart(this.storageKey)
        },

        _persist() {
            saveCart(this.storageKey, this.items)
        },

        addToCart({ product, qty = 1, size = null, color = null }) {
            if (!product) return

            const productId = product.id ?? product.productId
            if (!productId) return

            const k = lineKey(productId, size, color)

            const existing = this.items.find((i) => i.key === k)
            if (existing) {
                existing.qty = Number(existing.qty ?? 1) + Number(qty ?? 1)
                this._persist()
                return
            }

            this.items.push({
                key: k,
                product,
                qty: Number(qty ?? 1),
                size,
                color,
            })

            this._persist()
        },

        removeItem(key) {
            this.items = this.items.filter((i) => i.key !== key)
            this._persist()
        },

        inc(key) {
            const it = this.items.find((i) => i.key === key)
            if (!it) return
            it.qty = Number(it.qty ?? 1) + 1
            this._persist()
        },

        dec(key) {
            const it = this.items.find((i) => i.key === key)
            if (!it) return

            const next = Number(it.qty ?? 1) - 1
            if (next <= 0) {
                this.removeItem(key)
                return
            }

            it.qty = next
            this._persist()
        },

        clear() {
            this.items = []
            this._persist()
        },

        // ✅ Aliases para que CartDrawer/Cart funcionen aunque llamen distinto
        increment(key) { return this.inc(key) },
        decrement(key) { return this.dec(key) },
        increaseQty(key) { return this.inc(key) },
        decreaseQty(key) { return this.dec(key) },
        clearCart() { return this.clear() },
        removeFromCart(key) { return this.removeItem(key) },
        removeLine(key) { return this.removeItem(key) },
    },
})
