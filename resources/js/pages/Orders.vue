<template>
    <v-container class="py-8 px-4 px-md-8">
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row align-md-end justify-space-between ga-4 mb-6">
            <div>
                <div class="text-h4 font-weight-bold">Mis pedidos</div>
                <div class="text-body-2 text-medium-emphasis mt-1">
                    Consulta el estado de tus compras recientes.
                </div>
            </div>

            <div class="d-flex ga-2 align-center">
                <v-chip variant="tonal" rounded="lg">
                    <template v-if="loading">Cargando…</template>
                    <template v-else>{{ filteredOrders.length }} pedido(s)</template>
                </v-chip>

                <v-btn color="primary" class="text-none" prepend-icon="mdi-storefront-outline" @click="goShop">
                    Seguir comprando
                </v-btn>
            </div>
        </div>

        <!-- Filtros -->
        <v-card rounded="xl" class="pa-3 mb-5 premium-surface">
            <div class="d-flex flex-wrap align-center justify-space-between ga-3">
                <div class="d-flex flex-wrap ga-2">
                    <v-chip v-for="opt in statusOptions" :key="opt.value"
                        :color="statusFilter === opt.value ? 'primary' : undefined"
                        :variant="statusFilter === opt.value ? 'flat' : 'tonal'" rounded="lg" class="text-none"
                        @click="statusFilter = opt.value" :disabled="loading">
                        {{ opt.label }}
                    </v-chip>
                </div>

                <v-btn variant="text" class="text-none" prepend-icon="mdi-refresh" @click="reload" :disabled="loading">
                    Recargar
                </v-btn>
            </div>
        </v-card>

        <!-- Empty state -->
        <v-fade-transition mode="out-in">
            <!-- LOADING -->
            <div v-if="loading" key="loading">
                <v-card rounded="xl" class="pa-6 premium-surface">
                    <div class="d-flex align-center justify-space-between mb-4">
                        <div class="text-subtitle-1 font-weight-bold">Cargando pedidos…</div>
                        <v-progress-circular indeterminate size="22" />
                    </div>

                    <div class="d-flex flex-column ga-3">
                        <v-skeleton-loader type="article" />
                        <v-skeleton-loader type="article" />
                        <v-skeleton-loader type="article" />
                    </div>
                </v-card>
            </div>

            <!-- ERROR -->
            <div v-else-if="loadError" key="error">
                <v-alert type="error" variant="tonal" rounded="lg" class="mb-4">
                    {{ loadError }}
                </v-alert>

                <v-btn color="primary" class="text-none" prepend-icon="mdi-refresh" @click="reload">
                    Reintentar
                </v-btn>
            </div>

            <!-- EMPTY -->
            <div v-else-if="filteredOrders.length === 0" key="empty">
                <v-card rounded="xl" class="pa-10 text-center premium-surface">
                    <v-icon icon="mdi-receipt-text-outline" size="56" class="mb-3" />

                    <div v-if="!hasAnyOrders" class="text-h6 font-weight-bold">
                        Aún no tienes pedidos
                    </div>
                    <div v-else class="text-h6 font-weight-bold">
                        No hay pedidos en esta sección
                    </div>

                    <div class="text-body-2 text-medium-emphasis mt-2">
                        <span v-if="!hasAnyOrders">
                            Cuando completes una compra, aparecerá aquí.
                        </span>
                        <span v-else>
                            Prueba con otro filtro (por ejemplo “Todos” o “Completados”).
                        </span>
                    </div>

                    <div class="d-flex justify-center mt-6">
                        <v-btn color="primary" class="text-none" @click="goShop">
                            Ir a la tienda
                        </v-btn>
                    </div>
                </v-card>
            </div>

            <!-- List -->
            <div v-else key="list">
                <TransitionGroup name="list" tag="div" class="orders-grid">
                    <v-card v-for="o in pagedOrders" :key="o.id" rounded="xl" class="pa-5 order-card premium-surface">
                        <div class="d-flex align-start justify-space-between ga-3">
                            <div class="min-w-0">
                                <div class="text-caption text-medium-emphasis">
                                    {{ formatDate(o.createdAt) }}
                                </div>

                                <div class="text-subtitle-1 font-weight-bold mt-1">
                                    Pedido {{ o.idShort }}
                                </div>

                                <div class="d-flex align-center ga-2 mt-2">
                                    <v-chip size="small" rounded="lg" variant="tonal" :color="statusColor(o.status)">
                                        {{ o.statusLabel }}
                                    </v-chip>

                                    <div v-if="o.itemsCount != null" class="text-body-2 text-medium-emphasis">
                                        · {{ o.itemsCount }} artículo(s)
                                    </div>
                                </div>

                                <div v-if="o.failureReason" class="text-body-2 mt-2">
                                    <span class="text-medium-emphasis">Motivo:</span>
                                    <span class="font-weight-bold"> {{ o.failureReason }}</span>
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="text-caption text-medium-emphasis">Total</div>
                                <div class="text-h6 font-weight-bold">
                                    {{ money(o.total ?? 0, o.currency || 'EUR') }}
                                </div>
                            </div>
                        </div>

                        <v-divider class="my-4" />

                        <div class="ga-2">
                            <v-btn variant="outlined" class="text-none" prepend-icon="mdi-eye-outline"
                                @click="openDetails(o)">
                                Ver detalles
                            </v-btn>
                        </div>
                    </v-card>
                </TransitionGroup>

                <v-fade-transition>
                    <div v-if="pageCount > 1" class="d-flex justify-center mt-6">
                        <v-pagination v-model="page" :length="pageCount" :total-visible="5" rounded="circle" />
                    </div>
                </v-fade-transition>
            </div>
        </v-fade-transition>

        <!-- Dialog detalles -->
        <!-- Dialog detalles (PRO) -->
        <v-dialog v-model="detailsOpen" max-width="720">
            <v-card rounded="xl" class="details-card">
                <!-- Header -->
                <v-card-title class="details-title">
                    <div class="d-flex align-center justify-space-between w-100">
                        <div class="d-flex align-center ga-3">
                            <v-avatar rounded="lg" size="44" class="details-icon">
                                <v-icon icon="mdi-receipt-text-outline" />
                            </v-avatar>

                            <div class="min-w-0">
                                <div class="text-subtitle-1 font-weight-bold">Detalles del pedido</div>
                                <div v-if="selected" class="text-caption text-medium-emphasis">
                                    Pedido {{ selected.idShort }} · {{ formatDate(selected.createdAt) }}
                                </div>
                            </div>
                        </div>

                        <v-btn icon variant="text" @click="detailsOpen = false" aria-label="Cerrar">
                            <v-icon icon="mdi-close" />
                        </v-btn>
                    </div>
                </v-card-title>

                <v-divider />

                <v-card-text v-if="selected" class="pt-5">
                    <!-- Status + total -->
                    <div class="d-flex flex-column flex-sm-row align-start justify-space-between ga-4 mb-5">
                        <div class="d-flex flex-column ga-2">
                            <div class="text-caption text-medium-emphasis">Estado</div>
                            <v-chip size="small" rounded="lg" variant="tonal" :color="statusColor(selected.status)">
                                {{ selected.statusLabel ?? selected.status }}
                            </v-chip>

                            <v-alert v-if="selected.failureReason" type="error" variant="tonal" rounded="lg"
                                density="compact" class="mt-2">
                                {{ selected.failureReason }}
                            </v-alert>
                        </div>

                        <v-card rounded="lg" class="total-pill pa-4" elevation="0">
                            <div class="text-caption text-medium-emphasis">Total</div>
                            <div class="text-h5 font-weight-bold mt-1">
                                {{ money(selected.total ?? 0, selected.currency || 'EUR') }}
                            </div>

                            <div v-if="selected.itemsCount != null" class="text-caption text-medium-emphasis mt-1">
                                {{ selected.itemsCount }} artículo(s)
                            </div>
                        </v-card>
                    </div>

                    <!-- Desglose -->
                    <v-card rounded="lg" class="breakdown-card pa-4" elevation="0">
                        <div class="d-flex align-center justify-space-between mb-3">
                            <div class="text-subtitle-2 font-weight-bold">Desglose</div>

                            <v-chip v-if="selected.couponDiscountTotal > 0" size="small" rounded="lg" variant="tonal"
                                color="success" prepend-icon="mdi-ticket-percent-outline">
                                Cupón aplicado
                            </v-chip>
                        </div>

                        <div class="d-flex justify-space-between mb-2">
                            <div class="text-body-2 text-medium-emphasis">Subtotal</div>
                            <div class="text-body-2 font-weight-medium">{{ money(selected.subtotal, selected.currency)
                            }}</div>
                        </div>

                        <div v-if="selected.discountTotal > 0" class="d-flex justify-space-between mb-2">
                            <div class="text-body-2 text-medium-emphasis">Descuento</div>
                            <div class="text-body-2 font-weight-medium">-{{ money(selected.discountTotal,
                                selected.currency) }}</div>
                        </div>

                        <div v-if="selected.couponDiscountTotal > 0" class="d-flex justify-space-between mb-2">
                            <div class="text-body-2 text-medium-emphasis">Cupón</div>
                            <div class="text-body-2 font-weight-medium">-{{ money(selected.couponDiscountTotal,
                                selected.currency) }}</div>
                        </div>

                        <div class="d-flex justify-space-between mb-2">
                            <div class="text-body-2 text-medium-emphasis">Envío</div>
                            <div class="text-body-2 font-weight-medium">
                                <span v-if="selected.shippingTotal === 0">Gratis</span>
                                <span v-else>{{ money(selected.shippingTotal ?? 0, selected.currency) }}</span>
                            </div>
                        </div>

                        <v-divider class="my-3" />

                        <div class="d-flex justify-space-between">
                            <div class="text-body-1 font-weight-bold">Total pagado</div>
                            <div class="text-body-1 font-weight-bold">
                                {{ money(selected.totalPaid ?? selected.total ?? 0, selected.currency) }}
                            </div>
                        </div>
                    </v-card>

                    <!-- Líneas -->
                    <v-card v-if="selected.lines?.length" rounded="lg" class="lines-card pa-4 mt-4" elevation="0">
                        <div class="d-flex align-center justify-space-between mb-3">
                            <div class="text-subtitle-2 font-weight-bold">Artículos</div>
                            <v-chip size="small" rounded="lg" variant="tonal">
                                {{ selected.lines.length }} línea(s)
                            </v-chip>
                        </div>

                        <div class="lines-list">
                            <div v-for="(l, i) in selected.lines" :key="`${l.product_id}-${i}`" class="line-row">
                                <div class="d-flex align-center ga-3">
                                    <v-avatar rounded="lg" size="44" class="bg-grey-lighten-4">
                                        <v-img v-if="lineImage(l)" :src="lineImage(l)" cover />
                                        <v-icon v-else icon="mdi-tshirt-crew-outline" />
                                    </v-avatar>

                                    <div class="flex-grow-1 min-w-0">
                                        <div class="text-body-2 font-weight-medium text-truncate">
                                            {{ lineName(l) }}
                                        </div>

                                        <div class="text-caption text-medium-emphasis">
                                            {{ l.quantity }} × {{ money(l.unit_price ?? 0, selected.currency ||
                                                'EUR') }}
                                        </div>

                                        <div v-if="lineSize(l) || lineColor(l)"
                                            class="text-caption text-medium-emphasis mt-1">
                                            <span v-if="lineSize(l)">Talla: {{ lineSize(l) }}</span>
                                            <span v-if="lineSize(l) && lineColor(l)"> · </span>
                                            <span v-if="lineColor(l)">Color: {{ lineColor(l) }}</span>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-body-2 font-weight-bold">
                                            {{ money(l.line_total ?? 0, selected.currency || 'EUR') }}
                                        </div>
                                    </div>
                                </div>

                                <v-divider class="my-3" />
                            </div>
                        </div>
                    </v-card>
                </v-card-text>
            </v-card>

            <!-- Snack -->
            <v-snackbar v-model="snack.open" :timeout="2200" rounded="lg">
                {{ snack.text }}
            </v-snackbar>
        </v-dialog>
    </v-container>
