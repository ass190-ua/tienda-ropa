<template>
    <v-container class="py-6 px-4 px-md-8" style="max-width: 1200px;">
        <div class="text-h4 font-weight-bold mb-4">Finalizar compra</div>

        <v-card class="pa-4 pa-md-6" rounded="xl" elevation="2">
            <v-stepper v-model="step" rounded="xl">
                <v-stepper-header>
                    <v-stepper-item value="1" title="Datos personales" :complete="stepNum > 1"
                        icon="mdi-account-circle-outline" />
                    <v-divider />
                    <v-stepper-item value="2" title="Direcciones" :complete="stepNum > 2"
                        icon="mdi-map-marker-outline" />
                    <v-divider />
                    <v-stepper-item value="3" title="Resumen" icon="mdi-receipt-text-outline" />
                </v-stepper-header>

                <v-stepper-window>
                    <!-- Paso 1 -->
                    <v-stepper-window-item value="1">
                        <v-fade-transition mode="out-in">
                            <div :key="'step-1'" class="step-panel">
                                <v-form ref="formPersonal" @submit.prevent="goStep2">
                                    <div class="text-h6 font-weight-medium mb-3">Datos personales</div>

                                    <v-row>
                                        <v-col cols="12" md="6">
                                            <v-text-field v-model="personal.name" label="Nombre y apellidos"
                                                :rules="[required]" autocomplete="name" />
                                        </v-col>

                                        <v-col cols="12" md="6">
                                            <v-text-field v-model="personal.email" label="Email"
                                                :rules="[required, emailRule]" autocomplete="email" />
                                        </v-col>
                                    </v-row>

                                    <div class="d-flex justify-end ga-2 mt-2">
                                        <v-btn variant="text" @click="goCart" :disabled="cart.syncing">Volver al
                                            carrito</v-btn>
                                        <v-btn color="primary" type="submit" class="text-none" rounded="lg"
                                            :disabled="cart.syncing">Continuar</v-btn>
                                    </div>
                                </v-form>
                            </div>
                        </v-fade-transition>
                    </v-stepper-window-item>

                    <!-- Paso 2 -->
                    <v-stepper-window-item value="2">
                        <v-fade-transition mode="out-in">
                            <div :key="'step-2'" class="step-panel">
                                <v-form ref="formAddresses" @submit.prevent="goStep3">
                                    <div class="text-h6 font-weight-medium mb-3">Direcciones</div>

                                    <v-row>
                                        <v-col cols="12" md="6">
                                            <div class="addr-header d-flex align-center justify-space-between mb-2">
                                                <div class="text-subtitle-1 font-weight-medium">Envío</div>
                                                <div class="addr-header-spacer" />
                                            </div>
                                            <v-text-field v-model="shipping.line1" label="Dirección"
                                                :rules="[required]" />
                                            <v-text-field v-model="shipping.city" label="Ciudad" :rules="[required]" />
                                            <v-text-field v-model="shipping.zip" label="Código postal"
                                                :rules="[required]" />
                                            <v-text-field v-model="shipping.country" label="País" :rules="[required]" />
                                        </v-col>

                                        <v-col cols="12" md="6">
                                            <div class="addr-header d-flex align-center justify-space-between mb-2">
                                                <div class="text-subtitle-1 font-weight-medium">Facturación</div>

                                                <div class="d-flex align-center ga-2">
                                                    <v-switch v-model="billingSameAsShipping" inset color="primary"
                                                        density="compact" hide-details class="switch-compact" />
                                                    <span class="text-caption text-medium-emphasis">Igual que
                                                        envío</span>
                                                </div>
                                            </div>

                                            <v-text-field v-model="billing.line1" label="Dirección"
                                                :rules="billingSameAsShipping ? [] : [required]"
                                                :disabled="billingSameAsShipping" />
                                            <v-text-field v-model="billing.city" label="Ciudad"
                                                :rules="billingSameAsShipping ? [] : [required]"
                                                :disabled="billingSameAsShipping" />
                                            <v-text-field v-model="billing.zip" label="Código postal"
                                                :rules="billingSameAsShipping ? [] : [required]"
                                                :disabled="billingSameAsShipping" />
                                            <v-text-field v-model="billing.country" label="País"
                                                :rules="billingSameAsShipping ? [] : [required]"
                                                :disabled="billingSameAsShipping" />
                                        </v-col>
                                    </v-row>

                                    <div class="d-flex justify-end ga-2 mt-2">
                                        <v-btn variant="text" class="text-none" rounded="lg" @click="step = '1'"
                                            :disabled="cart.syncing">Atrás</v-btn>
                                        <v-btn color="primary" type="submit" class="text-none" rounded="lg"
                                            :disabled="cart.syncing">Continuar</v-btn>
                                    </div>
                                </v-form>
                            </div>
                        </v-fade-transition>
                    </v-stepper-window-item>

                    <!-- Paso 3 -->
                    <v-stepper-window-item value="3">
                        <v-fade-transition mode="out-in">
                            <div :key="'step-3'" class="step-panel">
                                <div class="text-h6 font-weight-medium mb-3">Resumen</div>

                                <div v-if="cart.isEmpty">
                                    <v-alert type="warning" variant="tonal" class="mb-4">
                                        Tu carrito está vacío.
                                    </v-alert>

                                    <v-btn color="primary" class="text-none" :to="{ name: 'shop' }">
                                        Ir a la tienda
                                    </v-btn>
                                </div>

                                <v-row v-else>
                                    <v-col cols="12" md="7">
                                        <v-card rounded="xl" class="panel-card pa-4">
                                            <div class="d-flex align-center justify-space-between mb-3">
                                                <div class="text-subtitle-1 font-weight-bold">Artículos</div>
                                                <v-chip size="small" class="qty-chip">{{ totalQty }} uds</v-chip>
                                            </div>

                                            <div class="items-scroll">
                                                <v-slide-y-transition group>
                                                    <div v-for="it in summaryItems" :key="it.key" class="item-row">
                                                        <div class="d-flex ga-3">
                                                            <v-avatar rounded="lg" size="64" class="bg-grey-lighten-3">
                                                                <v-img v-if="it.image" :src="it.image" cover />
                                                                <v-icon v-else icon="mdi-tshirt-crew-outline" />
                                                            </v-avatar>

                                                            <div class="flex-grow-1">
                                                                <div
                                                                    class="d-flex align-start justify-space-between ga-3">
                                                                    <div>
                                                                        <div class="text-body-1 font-weight-medium">{{
                                                                            it.name }}</div>
                                                                        <div class="text-caption text-medium-emphasis">
                                                                            <span v-if="it.size">Talla: {{ it.size
                                                                            }}</span>
                                                                            <span v-if="it.size && it.color"> · </span>
                                                                            <span v-if="it.color">Color: {{ it.color
                                                                            }}</span>
                                                                        </div>
                                                                    </div>

                                                                    <div class="text-right">
                                                                        <div class="text-body-2 text-medium-emphasis">
                                                                            {{ money(it.unitPrice) }} × {{ it.qty }}
                                                                        </div>
                                                                        <div class="text-body-1 font-weight-bold">
                                                                            {{ money(it.lineTotal) }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </v-slide-y-transition>
                                            </div>
                                        </v-card>
                                    </v-col>

                                    <v-col cols="12" md="5">
                                        <v-card rounded="xl" class="panel-card pa-4 summary-totals">
                                            <div class="text-subtitle-1 font-weight-bold mb-3">Totales</div>
                                            <div class="d-flex justify-space-between mb-2">
                                                <div class="text-body-2 text-medium-emphasis">Subtotal</div>
                                                <div class="text-body-2 font-weight-medium">{{ money(cart.subtotal) }}
                                                </div>
                                            </div>

                                            <div class="d-flex justify-space-between mb-2">
                                                <div class="text-body-2 text-medium-emphasis">Envío</div>
                                                <div class="text-body-2 font-weight-medium">{{ shippingLabel }}</div>
                                            </div>

                                            <!-- Cupón -->
                                            <v-text-field v-model="couponCode" label="Cupón de descuento"
                                                density="comfortable" hide-details="auto" :disabled="!!appliedCoupon"
                                                :loading="applyingCoupon" class="mt-3">
                                                <template #append-inner>
                                                    <v-btn size="small" class="text-none"
                                                        :disabled="!couponCode || applyingCoupon || !!appliedCoupon || cart.syncing"
                                                        @click="applyCoupon">
                                                        Aplicar
                                                    </v-btn>
                                                </template>
                                            </v-text-field>

                                            <v-alert v-if="couponError" type="error" variant="tonal" class="mt-2"
                                                rounded="lg">
                                                {{ couponError }}
                                            </v-alert>

                                            <v-expand-transition>
                                                <div v-if="appliedCoupon"
                                                    class="d-flex align-center justify-space-between mt-2">
                                                    <v-chip size="small" rounded="lg" variant="tonal" color="success"
                                                        prepend-icon="mdi-ticket-percent-outline">
                                                        Cupón {{ appliedCoupon.code }}
                                                    </v-chip>

                                                    <v-btn variant="text" size="small" class="text-none"
                                                        prepend-icon="mdi-close" @click="removeCoupon"
                                                        :disabled="cart.syncing">
                                                        Quitar
                                                    </v-btn>
                                                </div>
                                            </v-expand-transition>

                                            <!-- Descuento -->
                                            <v-expand-transition>
                                                <div v-if="couponDiscount > 0"
                                                    class="d-flex justify-space-between mb-2 mt-3">
                                                    <div class="text-body-2 text-medium-emphasis">Descuento</div>
                                                    <div class="text-body-2 font-weight-medium">-{{
                                                        money(couponDiscount) }}</div>
                                                </div>
                                            </v-expand-transition>

                                            <div class="d-flex justify-space-between total-row mt-3">
                                                <div>Total</div>
                                                <div>{{ money(totalToPay) }}</div>
                                            </div>

                                            <v-btn class="text-none mt-4" color="primary" size="large" block
                                                :loading="isPaying"
                                                :disabled="cart.isEmpty || isPaying || cart.syncing || !!paymentError"
                                                @click="pay" prepend-icon="mdi-credit-card-outline">
                                                Pagar
                                            </v-btn>

                                            <div class="d-flex flex-column flex-sm-row justify-end ga-2 mt-2">
                                                <v-btn class="text-none" rounded="lg" variant="text"
                                                    @click="step = '2'">
                                                    Atrás
                                                </v-btn>
                                            </div>
                                        </v-card>
                                    </v-col>
                                </v-row>
                            </div>
                        </v-fade-transition>
                    </v-stepper-window-item>
                </v-stepper-window>
            </v-stepper>
        </v-card>
    </v-container>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'

const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()

const isPaying = ref(false)

const step = ref('1')
const stepNum = computed(() => Number(step.value))

const formPersonal = ref(null)
const formAddresses = ref(null)

const didPrefillAddress = ref(false)

const couponCode = ref('')
const appliedCoupon = ref(null)

const applyingCoupon = ref(false)
const couponError = ref('')

const paymentError = ref('')

const couponDiscount = computed(() => Number(appliedCoupon.value?.discount_amount ?? 0))

const subtotalAfterDiscount = computed(() => {
    return Math.max(0, Number(cart.subtotal || 0) - couponDiscount.value)
})

const totalToPay = computed(() => {
    return Number((subtotalAfterDiscount.value + shippingCost.value).toFixed(2))
})

const personal = ref({
    name: '',
    email: '',
    phone: '',
})

const shipping = ref({
    line1: '',
    city: '',
    zip: '',
    country: 'España',
})

const billingSameAsShipping = ref(true)

const billing = ref({
    line1: '',
    city: '',
    zip: '',
    country: 'España',
})

const summaryItems = computed(() => {
    return (cart.items ?? []).map((it, idx) => {
        const p = it?.product ?? it ?? {}
        const qty = Number(it?.qty ?? it?.quantity ?? 1)

        const unitPrice = Number(p?.price ?? it?.price ?? 0)

        const imgs = Array.isArray(p?.images) ? p.images : []
        const first = imgs[0]
        const image =
            (typeof first === 'string' ? first : (first?.url ?? first?.path ?? first?.src)) ??
            p?.image ??
            p?.image_url ??
            ''

        return {
            key: it?.key ?? `${p?.id ?? 'item'}-${idx}`,
            name: p?.name ?? it?.name ?? 'Producto',
            qty,
            unitPrice,
            lineTotal: unitPrice * qty,
            image,
            size: it?.size ?? null,
            color: it?.color ?? null,
        }
    })
})

const totalQty = computed(() => summaryItems.value.reduce((acc, it) => acc + Number(it.qty || 0), 0))

const shippingCost = computed(() => {
    if (cart.isEmpty) return 0
    return cart.subtotal >= 60 ? 0 : 4.99
})

const shippingLabel = computed(() => (shippingCost.value === 0 ? 'Gratis' : money(shippingCost.value)))

const total = computed(() => cart.subtotal + shippingCost.value)

watch(billingSameAsShipping, (same) => {
    if (same) billing.value = { ...shipping.value }
}, { immediate: true })

watch(shipping, (v) => {
    if (billingSameAsShipping.value) billing.value = { ...v }
}, { deep: true })

watch(
    () => auth.user,
    (u) => {
        if (!u) return
        personal.value.name = personal.value.name || (u.name ?? '')
        personal.value.email = personal.value.email || (u.email ?? '')
    },
    { immediate: true }
)

watch(
    () => auth.user?.id,
    async (id) => {
        if (!id || didPrefillAddress.value) return
        didPrefillAddress.value = true

        try {
            const resp = await axios.get('/api/addresses/me')
            const ship = resp?.data?.shipping
            const bill = resp?.data?.billing

            const shippingEmpty = !shipping.value.line1 && !shipping.value.city && !shipping.value.zip
            if (ship && shippingEmpty) {
                shipping.value = { ...shipping.value, ...ship }
            }

            // Si billing es distinto y está vacío, lo rellenamos
            const billingEmpty = !billing.value.line1 && !billing.value.city && !billing.value.zip
            if (!billingSameAsShipping.value && bill && billingEmpty) {
                billing.value = { ...billing.value, ...bill }
            }
        } catch (e) {
            console.error('No se pudieron cargar direcciones del usuario', e)
        }
    },
    { immediate: true }
)

const required = (v) => !!v || 'Campo obligatorio'
const emailRule = (v) => /.+@.+\..+/.test(v) || 'Email inválido'

function money(n) {
    const num = Number(n || 0)
    return num.toLocaleString('es-ES', { style: 'currency', currency: 'EUR' })
}

function goCart() {
    router.push('/cart')
}

async function applyCoupon() {
    couponError.value = ''
    const code = String(couponCode.value || '').trim()
    if (!code) return

    applyingCoupon.value = true
    try {
        let resp
        try {
            resp = await axios.post('/api/coupons/validate', {
                code,
                subtotal: Number(cart.subtotal || 0),
            })
        } catch (e) {
            if (e?.response?.status === 419) {
                await axios.get('/sanctum/csrf-cookie')
                resp = await axios.post('/api/coupons/validate', {
                    code,
                    subtotal: Number(cart.subtotal || 0),
                })
            } else {
                throw e
            }
        }

        appliedCoupon.value = {
            ...resp.data.coupon,
            discount_amount: Number(resp.data.discount_amount || 0),
        }
        couponCode.value = appliedCoupon.value.code
    } catch (e) {
        appliedCoupon.value = null
        couponError.value =
            e?.response?.data?.error ||
            e?.response?.data?.message ||
            e?.message ||
            'No se pudo aplicar el cupón.'
    } finally {
        applyingCoupon.value = false
    }
}

watch(
    () => cart.subtotal,
    (newVal, oldVal) => {
        if (!appliedCoupon.value) return
        if (Number(newVal) === Number(oldVal)) return

        appliedCoupon.value = null
        couponError.value = 'El carrito ha cambiado. Vuelve a aplicar el cupón.'
    }
)

watch(
    () => cart.isEmpty,
    (empty) => {
        if (!empty) return
        appliedCoupon.value = null
        couponError.value = ''
        couponCode.value = ''
    }
)

function removeCoupon() {
    appliedCoupon.value = null
    couponError.value = ''
    couponCode.value = ''
}

async function goStep2() {
    const res = await formPersonal.value?.validate()
    if (res?.valid) step.value = '2'
}

async function goStep3() {
    // si billingSameAsShipping está activo, ya está copiado
    const res = await formAddresses.value?.validate()
    if (res?.valid) step.value = '3'
}

async function pay() {
    if (cart.isEmpty || isPaying.value || cart.syncing) return

    if (!auth.isAuthenticated) {
        router.push('/login')
        return
    }

    paymentError.value = ''
    isPaying.value = true

    try {
        const amount = Number(totalToPay.value.toFixed(2))

        let resp

        const payload = {}
        if (appliedCoupon.value?.code) payload.coupon_code = appliedCoupon.value.code

        console.log('[Checkout] Pagando... carrito', {
            totalToPay: totalToPay.value,
            totalItems: cart.totalItems,
            items: cart.items.map(it => ({
                product_id: it.product?.id ?? it.product_id ?? null,
                qty: Number(it.qty ?? it.quantity ?? 1),
                name: it.product?.name ?? it.name ?? 'Producto',
            }))
            ,
            coupon: appliedCoupon.value?.code ?? null,
        })

        try { await cart.refreshAvailabilityForCart?.() } catch { }

        const issues = (cart.items ?? [])
            .map((it) => {
                const pid = Number(it.product?.id ?? it.product_id ?? it.productId ?? 0)
                const qty = Number(it.qty ?? it.quantity ?? 0)

                const st = cart.lineAvailabilityStatus?.(it)

                return {
                    name: it.product?.name ?? it.name ?? 'Producto',
                    state: st?.state ?? 'OK',
                    ok: st?.ok ?? true,
                    available: Number(st?.available ?? 0),
                    qty,
                }
            })
            .filter(x => x.ok === false)

        if (issues.length) {
            sessionStorage.setItem('tiendamoda_stock_issue', JSON.stringify({
                at: Date.now(),
                issues: issues.map(x => ({
                    name: x.name,
                    state: x.state,
                    available: x.available,
                    qty: x.qty,
                })),
            }))

            router.push({ name: 'cart', query: { stock: '1' } })
            return
        }

        resp = await axios.post('/api/checkout/start', payload)

        console.log('[Checkout] /checkout/start OK', resp?.data)

        const serverAmount = Number(resp?.data?.amount ?? amount)
        try {
        } catch (e) {
            // Si es 419, pedimos csrf-cookie y reintentamos UNA vez
            if (e?.response?.status === 419) {
                await axios.get('/sanctum/csrf-cookie')
                resp = await axios.post('/api/checkout/start', { amount })
            } else {
                throw e
            }
        }

        const paymentUrl = resp?.data?.paymentUrl
        const token = resp?.data?.token

        if (!paymentUrl || !token) {
            throw new Error('No se recibió paymentUrl/token desde el backend.')
        }

        const items = (cart.items ?? [])
            .map(it => ({
                product_id: it.product?.id ?? it.product_id ?? null,
                qty: Number(it.qty ?? it.quantity ?? 1),
                name: it.product?.name ?? it.name ?? null,
            }))
            .filter(i => i.product_id && i.qty > 0)

        localStorage.setItem('tiendamoda_pending_order', JSON.stringify({
            id: token,
            token,
            createdAt: Date.now(),
            status: 'PENDING',
            total: serverAmount,
            currency: 'EUR',
            itemsCount: cart.totalItems,
            coupon: appliedCoupon.value ? { ...appliedCoupon.value } : null,

            personal: { ...personal.value },
            shipping: { ...shipping.value },
            billing: billingSameAsShipping.value ? { ...shipping.value } : { ...billing.value },

            items,
        }))

        console.log('[Checkout] Redirigiendo a TPV', { paymentUrl, token, serverAmount })

        window.location.href = paymentUrl
    } catch (e) {
        console.error('Error iniciando pago:', e)

        const status = e?.response?.status
        const data = e?.response?.data

        if (status === 422) {
            // Si backend manda message + code
            console.log('[Checkout] 422 STOCK ANTES DE TPV', data)
            paymentError.value = data?.message || 'No hay stock suficiente para iniciar el pago.'
            return
        }

        paymentError.value = 'No se ha podido iniciar el pago. Inténtalo de nuevo.'
    } finally {
        isPaying.value = false
    }
}
</script>

<style scoped>
.addr-header {
    min-height: 44px;
}

.addr-header-spacer {
    width: 1px;
    height: 1px;
}

.switch-compact :deep(.v-selection-control__wrapper) {
    margin-inline-end: 0;
}

.step-panel {
    animation: slideFadeIn 220ms ease;
}

@keyframes slideFadeIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (min-width: 960px) {
    .items-scroll {
        max-height: calc(100vh - 360px);
        overflow-y: auto;
        padding-right: 8px;
    }

    .summary-totals {
        position: sticky;
        top: 110px;
        align-self: start;
    }
}

/* Paneles principales (Artículos / Totales) */
.panel-card {
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}

/* Chip de unidades */
.qty-chip {
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.06);
    font-weight: 600;
}

/* Cada item como “fila tarjeta” */
.item-row {
    padding: 14px 12px;
    border-radius: 14px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    background: rgba(0, 0, 0, 0.02);
    margin-bottom: 12px;
}

.item-row:last-child {
    margin-bottom: 0;
}

/* Scroll más elegante */
.items-scroll {
    padding-right: 6px;
}

.items-scroll::-webkit-scrollbar {
    width: 8px;
}

.items-scroll::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.18);
    border-radius: 999px;
}

.items-scroll::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 999px;
}

.total-row {
    font-weight: 800;
    font-size: 1.05rem;
    padding-top: 10px;
    border-top: 1px solid rgba(0, 0, 0, 0.12);
}
</style>
