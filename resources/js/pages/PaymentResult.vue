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

                            <v-btn v-if="isCompleted && !creatingOrder && !createError" variant="outlined"
                                class="text-none" prepend-icon="mdi-receipt-text-outline" @click="goOrders">
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
const reason = computed(() => String(route.query.reason ?? ''))

const isCompleted = computed(() => status.value === 'COMPLETED')
const isFailed = computed(() => status.value === 'FAILED')

const creatingOrder = ref(false)
const createError = ref('')
const orderCreated = ref(false)

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

    if (!payload.shipping || !payload.billing || payload.items.length === 0) {
        throw new Error('Faltan direcciones o items en pending_order.')
    }

    if (payload.items.some(i => !i.product_id || !i.qty)) {
        throw new Error('Hay items inválidos (product_id/qty) en pending_order.')
    }

    // POST con retry 419 (Sanctum/CSRF)
    try {
        const resp = await axios.post('/api/orders/complete', payload)
        return resp?.data
    } catch (e) {
        if (e?.response?.status === 419) {
            await axios.get('/sanctum/csrf-cookie')
            await axios.post('/api/orders/complete', payload)
        } else {
            throw e
        }
    }
}

onMounted(async () => {
    if (!token.value) return

    // Si falla, no tocamos carrito
    if (isFailed.value) {
        // limpiamos pending para no acumular tokens
        removePending()
        return
    }

    // Solo creamos pedido si COMPLETED
    if (!isCompleted.value) return

    creatingOrder.value = true
    createError.value = ''

    try {
        const result = await createOrderInDb()
        orderCreated.value = true

        cart.clear()
        removePending()
    } catch (e) {
        createError.value =
            e?.response?.data?.error ||
            e?.response?.data?.message ||
            e?.message ||
            'No se pudo crear el pedido en la base de datos.'
        console.error(e)
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