</template>

<script setup>
import { computed, ref, onMounted, watch, reactive } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const statusFilter = ref('ALL')
const detailsOpen = ref(false)
const selected = ref(null)

const statusOptions = [
    { value: 'ALL', label: 'Todos' },
    { value: 'COMPLETED', label: 'Completados' },
    { value: 'FAILED', label: 'Cancelados' },
    { value: 'UNKNOWN', label: 'Otros' },
]

const hasAnyOrders = computed(() => orders.value.length > 0)

const orders = ref([])
const loading = ref(false)
const loadError = ref('')

const page = ref(1)
const pageSize = 4

const snack = reactive({ open: false, text: '' })

const filteredOrders = computed(() => {
    const list = [...orders.value].sort((a, b) => (b.createdAt || 0) - (a.createdAt || 0))
    if (statusFilter.value === 'ALL') return list
    return list.filter(o => (o.status || 'UNKNOWN') === statusFilter.value)
})

const pageCount = computed(() => {
    const n = filteredOrders.value.length
    return Math.max(1, Math.ceil(n / pageSize))
})

const pagedOrders = computed(() => {
    const start = (page.value - 1) * pageSize
    return filteredOrders.value.slice(start, start + pageSize)
})

function normalizeStatus(orderStatus, paymentStatus) {
    if (paymentStatus === 'success' || orderStatus === 'paid') return 'COMPLETED'
    if (paymentStatus === 'failed' || orderStatus === 'cancelled') return 'FAILED'
    return 'UNKNOWN'
}

