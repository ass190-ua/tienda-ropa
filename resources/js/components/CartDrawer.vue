<template>
    <v-navigation-drawer :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)"
        location="right" temporary :width="drawerWidth" class="cart-drawer">
        <div class="drawer-inner">
            <!-- Header -->
            <div class="drawer-header d-flex align-start justify-space-between ga-3">
                <div>
                    <div class="text-h6 font-weight-bold">Carrito</div>
                    <div class="text-body-2 text-medium-emphasis">
                        {{ totalItems }} artículo(s)
                    </div>
                </div>

                <v-btn icon variant="text" aria-label="Cerrar" @click="emit('update:modelValue', false)"
                    :disabled="cart.syncing">
                    <v-icon icon="mdi-close" />
                </v-btn>
            </div>

            <v-divider />

            <!-- Content -->
            <div class="drawer-content">
                <v-fade-transition mode="out-in">
                    <!-- Empty -->
                    <div v-if="items.length === 0" key="empty" class="empty-state">
                        <v-icon icon="mdi-cart-outline" size="34" class="mb-2" />
                        <div class="text-subtitle-1 font-weight-bold">Tu carrito está vacío</div>
                        <div class="text-body-2 text-medium-emphasis">
                            Añade productos para verlos aquí.
                        </div>
                    </div>

                    <!-- Items -->
                    <div v-else key="items" :class="['items-wrap', { clearing: isClearing }]">
                        <TransitionGroup name="cart" tag="div" class="items-list">
                            <div v-for="it in items" :key="it.key" class="item-row">
                                <v-card rounded="xl" elevation="0" class="item-card" :class="{
                                    'item-out': av(it).state === 'OUT',
                                    'item-low': av(it).state === 'LOW',
                                }">
                                    <div class="d-flex ga-3">
                                        <v-img :src="itemImage(it)" width="74" height="74" cover class="thumb" />

                                        <div class="flex-grow-1">
                                            <div class="text-body-2 text-medium-emphasis">
                                                {{ itemBrand(it) }}
                                            </div>

                                            <div class="text-subtitle-2 font-weight-bold title-clamp">
                                                {{ itemName(it) }}
                                            </div>

                                            <div class="text-body-2 text-medium-emphasis mt-1">
                                                <span v-if="it.size">Talla: {{ it.size }}</span>
                                                <span v-if="it.size && it.color"> · </span>
                                                <span v-if="it.color">Color: {{ it.color }}</span>

                                                <div class="mt-2">
                                                    <v-chip v-if="!av(it).ok" size="x-small" variant="tonal"
                                                        rounded="lg"
                                                        :color="av(it).state === 'OUT' ? 'error' : 'warning'">
                                                        <template v-if="av(it).state === 'OUT'">
                                                            Agotado
                                                        </template>
                                                        <template v-else>
                                                            Stock insuficiente
                                                        </template>
                                                    </v-chip>
                                                </div>
                                            </div>

                                            <div class="d-flex align-center justify-space-between mt-3">
                                                <div class="text-primary font-weight-bold">
                                                    {{ money(lineTotal(it)) }}
                                                </div>

                                                <div class="d-flex align-center ga-1">
                                                    <v-btn icon variant="outlined" rounded="lg" size="small"
                                                        class="qty-btn" aria-label="Disminuir" @click="dec(it)"
                                                        :disabled="cart.syncing || (it.qty ?? 1) <= 1">
                                                        <v-icon icon="mdi-minus" />
                                                    </v-btn>

                                                    <div class="qty-pill">{{ it.qty ?? 1 }}</div>

                                                    <v-btn icon variant="outlined" rounded="lg" size="small"
                                                        class="qty-btn" aria-label="Aumentar" @click="inc(it)"
                                                        :disabled="cart.syncing || !canInc(it)">
                                                        <v-icon icon="mdi-plus" />
                                                    </v-btn>

                                                    <!-- Papelera roja -->
                                                    <v-btn icon variant="text" size="small" color="error"
                                                        class="trash-btn" aria-label="Eliminar" @click="remove(it)"
                                                        :disabled="cart.syncing">
                                                        <v-icon icon="mdi-trash-can-outline" />
                                                    </v-btn>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </v-card>
                            </div>
                        </TransitionGroup>
                    </div>
                </v-fade-transition>
            </div>

            <v-divider />

            <!-- Footer -->
            <div class="drawer-footer">
                <div class="d-flex align-center justify-space-between mb-3">
                    <div class="text-subtitle-2 text-medium-emphasis">Subtotal</div>
                    <div class="text-h6 font-weight-bold">{{ money(subtotal) }}</div>
                </div>

                <v-btn v-if="!isCartPage" block color="primary" class="mb-2" @click="goToCart">
                    Ir al carrito
                </v-btn>

                <!-- Confirmación inline para vaciar -->
                <v-expand-transition>
                    <div v-if="confirmClear" class="confirm-wrap">
                        <v-alert type="error" variant="tonal" density="comfortable" class="confirm-alert">
                            <div class="d-flex align-start justify-space-between ga-3">
                                <div>
                                    <div class="font-weight-bold">¿Vaciar el carrito?</div>
                                    <div class="text-body-2">
                                        Se eliminarán {{ totalItems }} artículo(s).
                                    </div>
                                </div>

                                <v-btn icon variant="text" color="error" aria-label="Cerrar confirmación"
                                    @click="confirmClear = false">
                                    <v-icon icon="mdi-close" />
                                </v-btn>
                            </div>

                            <div class="d-flex ga-2 mt-3">
                                <v-btn variant="outlined" rounded="lg" class="text-none flex-grow-1"
                                    @click="confirmClear = false">
                                    Cancelar
                                </v-btn>

                                <v-btn color="error" variant="flat" rounded="lg" class="text-none flex-grow-1"
                                    @click="clearAllConfirmed" :disabled="cart.syncing">
                                    Vaciar
                                </v-btn>
                            </div>
                        </v-alert>
                    </div>
                </v-expand-transition>

                <!-- Botón “Vaciar carrito” -->
                <v-btn v-if="items.length > 0" :disabled="confirmClear || cart.syncing" color="error" variant="tonal"
                    rounded="lg" class="text-none w-100" @click="confirmClear = true">
                    <v-icon icon="mdi-trash-can-outline" class="mr-2" />
                    Vaciar carrito
                </v-btn>
            </div>
        </div>

        <v-snackbar :model-value="!!cart.stockWarning" @update:model-value="v => { if (!v) cart.clearStockWarning() }"
            timeout="2500">
            {{ cart.stockWarning?.message }}
        </v-snackbar>
    </v-navigation-drawer>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useCartStore } from '../stores/cart'

