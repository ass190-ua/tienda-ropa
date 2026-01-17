<template>
    <v-container class="py-6 px-4 px-md-8">
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row align-md-end justify-space-between ga-4 mb-6">
            <div>
                <div class="text-h4 font-weight-bold">Carrito</div>

                <div v-if="!cart.isEmpty" class="text-body-2 text-medium-emphasis mt-1">
                    {{ cart.totalItems }} artículo(s) · Subtotal {{ money(cart.subtotal) }}
                </div>
                <div v-else class="text-body-2 text-medium-emphasis mt-1">
                    Tu carrito está vacío. Cuando añadas productos, aparecerán aquí.
                </div>
            </div>

            <div class="d-flex ga-2">
                <v-btn :to="{ name: 'shop' }" variant="text" class="text-none" color="primary"
                    prepend-icon="mdi-storefront-outline">
                    Seguir comprando
                </v-btn>

                <v-btn v-if="!cart.isEmpty" color="error" variant="tonal" class="text-none"
                    prepend-icon="mdi-trash-can-outline" @click="clearDialog = true" :disabled="cart.syncing">
                    Vaciar
                </v-btn>
            </div>
        </div>

        <!-- Error pago + avisos stock -->
        <v-slide-y-transition group tag="div">
            <v-alert v-if="paymentError" key="payerr" type="error" variant="tonal" rounded="lg" class="mb-4" closable
                @click:close="paymentError = ''">
                {{ paymentError }}
            </v-alert>

            <v-alert v-if="hasOut" key="out" type="error" variant="tonal" class="mb-4">
                Algunos productos están agotados. Elimínalos para continuar.
            </v-alert>

            <v-alert v-if="hasLow" key="low" type="warning" variant="tonal" class="mb-4">
                Algunos productos tienen stock insuficiente. Ajusta cantidades para continuar.
            </v-alert>
        </v-slide-y-transition>

        <!-- Content -->
        <v-fade-transition mode="out-in">
            <!-- Empty -->
            <div v-if="cart.isEmpty" key="empty">
                <v-card variant="outlined" rounded="xl" class="pa-8 text-center">
                    <v-icon icon="mdi-cart-outline" size="52" class="mb-3" />
                    <div class="text-h6 font-weight-bold">Tu carrito está vacío</div>
                    <div class="text-body-2 text-medium-emphasis mt-1">
                        Explora la tienda y añade productos para continuar con la compra.
                    </div>

                    <div class="d-flex flex-column flex-sm-row ga-2 justify-center mt-6">
                        <v-btn color="primary" class="text-none" :to="{ name: 'shop' }">
                            Ir a la tienda
                        </v-btn>
                        <v-btn variant="text" class="text-none" :to="{ name: 'home' }">
                            Volver a inicio
                        </v-btn>
                    </div>
                </v-card>
            </div>

            <!-- Filled -->
            <v-row v-else key="filled" align="start">
                <!-- Items -->
                <v-col cols="12" md="8">
                    <v-card variant="outlined" rounded="xl" class="items-panel premium-surface">
                        <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
                            <div class="text-subtitle-1 font-weight-bold">Productos</div>
                            <v-chip size="small" variant="tonal" rounded="lg">
                                {{ cart.totalItems }} item(s)
                            </v-chip>
                        </div>

                        <v-divider />

                        <div class="items-scroll px-4 py-3">
                            <TransitionGroup name="cart" tag="div" class="cart-list">
                                <div v-for="it in items" :key="it.key" class="cart-row">
                                    <!-- ITEM (lo compactamos en el micro-paso 2) -->
                                    <v-card variant="flat" rounded="xl" class="pa-3 item-compact" :class="{
                                        'item-out': it.av && !it.av.ok && it.av.state === 'OUT',
                                        'item-low': it.av && !it.av.ok && it.av.state === 'LOW',
                                    }">
                                        <div class="d-flex ga-3 align-start">
                                            <v-btn variant="text" class="pa-0 img-btn" :ripple="false"
                                                @click="goToProduct(it.productId)" aria-label="Ver producto">
                                                <v-img :src="it.image" :alt="it.name" width="72" height="72" cover
                                                    class="thumb" />
                                            </v-btn>

                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex align-start justify-space-between ga-2">
                                                    <div class="min-w-0">
                                                        <div class="text-caption text-medium-emphasis">{{ it.brand || ''
                                                        }}</div>
                                                        <div class="text-body-1 font-weight-bold title-clamp">
                                                            {{ it.name }}
                                                        </div>

                                                        <div class="d-flex flex-wrap ga-2 mt-1">
                                                            <v-chip v-if="it.size" size="x-small" variant="tonal"
                                                                rounded="lg">
                                                                Talla: {{ it.size }}
                                                            </v-chip>
                                                            <v-chip v-if="it.color" size="x-small" variant="tonal"
                                                                rounded="lg">
                                                                Color: {{ it.color }}
                                                            </v-chip>
                                                            <v-chip v-if="it.av && !it.av.ok" size="x-small"
                                                                variant="tonal" rounded="lg"
                                                                :color="it.av.state === 'OUT' ? 'error' : 'warning'">
                                                                <template v-if="it.av.state === 'OUT'">
                                                                    Agotado
                                                                </template>
                                                                <template v-else>
                                                                    Stock insuficiente (máx {{ it.av.available }})
                                                                </template>
                                                            </v-chip>
                                                            <v-btn v-if="it.av && it.av.state === 'LOW'" size="x-small"
                                                                variant="text" class="text-none px-0"
                                                                @click="adjustToAvailable(it)" :disabled="cart.syncing">
                                                                Ajustar a {{ it.av.available }}
                                                            </v-btn>
                                                        </div>
                                                    </div>

                                                    <v-btn icon variant="text" color="error" aria-label="Eliminar"
                                                        @click="remove(it)" :disabled="cart.syncing">
                                                        <v-icon
                                                            :icon="(it.av && it.av.state === 'OUT') ? 'mdi-close' : 'mdi-trash-can-outline'" />
                                                    </v-btn>
                                                </div>

                                                <div class="d-flex align-center justify-space-between mt-3">
                                                    <div class="d-flex align-center ga-2">
                                                        <v-btn icon variant="outlined" rounded="lg" size="x-small"
                                                            aria-label="Disminuir"
                                                            :disabled="cart.syncing || it.qty <= 1 || (it.av && it.av.state === 'OUT')"
                                                            @click="dec(it)">
                                                            <v-icon icon="mdi-minus" />
                                                        </v-btn>

                                                        <div class="qty-pill">{{ it.qty }}</div>

                                                        <v-btn icon variant="outlined" rounded="lg" size="x-small"
                                                            aria-label="Aumentar" @click="inc(it)"
                                                            :disabled="cart.syncing || !canInc(it)">
                                                            <v-icon icon="mdi-plus" />
                                                        </v-btn>
                                                    </div>

                                                    <div class="text-right">
                                                        <div class="text-caption text-medium-emphasis">{{
                                                            money(it.price) }} / ud.</div>
                                                        <div class="text-subtitle-2 font-weight-bold">
                                                            {{ money(it.price * it.qty) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </v-card>
                                </div>
                            </TransitionGroup>
                        </div>
                    </v-card>
                </v-col>

                <!-- Summary -->
                <v-col cols="12" md="4">
                    <v-card variant="outlined" rounded="xl" class="pa-5 summary-card premium-surface">
                        <div class="d-flex align-center justify-space-between mb-4">
                            <div class="text-h6 font-weight-bold">Resumen</div>
                            <v-chip size="small" variant="tonal" rounded="lg">
                                {{ cart.totalItems }} item(s)
                            </v-chip>
                        </div>

                        <div class="d-flex align-center justify-space-between py-2">
                            <div class="text-body-2 text-medium-emphasis">Subtotal</div>
                            <div class="font-weight-bold">{{ money(subtotal) }}</div>
                        </div>

                        <div class="d-flex align-center justify-space-between py-2">
                            <div class="text-body-2 text-medium-emphasis">Envío</div>
                            <div class="font-weight-bold">
                                {{ shippingLabel }}
                            </div>
                        </div>

                        <v-divider class="my-3" />

                        <div class="d-flex align-center justify-space-between">
                            <div class="text-subtitle-2 text-medium-emphasis">Total</div>
                            <div class="text-h6 font-weight-bold">{{ money(total) }}</div>
                        </div>

                        <v-btn block color="primary" size="large" rounded="lg" class="text-none mt-5"
                            prepend-icon="mdi-lock-outline"
                            :disabled="cart.syncing || cart.isEmpty || cart.hasAvailabilityIssues"
                            @click="goToCheckout">
                            Continuar con el pago
                        </v-btn>

                        <div v-if="!isAuthenticated" class="text-caption text-medium-emphasis mt-2">
                            Para finalizar la compra tendrás que iniciar sesión.
                        </div>
                    </v-card>
                </v-col>
            </v-row>
        </v-fade-transition>

        <!-- Dialog: clear cart -->
        <v-dialog v-model="clearDialog" max-width="420">
            <v-card rounded="xl">
                <v-card-title class="text-h6 font-weight-bold">
                    Vaciar carrito
                </v-card-title>

                <v-card-text class="text-body-2 text-medium-emphasis">
                    Se eliminarán {{ cart.totalItems }} artículo(s). ¿Seguro que quieres vaciar el carrito?
                </v-card-text>

                <v-card-actions class="px-4 pb-4">
                    <v-spacer />
                    <v-btn variant="text" class="text-none" @click="clearDialog = false">
                        Cancelar
                    </v-btn>
                    <v-btn color="error" variant="flat" class="text-none" @click="confirmClear"
                        :disabled="cart.syncing">
                        Vaciar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="stockDialog" max-width="560" persistent>
            <v-card rounded="xl">
                <v-card-title class="text-h6 font-weight-bold">
                    No puedes continuar con el pago
                </v-card-title>

                <v-card-text class="text-body-2 text-medium-emphasis">
                    <div class="mb-3">
                        El stock ha cambiado. Revisa estos productos:
                    </div>

                    <div v-if="stockIssues.length === 0">
                        Hay productos sin stock suficiente.
                    </div>

                    <div v-else class="d-flex flex-column ga-2">
                        <div v-for="(x, idx) in stockIssues" :key="idx" class="d-flex align-start ga-2">
                            <v-icon :icon="x.state === 'OUT' ? 'mdi-close-circle-outline' : 'mdi-alert-circle-outline'"
                                :color="x.state === 'OUT' ? 'error' : 'warning'" class="mt-1" />
                            <div>
                                <div class="font-weight-bold">{{ x.name }}</div>

                                <div v-if="x.state === 'OUT'">
                                    Agotado.
                                </div>
                                <div v-else>
                                    Stock insuficiente: pedías {{ x.qty }}, máximo disponible {{ x.available }}.
                                </div>
                            </div>
                        </div>
                    </div>
                </v-card-text>

                <v-card-actions class="px-4 pb-4">
                    <v-spacer />
                    <v-btn class="text-none" color="primary" @click="closeStockDialog">
                        Entendido
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar :model-value="!!cart.stockWarning" @update:model-value="v => { if (!v) cart.clearStockWarning() }"
            timeout="2500">
            {{ cart.stockWarning?.message }}
        </v-snackbar>
    </v-container>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const cart = useCartStore()
const auth = useAuthStore()

const clearDialog = ref(false)

const stockDialog = ref(false)
const stockIssues = ref([])

const paymentError = ref('')

const isAuthenticated = computed(() => auth.isAuthenticated)

const items = computed(() => {
    return cart.items.map((it) => {
        const p = it.product ?? {}
        const imgs = Array.isArray(p.images) ? p.images : []
        const first = imgs[0]
        const image =
            (typeof first === 'string' ? first : (first?.url ?? first?.path ?? first?.src)) ??
            p.image ??
            p.image_url ??
            ''

        const mapped = {
            key: it.key,
            productId: p.id ?? it.productId ?? null,
            name: p.name ?? it.name ?? '',
            brand: p.brand ?? it.brand ?? '',
            image,
            price: Number(p.price ?? it.price ?? 0),
            qty: Number(it.qty ?? 1),
            size: it.size ?? null,
            color: it.color ?? null,
        }

        mapped.av = cart.lineAvailabilityStatus({
            product_id: mapped.productId,
            qty: mapped.qty,
        })

        return mapped
    })
})

const hasOut = computed(() => items.value.some(i => i.av && !i.av.ok && i.av.state === 'OUT'))
const hasLow = computed(() => items.value.some(i => i.av && !i.av.ok && i.av.state === 'LOW'))

const subtotal = computed(() => cart.subtotal)

const shipping = computed(() => {
    if (cart.isEmpty) return 0
    return subtotal.value >= 60 ? 0 : 4.99
})

const shippingLabel = computed(() => {
    if (cart.isEmpty) return money(0)
    return shipping.value === 0 ? 'Gratis' : money(shipping.value)
})

const total = computed(() => subtotal.value + shipping.value)

function money(n) {
    const value = Number(n || 0)
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 2,
    }).format(value)
}