function mapApiOrder(o) {
    const id = String(o?.id ?? '')
    const createdAt = o?.created_at ? Date.parse(o.created_at) : Date.now()

    const paymentToken = o?.payment?.transaction_id ? String(o.payment.transaction_id) : ''
    const paymentStatus = o?.payment?.status ? String(o.payment.status) : null

    const statusKey = normalizeStatus(String(o?.status ?? ''), paymentStatus)

    const subtotal = Number(o?.subtotal ?? 0)
    const discountTotal = Number(o?.discount_total ?? 0)
    const couponDiscountTotal = Number(o?.coupon_discount_total ?? 0)

    const totalBase = Number(o?.total_base ?? (subtotal - discountTotal - couponDiscountTotal))
    const totalPaid = (o?.total_paid != null) ? Number(o.total_paid) : (o?.payment?.amount != null ? Number(o.payment.amount) : null)

    const total = (o?.total != null) ? Number(o.total) : (totalPaid ?? totalBase)

    const shippingTotal =
        (o?.shipping_total != null)
            ? Number(o.shipping_total)
            : (totalPaid != null ? Math.max(0, Number((totalPaid - totalBase).toFixed(2))) : null)

    const itemsCount = o?.items_count != null ? Number(o.items_count) : null

    return {
        id,
        idShort: id.length > 10 ? id.slice(0, 10) + '…' : id,
        createdAt,

        status: statusKey,
        statusLabel:
            statusKey === 'COMPLETED' ? 'COMPLETADO' :
                statusKey === 'FAILED' ? 'CANCELADO' :
                    'OTRO',

        currency: 'EUR',

        subtotal,
        discountTotal,
        couponDiscountTotal,
        totalBase,
        shippingTotal,
        totalPaid,

        total,

        itemsCount,
        token: paymentToken,
        tokenShort: paymentToken.length > 10 ? paymentToken.slice(0, 10) + '…' : paymentToken,

        failureReason: null,
        lines: Array.isArray(o?.lines) ? o.lines : [],
    }
}