const route = useRoute()
const router = useRouter()

const props = defineProps({
    modelValue: { type: Boolean, required: true },
})

const emit = defineEmits(['update:modelValue'])

const cart = useCartStore()
const { smAndDown } = useDisplay()

const drawerWidth = computed(() => (smAndDown.value ? 390 : 420))

const confirmClear = ref(false)
const isClearing = ref(false)

const isCartPage = computed(() => route.path === '/cart' || route.name === 'cart')

const items = computed(() => cart.items)

const totalItems = computed(() => cart.totalItems)

const subtotal = computed(() => cart.subtotal)

function money(n) {
    const v = Number(n || 0)
    return v.toFixed(2) + '€'
}

function unitPrice(it) {
    return Number(it.product?.price ?? it.price ?? 0)
}

function lineTotal(it) {
    return unitPrice(it) * Number(it.qty ?? 1)
}

function itemName(it) {
    return it.product?.name ?? it.name ?? 'Producto'
}

function itemBrand(it) {
    return it.product?.brand ?? it.brand ?? ''
}

function itemImage(it) {
    const p = it.product ?? it
    const imgs = Array.isArray(p?.images) ? p.images : []
    const first = imgs[0]
    if (typeof first === 'string') return first
    return (
        first?.url ??
        first?.path ??
        first?.src ??
        p?.image ?? p?.image_url ?? it.image ?? ''
    )
}