function goToProduct(id) {
    router.push({ name: 'product', params: { id } })
}

function openStockDialogFromSession() {
    try {
        const raw = sessionStorage.getItem('tiendamoda_stock_issue')
        if (!raw) return

        const parsed = JSON.parse(raw)
        stockIssues.value = Array.isArray(parsed?.issues) ? parsed.issues : []
    } catch {
        stockIssues.value = []
    }
}

function closeStockDialog() {
    stockDialog.value = false

    try { sessionStorage.removeItem('tiendamoda_stock_issue') } catch { }

    const q = { ...route.query }
    delete q.stock
    router.replace({ name: 'cart', query: q })
}

function canInc(it) {
    const st = it?.av
    if (!st) return true

    if (st.state === 'OUT') return false

    const available = Number(st.available)
    const qty = Number(it?.qty ?? 0)

    if (!Number.isFinite(available)) return true

    return qty < available
}

async function inc(it) {
    await cart.inc(it.key)
}

async function dec(it) {
    cart.dec(it.key)
}

async function remove(it) {
    cart.removeItem(it.key)
}

async function confirmClear() {
    cart.clear()
    clearDialog.value = false
}

function goToCheckout() {
    router.push({ name: 'checkout' })
}

async function adjustToAvailable(it) {
    const max = Number(it?.av?.available ?? 0)
    const pid = Number(it?.productId ?? 0)
    if (!pid) return
    await cart.setQty(pid, max)
    await cart.refreshAvailabilityForCart()
}

