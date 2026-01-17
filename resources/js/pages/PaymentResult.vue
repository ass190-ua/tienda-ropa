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

                        <v-btn v-if="isCompleted && createError" class="text-none mt-3" color="primary"
                            prepend-icon="mdi-refresh" :loading="creatingOrder" :disabled="creatingOrder"
                            @click="retryCreateOrder">
                            Reintentar crear pedido
                        </v-btn>

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

                            <v-btn v-if="orderCreated" variant="outlined" class="text-none"
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
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useCartStore } from '@/stores/cart'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()

// Query params
const token = computed(() => String(route.query.token ?? ''))
const status = computed(() => String(route.query.status ?? ''))
const reason = computed(() => (hasReason.value ? String(route.query.reason) : ''))

const creatingOrder = ref(false)
const createError = ref('')
const orderCreated = ref(false)

const deciding = ref(true)

const hasReason = computed(() => {
    const r = String(route.query.reason ?? '').trim()
    return r.length > 0 && r.toLowerCase() !== 'null' && r.toLowerCase() !== 'undefined'
})

// SOLO es “completado” si COMPLETED y NO hay failureReason
const isCompleted = computed(() => status.value === 'COMPLETED' && !hasReason.value)

// Es “fallido” si FAILED o si viene failureReason
const isFailed = computed(() => status.value === 'FAILED' || hasReason.value)

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
    if (deciding.value) return 'Procesando pago...'
    if (isCompleted.value) return 'Pago completado'
    if (isFailed.value) return 'Pago fallido'
    return 'Pago en revisión'
})

const subtitle = computed(() => {
    if (deciding.value) return 'Estamos confirmando tu pedido...'
    if (isCompleted.value) {
        return orderCreated.value
            ? 'Tu pedido se ha procesado correctamente.'
            : 'Pago completado. Estamos registrando el pedido...'
    }
    if (isFailed.value) return 'No se pudo completar el pago. Puedes intentarlo de nuevo.'
    return 'Hemos recibido la respuesta del TPV. Si no ves el resultado, vuelve a intentarlo.'
})

function readPending() {
    try {
        const raw = localStorage.getItem('tiendamoda_pending_order')
        return raw ? JSON.parse(raw) : null
    } catch {
        return null
    }
}

function removePending() {
    try { localStorage.removeItem('tiendamoda_pending_order') } catch { }
}

function redirectToCartWithStockIssue(e) {
    const http = e?.response
    const statusCode = http?.status
    const data = http?.data

    if (statusCode !== 422) return false

    // backend puede mandar product_id o id o incluso issues[]
    const pid = Number(data?.product_id ?? data?.id ?? 0)
    if (!pid) return false

    const pending = readPending()

    const requested = Number(data?.requested ?? data?.qty ?? 0)
    const available = Number(data?.available ?? data?.stock_total ?? data?.stock ?? 0)

    // Intento 1: pending.items
    const foundPending = Array.isArray(pending?.items)
        ? pending.items.find(x => Number(x.product_id) === pid)
        : null

    // Intento 2: cart.items (normalmente sí tiene product.name)
    const foundCart = Array.isArray(cart?.items)
        ? cart.items.find(x => Number(x?.product?.id ?? x?.product_id ?? 0) === pid)
        : null

    const name =
        foundPending?.name ??
        foundCart?.product?.name ??
        foundCart?.name ??
        `Producto #${pid}`

    sessionStorage.setItem('tiendamoda_stock_issue', JSON.stringify({
        at: Date.now(),
        issues: [{
            name,
            state: available <= 0 ? 'OUT' : 'LOW',
            available,
            qty: requested,
        }],
    }))

    removePending()

    router.push({ name: 'cart', query: { stock: '1' } })
    return true
}

async function createOrderInDb() {
    const pending = readPending()

    if (!pending) throw new Error('No existe pending_order en el navegador.')
    if (String(pending.token ?? pending.id ?? '') !== token.value) {
        throw new Error('El token no coincide con el pending_order (posible sesión vieja).')
    }

    const payload = {
        token: token.value,
        amount: pending.total ?? null,
        shipping: pending.shipping,
        billing: pending.billing,
        items: Array.isArray(pending.items) ? pending.items : [],
        ...(pending?.coupon?.code ? { coupon_code: pending.coupon.code } : {}),
    }

    try {
        const resp = await axios.post('/api/orders/complete', payload)
        return resp?.data
    } catch (e) {
        if (e?.response?.status === 419) {
            await axios.get('/sanctum/csrf-cookie')
            const resp2 = await axios.post('/api/orders/complete', payload)
            return resp2?.data
        }
        throw e
    }
}

async function retryCreateOrder() {
    if (!isCompleted.value || !token.value) return

    creatingOrder.value = true
    createError.value = ''

    try {
        await createOrderInDb()
        orderCreated.value = true

        removePending()
        await cart.clear()
    } catch (e) {
        if (redirectToCartWithStockIssue(e)) return

        createError.value =
            e?.response?.data?.error ||
            e?.response?.data?.message ||
            e?.message ||
            'No se pudo crear el pedido en la base de datos.'
        console.error(e)
    } finally {
        creatingOrder.value = false
    }
}

onMounted(async () => {
    console.log('[PaymentResult] mounted', {
        token: token.value,
        status: status.value,
        reason: reason.value,
    })
    console.log('[PaymentResult] flags', {
        isCompleted: isCompleted.value,
        isFailed: isFailed.value,
    })

    // Si no hay token, no hacemos nada
    if (!token.value) {
        deciding.value = false
        return
    }

    // Si falla, NO tocamos carrito, pero ya hemos decidido
    if (isFailed.value) {
        removePending()
        deciding.value = false
        return
    }

    // Si no es COMPLETED (p.ej. PENDING), no hacemos nada, pero ya hemos decidido
    if (!isCompleted.value) {
        deciding.value = false
        return
    }

    creatingOrder.value = true
    createError.value = ''

    try {
        await createOrderInDb()
        orderCreated.value = true

        console.log('[PaymentResult] Borrando carrito porque pedido creado OK')
        removePending()
        await cart.clear()

        deciding.value = false
    } catch (e) {
        if (redirectToCartWithStockIssue(e)) return

        createError.value =
            e?.response?.data?.error ||
            e?.response?.data?.message ||
            e?.message ||
            'No se pudo crear el pedido en la base de datos.'

        console.error(e)
        deciding.value = false
    } finally {
        creatingOrder.value = false
    }
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
