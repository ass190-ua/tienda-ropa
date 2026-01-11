<template>
    <v-container class="py-10 px-4 px-md-8">
        <v-row justify="center">
            <v-col cols="12" md="8" lg="6">
                <v-card rounded="xl" class="pa-8 premium-surface">
                    <div class="d-flex flex-column align-center text-center">
                        <v-icon :icon="icon" size="64" :color="iconColor" class="mb-3" />

                        <div class="text-h5 font-weight-bold">
                            {{ title }}
                        </div>

                        <div class="text-body-2 text-medium-emphasis mt-2">
                            {{ subtitle }}
                        </div>

                        <div v-if="isCompleted" class="text-body-2 text-medium-emphasis mt-3">
                            Te hemos enviado un resumen al correo (simulado). Puedes consultar tus pedidos cuando
                            quieras.
                        </div>

                        <v-chip v-if="isCompleted" class="mt-4" variant="tonal" rounded="lg" :color="chipColor">
                            Estado: Completado
                        </v-chip>

                        <v-chip v-if="isFailed" class="mt-4" variant="tonal" rounded="lg" :color="chipColor">
                            Estado: Fallido
                        </v-chip>

                        <div v-if="reason" class="text-body-2 mt-4">
                            <span class="text-medium-emphasis">Motivo:</span>
                            <span class="font-weight-bold"> {{ reason }}</span>
                        </div>

                        <div class="d-flex flex-column flex-sm-row ga-2 mt-8 w-100 justify-center">
                            <v-btn color="primary" class="text-none" @click="goShop">
                                Seguir comprando
                            </v-btn>

                            <v-btn v-if="isCompleted" variant="outlined" class="text-none"
                                prepend-icon="mdi-receipt-text-outline" @click="goOrders">
                                Mis pedidos
                            </v-btn>

                            <v-btn v-else variant="outlined" class="text-none" prepend-icon="mdi-cart-outline"
                                @click="goCart">
                                Volver al carrito
                            </v-btn>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '../stores/auth'
import { ca } from 'vuetify/locale'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()
const auth = useAuthStore()

// Query params
const token = computed(() => String(route.query.token ?? ''))
const status = computed(() => String(route.query.status ?? ''))
const reason = computed(() => String(route.query.reason ?? ''))

const isCompleted = computed(() => status.value === 'COMPLETED')
const isFailed = computed(() => status.value === 'FAILED')

const icon = computed(() => {
    if (isCompleted.value) return 'mdi-check-circle-outline'
    if (isFailed.value) return 'mdi-close-circle-outline'
    return 'mdi-alert-circle-outline'
})

const iconColor = computed(() => {
    if (isCompleted.value) return 'success'
    if (isFailed.value) return 'error'
    return 'warning'
})

const chipColor = computed(() => iconColor.value)

const title = computed(() => {
    if (isCompleted.value) return 'Pago completado'
    if (isFailed.value) return 'Pago fallido'
    return 'Pago en revisión'
})

const subtitle = computed(() => {
    if (isCompleted.value) return 'Tu pedido se ha procesado correctamente.'
    if (isFailed.value) return 'No se pudo completar el pago. Puedes intentarlo de nuevo.'
    return 'Hemos recibido la respuesta del TPV. Si no ves el resultado, vuelve a intentarlo.'
})

function ordersKeyFor(userId) {
    return userId ? `tiendamoda_orders_id${userId}` : 'tiendamoda_orders_guest'
}

function loadOrders(key) {
    try {
        const raw = localStorage.getItem(key)
        if (!raw) return []
        const parsed = JSON.parse(raw)
        return Array.isArray(parsed) ? parsed : []
    } catch {
        return []
    }
}

function saveOrders(key, list) {
    try {
        localStorage.setItem(key, JSON.stringify(list))
    } catch {}
}

function persistOrderFromResult() {
    if (!token.value) return

    const key = ordersKeyFor(auth.user?.id ?? null)
    const list = loadOrders(key)

    let pending = null

    try {
        const raw = localStorage.getItem('tiendamoda_pending_order')
        if (raw) {
            const p = JSON.parse(raw)
            if (p && (p.token === token.value || p.id === token.value)) pending = 0
        }
    } catch { }

    const order = {
        ...(pending ?? {}),
        id: token.value,
        token: token.value,
        createdAt: pending?.createdAt ?? Date.now(),
        status: status.value || 'UNKOWN',
        total: pending?.total ?? null,
        currency: pending?.currency ?? 'EUR',
        itemsCount: pending?.itemsCount ?? null,
        failureReason: isFailed.value ?? (reason.value || pending?.failureReason)
    }

    const idx = list.findIndex(o => String(o?.id || o?.token || '') === order.id)

    if (idx >= 0) list[idx] = order
    else list.push(order)

    saveOrders(key, list)

    if (pending) {
        try {
            localStorage.removeItem('tiendamoda_pending_order')
        } catch { }
    }
}

// Vaciar carrito (compatibilidad con tu store)
function clearCartSafely() {
    if (typeof cart.clearCart === 'function') return cart.clearCart()
    if (typeof cart.clear === 'function') return cart.clear()
    if (Array.isArray(cart.items)) cart.items = []
}

onMounted(() => {
    // Si el pago está COMPLETED, vaciamos el carrito
    if (isCompleted.value) {
        cart.clear()
    }
    persistOrderFromResult()
})

function goShop() {
    router.push({ name: 'shop' })
}

function goCart() {
    router.push({ name: 'cart' })
}

function goOrders() {
    router.push({ name: 'orders' })
}
</script>

<style scoped>
.premium-surface {
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: rgba(0, 0, 0, 0.01);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
}
</style>