watch(() => cart.items, async () => {
    await cart.refreshAvailabilityForCart()
}, { deep: true })

onMounted(async () => {
    if (route.query.stock === '1') {
        openStockDialogFromSession()
        if (stockIssues.value.length) stockDialog.value = true
        else {
            const q = { ...route.query }
            delete q.stock
            router.replace({ name: 'cart', query: q })
        }
    }

    await cart.pullFromBackend?.(true)
    await cart.refreshAvailabilityForCart()
})
</script>


<style scoped>
.premium-surface {
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: rgba(0, 0, 0, 0.01);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
}

.items-panel {
    overflow: hidden;
}

@media (min-width: 960px) {
    .items-scroll {
        max-height: calc(100vh - 220px);
        overflow: auto;
        padding-right: 10px;
    }

    .items-scroll::-webkit-scrollbar {
        width: 10px;
    }

    .items-scroll::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.12);
        border-radius: 999px;
        border: 3px solid transparent;
        background-clip: content-box;
    }

    .items-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
}

.cart-list {
    display: grid;
    gap: 12px;
}

.item-compact {
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
}

.item-compact:hover {
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
    transition: box-shadow 180ms ease, transform 180ms ease;
}

.item-out {
    border: 1px solid rgba(244, 67, 54, 0.35) !important;
    background: rgba(244, 67, 54, 0.06) !important;
}

.item-low {
    border: 1px solid rgba(255, 152, 0, 0.35) !important;
    background: rgba(255, 152, 0, 0.06) !important;
}

.item-out .title-clamp {
    opacity: 0.75;
    text-decoration: line-through;
}

.title-clamp {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.min-w-0 {
    min-width: 0;
}

.thumb {
    border-radius: 14px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.img-btn {
    border-radius: 14px;
    min-width: auto;
}

.qty-pill {
    min-width: 34px;
    height: 30px;
    padding: 0 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.04);
}

@media (min-width: 960px) {
    .summary-card {
        position: sticky;
        top: 92px;
    }
}

.cart-enter-active,
.cart-leave-active {
    transition: opacity 220ms ease, transform 220ms ease;
}

.cart-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.cart-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.cart-move {
    transition: transform 220ms ease;
}
</style>
