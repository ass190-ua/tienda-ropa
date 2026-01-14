import { defineStore } from 'pinia'

function mergeItemLists(baseItems, incomingItems) {
    const map = new Map()

    for (const it of (baseItems ?? [])) {
        if (!it?.key) continue
        map.set(it.key, { ...it, qty: Number(it.qty ?? 1) })
    }

    for (const it of (incomingItems ?? [])) {
        const k = it?.key
        if (!k) continue

        if (map.has(k)) {
            const existing = map.get(k)
            existing.qty = Number(existing.qty ?? 1) + Number(it.qty ?? 1)

            existing.product = existing.product ?? it.product
            existing.size = existing.size ?? it.size
            existing.color = existing.color ?? it.color

            existing.size_id = existing.size_id ?? it.size_id
            existing.color_id = existing.color_id ?? it.color_id
        } else {
            map.set(k, { ...it, qty: Number(it.qty ?? 1) })
        }
    }

    return Array.from(map.values())
}

function legacyLineKey(productId, size, color) {
    // formato antiguo (para no “romper” carritos existentes)
    return `${productId}__${size ?? ''}__${color ?? ''}`
}

function lineKey(productId, size_id, color_id, size, color) {
    // formato nuevo: usa IDs si existen, y si no, cae al texto
    const s = size_id ? `sid:${size_id}` : `s:${size ?? ''}`
    const c = color_id ? `cid:${color_id}` : `c:${color ?? ''}`
    return `${productId}__${s}__${c}`
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
            const nextUserId = userId ?? null

            // Si pasamos de invitado -> usuario: mergeamos y vaciamos guest
            if (this.userId === null && nextUserId !== null) {
                const guestKey = storageKeyFor(null)
                const userKey = storageKeyFor(nextUserId)

                const guestItems =
                    this.storageKey === guestKey ? (this.items ?? []) : loadCart(guestKey)

                const userItems = loadCart(userKey)

                const merged = mergeItemLists(userItems, guestItems)

                saveCart(userKey, merged)
                saveCart(guestKey, []) // vaciamos el carrito invitado

                this.userId = nextUserId
                this.storageKey = userKey
                this.items = merged

                return
            }

            // Cualquier otro caso: solo cambiamos el “dueño” y cargamos su carrito
            this.userId = nextUserId
            this.storageKey = storageKeyFor(this.userId)
            this.items = loadCart(this.storageKey)
        },

        _persist() {
            saveCart(this.storageKey, this.items)
        },

        addToCart({ product, qty = 1, size = null, color = null, size_id = null, color_id = null }) {
            if (!product) return

            const productId = product.id ?? product.productId
            if (!productId) return

            const q = Math.max(1, Number(qty ?? 1))

            const newKey = lineKey(productId, size_id, color_id, size, color)
            const oldKey = legacyLineKey(productId, size, color)

            // ✅ Compat: si ya existía con key antigua, la encontramos igual
            let existing = this.items.find((i) => i.key === newKey)
                || this.items.find((i) => i.key === oldKey)

            if (existing) {
                // si venía con key antigua, migramos a la nueva (sin duplicar)
                if (existing.key !== newKey) existing.key = newKey

                existing.qty = Number(existing.qty ?? 1) + q
                existing.product = existing.product ?? product

                // Guardamos valores y IDs (si llegan)
                existing.size = size ?? existing.size ?? null
                existing.color = color ?? existing.color ?? null
                existing.size_id = size_id ?? existing.size_id ?? null
                existing.color_id = color_id ?? existing.color_id ?? null

                this._persist()
                return
            }

            this.items.push({
                key: newKey,
                product,
                qty: q,
                size,
                color,
                size_id,
                color_id,
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
