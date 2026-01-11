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
                <v-btn :to="{ name: 'shop' }" variant="text" class="text-none" prepend-icon="mdi-storefront-outline">
                    Seguir comprando
                </v-btn>

                <v-btn v-if="!cart.isEmpty" color="error" variant="tonal" class="text-none"
                    prepend-icon="mdi-trash-can-outline" @click="clearDialog = true">
                    Vaciar
                </v-btn>
            </div>
        </div>

        <!-- Error pago -->
        <v-slide-y-transition>
            <v-alert v-if="paymentError" type="error" variant="tonal" rounded="lg" class="mb-4" closable
                @click:close="paymentError = ''">
                {{ paymentError }}
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
                                    <v-card variant="flat" rounded="xl" class="pa-3 item-compact">
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
                                                        </div>
                                                    </div>

                                                    <v-btn icon variant="text" color="error" aria-label="Eliminar"
                                                        @click="remove(it)">
                                                        <v-icon icon="mdi-trash-can-outline" />
                                                    </v-btn>
                                                </div>

                                                <div class="d-flex align-center justify-space-between mt-3">
                                                    <div class="d-flex align-center ga-2">
                                                        <v-btn icon variant="outlined" rounded="lg" size="x-small"
                                                            aria-label="Disminuir" :disabled="it.qty <= 1"
                                                            @click="dec(it)">
                                                            <v-icon icon="mdi-minus" />
                                                        </v-btn>

                                                        <div class="qty-pill">{{ it.qty }}</div>

                                                        <v-btn icon variant="outlined" rounded="lg" size="x-small"
                                                            aria-label="Aumentar" @click="inc(it)">
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
                            prepend-icon="mdi-lock-outline" :loading="isPaying"
                            :disabled="cart.isEmpty || isPaying || !isAuthenticated" @click="startCheckout">
                            Continuar al pago
                        </v-btn>

                        <div class="text-caption text-medium-emphasis mt-3">
                            Al continuar, se abrirá la pasarela de pago (TPV) para completar la compra.
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
                    <v-btn color="error" variant="flat" class="text-none" @click="confirmClear">
                        Vaciar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const cart = useCartStore()
const auth = useAuthStore()

const clearDialog = ref(false)
const isPaying = ref(false)
const paymentError = ref('')

const isAuthenticated = computed(() => auth.isAuthenticated)

function redirectToLogin() {
    router.replace({
        path: '/login',
        query: { redirect: route.fullPath || '/cart' },
    })
}

onMounted(() => {
    if (!isAuthenticated.value) redirectToLogin()
})

// Si se desloguea mientras está en el carrito, lo echamos a login.
watch(isAuthenticated, (logged) => {
    if (!logged) redirectToLogin()
})

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

        return {
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
    })
})

const subtotal = computed(() => cart.subtotal)

const shipping = computed(() => {
    // ejemplo simple: gratis desde 60€, si no 4.99€
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

function inc(it) {
    cart.inc(it.key)
}

function dec(it) {
    cart.dec(it.key)
}

function remove(it) {
    cart.removeItem(it.key)
}

function confirmClear() {
    cart.clear()
    clearDialog.value = false
}

async function startCheckout() {
    paymentError.value = ''

    if (!auth.isAuthenticated) {
        router.push({ name: 'login', query: { redirect: '/cart' } })
        return
    }

    isPaying.value = true

    try {
        const amount = Number(total.value.toFixed(2))

        const resp = await axios.post('/api/checkout/start', { amount })
        const paymentUrl = resp?.data?.paymentUrl
        const token = resp?.data?.token

        if (!paymentUrl || !token) {
            throw new Error('No se recibió paymentUrl/token desde el backend.')
        }

        localStorage.setItem('tiendamoda_pending_order', JSON.stringify({
            id: token,
            token,
            createdAt: Date.now(),
            status: 'PENDING',
            total: amount,
            currency: 'EUR',
            itemsCount: cart.totalItems,
        }))

        window.location.href = paymentUrl
    } catch (e) {
        paymentError.value =
            e?.response?.data?.message ||
            e?.message ||
            'No se pudo iniciar el pago. Inténtalo de nuevo.'
    } finally {
        isPaying.value = false
    }
}
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
