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
                    {{ filteredOrders.length }} pedido(s)
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
                        @click="statusFilter = opt.value">
                        {{ opt.label }}
                    </v-chip>
                </div>

                <v-btn variant="text" class="text-none" prepend-icon="mdi-refresh" @click="reload">
                    Recargar
                </v-btn>
            </div>
        </v-card>

        <!-- Empty state -->
        <v-fade-transition mode="out-in">
            <div v-if="filteredOrders.length === 0" key="empty">
                <v-card rounded="xl" class="pa-10 text-center premium-surface">
                    <v-icon icon="mdi-receipt-text-outline" size="56" class="mb-3" />
                    <div class="text-h6 font-weight-bold">Aún no tienes pedidos</div>
                    <div class="text-body-2 text-medium-emphasis mt-2">
                        Cuando completes una compra, aparecerá aquí.
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
                    <v-card v-for="o in filteredOrders" :key="o.id" rounded="xl"
                        class="pa-5 order-card premium-surface">
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
                                        {{ o.status }}
                                    </v-chip>

                                    <div v-if="o.itemsCount != null" class="text-body-2 text-medium-emphasis">
                                        · {{ o.itemsCount }} item(s)
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

                        <div class="d-flex flex-column flex-sm-row justify-space-between ga-2">
                            <v-btn variant="outlined" class="text-none" prepend-icon="mdi-eye-outline"
                                @click="openDetails(o)">
                                Ver detalles
                            </v-btn>

                            <v-btn variant="text" class="text-none" prepend-icon="mdi-storefront-outline"
                                @click="goShop">
                                Seguir comprando
                            </v-btn>
                        </div>
                    </v-card>
                </TransitionGroup>
            </div>
        </v-fade-transition>

        <!-- Dialog detalles -->
        <v-dialog v-model="detailsOpen" max-width="560">
            <v-card rounded="xl">
                <v-card-title class="text-h6 font-weight-bold">
                    Detalles del pedido
                </v-card-title>

                <v-card-text v-if="selected">
                    <div class="d-flex align-center justify-space-between mb-3">
                        <div class="text-body-2 text-medium-emphasis">ID</div>
                        <div class="font-weight-bold">{{ selected.id }}</div>
                    </div>

                    <div class="d-flex align-center justify-space-between mb-3">
                        <div class="text-body-2 text-medium-emphasis">Fecha</div>
                        <div class="font-weight-bold">{{ formatDate(selected.createdAt) }}</div>
                    </div>

                    <div class="d-flex align-center justify-space-between mb-3">
                        <div class="text-body-2 text-medium-emphasis">Estado</div>
                        <v-chip size="small" rounded="lg" variant="tonal" :color="statusColor(selected.status)">
                            {{ selected.status }}
                        </v-chip>
                    </div>

                    <div class="d-flex align-center justify-space-between mb-3">
                        <div class="text-body-2 text-medium-emphasis">Total</div>
                        <div class="font-weight-bold">
                            {{ money(selected.total ?? 0, selected.currency || 'EUR') }}
                        </div>
                    </div>

                    <div class="d-flex align-center justify-space-between mb-3">
                        <div class="text-body-2 text-medium-emphasis">Token</div>
                        <div class="font-weight-bold">{{ selected.tokenShort }}</div>
                    </div>

                    <v-alert v-if="selected.failureReason" type="error" variant="tonal" rounded="lg" class="mt-4">
                        {{ selected.failureReason }}
                    </v-alert>
                </v-card-text>

                <v-card-actions class="px-4 pb-4">
                    <v-spacer />
                    <v-btn class="text-none" variant="text" @click="detailsOpen = false">
                        Cerrar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const statusFilter = ref('ALL')
const detailsOpen = ref(false)
const selected = ref(null)

const statusOptions = [
    { value: 'ALL', label: 'Todos' },
    { value: 'COMPLETED', label: 'Completados' },
    { value: 'FAILED', label: 'Fallidos' },
    { value: 'UNKNOWN', label: 'Otros' },
]

const orders = ref([])

const filteredOrders = computed(() => {
    const list = [...orders.value].sort((a, b) => (b.createdAt || 0) - (a.createdAt || 0))
    if (statusFilter.value === 'ALL') return list
    return list.filter(o => (o.status || 'UNKNOWN') === statusFilter.value)
})

function ordersKeyFor(userId) {
  return userId ? `tiendamoda_orders_u${userId}` : 'tiendamoda_orders_guest'
}

const STORAGE_KEY = computed(() => ordersKeyFor(auth.user?.id ?? null))

function reload() {
    orders.value = loadOrders()
}

function loadOrders() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY.value)
        if (!raw) return []
        const parsed = JSON.parse(raw)
        if (!Array.isArray(parsed)) return []
        return parsed.map(normalizeOrder).filter(Boolean)
    } catch {
        return []
    }
}

watch(
    () => STORAGE_KEY.value,
    () => reload(),
    { immediate: true }
)

function normalizeOrder(o) {
    if (!o) return null
    const id = String(o.id || o.token || '')
    const token = String(o.token || o.id || '')
    const createdAt = Number(o.createdAt || Date.now())
    const status = String(o.status || 'UNKNOWN')
    const total = o.total != null ? Number(o.total) : null
    const currency = String(o.currency || 'EUR')

    return {
        id,
        idShort: id.length > 10 ? id.slice(0, 10) + '…' : id,
        token,
        tokenShort: token.length > 10 ? token.slice(0, 10) + '…' : token,
        createdAt,
        status,
        total,
        currency,
        itemsCount: o.itemsCount ?? null,
        failureReason: o.failureReason ?? null,
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

function openDetails(o) {
    selected.value = o
    detailsOpen.value = true
}

function goShop() {
    router.push({ name: 'shop' })
}
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