function lineStatus(it) {
    const pid = Number(it.product?.id ?? it.product_id ?? it.productId ?? 0)
    const qty = Number(it.qty ?? it.quantity ?? 0)
    return cart.lineAvailabilityStatus({ product_id: pid, qty })
}

function canInc(it) {
    const st = lineStatus(it)
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
    await cart.dec(it.key)
}

async function remove(it) {
    await cart.removeItem(it.key)
}

function goToCart() {
    emit('update:modelValue', false)
    router.push('/cart')
}

async function clearCart() {
    await cart.clear()
}

async function clearAllConfirmed() {
    // animación de “vaciar todo”
    isClearing.value = true
    await new Promise((r) => setTimeout(r, 180))
    await clearCart()
    confirmClear.value = false
    await new Promise((r) => setTimeout(r, 50))
    isClearing.value = false
}

function av(it) {
    return cart.lineAvailabilityStatus(it)
}

watch(
    () => props.modelValue,
    async (open) => {
        if (open) {
            confirmClear.value = false
            isClearing.value = false

            try {
                await cart.pullFromBackend?.(true)
            } catch { }

            try {
                await cart.refreshAvailabilityForCart?.()
            } catch { }
        } else {
            confirmClear.value = false
            isClearing.value = false
        }
    }
)

watch(() => cart.items, async () => {
    await cart.refreshAvailabilityForCart()
}, { deep: true })
</script>


<style scoped>
.cart-drawer {
    border-left: 1px solid rgba(0, 0, 0, 0.08);
}

.drawer-inner {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.drawer-header {
    padding: 16px 16px 12px 16px;
}

.drawer-content {
    flex: 1;
    overflow: auto;
    padding: 14px 16px;
}

.drawer-footer {
    padding: 14px 16px 16px 16px;
}

.empty-state {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 6px;
    opacity: 0.9;
}

.empty-state :deep(.v-icon) {
    display: block;
    line-height: 1;
}

.items-wrap {
    transition: opacity 180ms ease, transform 180ms ease;
}

.items-wrap.clearing {
    opacity: 0;
    transform: translateX(12px);
}

.items-list {
    position: relative;
    display: grid;
    gap: 12px;
}

.item-row {
    position: relative;
}

.item-card {
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 18px;
    padding: 12px;
    background: rgba(0, 0, 0, 0.012);
}

.item-out {
    border: 1px solid rgba(244, 67, 54, 0.30) !important;
    background: rgba(244, 67, 54, 0.04) !important;
}

.item-low {
    border: 1px solid rgba(255, 152, 0, 0.30) !important;
    background: rgba(255, 152, 0, 0.04) !important;
}

.thumb {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.title-clamp {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.qty-btn {
    width: 34px;
    height: 34px;
    min-width: 34px;
}

.qty-pill {
    min-width: 30px;
    text-align: center;
    font-weight: 800;
}

.trash-btn {
    border-radius: 10px;
}

.trash-btn:hover {
    background: rgba(244, 67, 54, 0.08);
}

/* Botón vaciar: que parezca botón de verdad */
.confirm-wrap {
    margin-bottom: 10px;
}

.confirm-alert {
    border-radius: 14px;
}

/* Animaciones de lista */
.cart-enter-active,
.cart-leave-active {
    transition: opacity 220ms ease, transform 220ms ease;
}

.cart-enter-from {
    opacity: 0;
    transform: translateX(10px);
}

.cart-leave-to {
    opacity: 0;
    transform: translateX(26px);
    /* se va hacia la derecha */
}

/* Esto hace que los de abajo “suban” con animación */
.cart-move {
    transition: transform 220ms ease;
}

/* Para que el “leave” no rompa el cálculo del FLIP */
.cart-leave-active {
    position: absolute;
    left: 0;
    right: 0;
}
</style>