async function copy(text) {
    try {
        await navigator.clipboard.writeText(String(text || ''))
        snack.text = 'Copiado al portapapeles'
        snack.open = true
    } catch {
        snack.text = 'No se pudo copiar'
        snack.open = true
    }
}

async function reload() {
    loading.value = true
    loadError.value = ''

    try {
        let resp
        try {
            resp = await axios.get('/api/orders')
        } catch (e) {
            if (e?.response?.status === 419) {
                await axios.get('/sanctum/csrf-cookie')
                resp = await axios.get('/api/orders')
            } else {
                throw e
            }
        }

        const list = resp?.data?.data ?? []
        orders.value = Array.isArray(list) ? list.map(mapApiOrder) : []
    } catch (e) {
        loadError.value =
            e?.response?.data?.message ||
            e?.message ||
            'No se pudieron cargar los pedidos.'
        orders.value = []
        console.error(e)
    } finally {
        loading.value = false
    }
}

function statusColor(s) {
    if (s === 'COMPLETED') return 'success'
    if (s === 'FAILED') return 'error'
    return 'warning'
}

function formatDate(ts) {
    const d = new Date(Number(ts || Date.now()))
    return new Intl.DateTimeFormat('es-ES', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(d)
}

function money(n, currency = 'EUR') {
    const v = Number(n || 0)
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(v)
}

function pickImageUrl(x) {
    if (!x) return null
    if (typeof x === 'string') return x
    return x.url ?? x.path ?? x.src ?? x.image_url ?? x.image ?? null
}

function lineImage(l) {
    // 1) Si el backend ya trae una imagen directa en la línea
    const direct = l?.image_path ?? l?.image_url ?? l?.image ?? null
    if (direct) return direct

    // 2) Si viene el product embebido con images
    const p = l?.product ?? null
    const imgs = Array.isArray(p?.images) ? p.images : []
    const first = imgs[0]
    const u = pickImageUrl(first)
    if (u) return u

    // 3) Fallback: nada
    return null
}

function lineName(l) {
    return l?.name ?? l?.product?.name ?? 'Producto'
}

function valueOf(v) {
    if (v == null) return null
    if (typeof v === 'string' || typeof v === 'number') return String(v)
    if (typeof v === 'object') return v.value ?? v.name ?? v.label ?? (v.id != null ? String(v.id) : null)
    return null
}

function lineSize(l) {
    return valueOf(l?.size ?? l?.size_value ?? l?.size_name)
}

function lineColor(l) {
    return valueOf(l?.color ?? l?.color_value ?? l?.color_name)
}

function openDetails(o) {
    selected.value = o
    detailsOpen.value = true
}

function goShop() {
    router.push({ name: 'shop' })
}

watch(statusFilter, () => {
    page.value = 1
})

watch(
    () => orders.value.length,
    () => {
        if (page.value > pageCount.value) page.value = 1
    }
)

watch(page, () => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
})

onMounted(() => reload())
</script>

<style scoped>
.premium-surface {
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: rgba(0, 0, 0, 0.01);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
}

.orders-grid {
    display: grid;
    gap: 14px;
}

.order-card {
    background: #fff;
}

.min-w-0 {
    min-width: 0;
}

.details-card {
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 18px 50px rgba(0, 0, 0, 0.12);
    background: #fff;
}

.details-title {
    padding: 18px 18px 14px 18px;
}

.details-icon {
    background: rgba(0, 0, 0, 0.04);
}

.total-pill {
    min-width: 220px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: rgba(0, 0, 0, 0.015);
}

.info-card,
.lines-card {
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: rgba(0, 0, 0, 0.01);
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

@media (min-width: 700px) {
    .info-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.info-item .label {
    font-size: 0.75rem;
    color: rgba(0, 0, 0, 0.55);
    margin-bottom: 4px;
}

.info-item .value {
    font-weight: 600;
}

.mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 360px;
    display: inline-block;
}

.details-card {
    background: #fff;
}

.details-title {
    padding: 16px 18px;
}

.details-icon {
    background: rgba(0, 0, 0, 0.04);
}

.total-pill {
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: rgba(0, 0, 0, 0.015);
    min-width: 180px;
}

.breakdown-card,
.payinfo-card,
.lines-card {
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: rgba(0, 0, 0, 0.012);
}

.lines-list .line-row:last-child .v-divider {
    display: none;
}

.list-enter-active,
.list-leave-active {
    transition: opacity 220ms ease, transform 220ms ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.list-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.list-move {
    transition: transform 220ms ease;
}
</style>
