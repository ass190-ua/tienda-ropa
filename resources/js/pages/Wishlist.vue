<template>
    <v-container class="py-6 px-8">
        <div class="d-flex flex-column flex-md-row align-start align-md-center justify-space-between ga-4 mb-6">
            <div>
                <h1 class="text-h4 font-weight-bold mb-1">Wishlist</h1>
                <div class="text-medium-emphasis">
                    <span v-if="items.length">
                        Guardados: <strong>{{ items.length }}</strong> {{ items.length === 1 ? 'producto' : 'productos'
                        }}
                    </span>
                    <span v-else>
                        Aún no has guardado ningún producto.
                    </span>
                </div>
            </div>

            <div class="d-flex ga-2" v-if="items.length">
                <v-btn variant="outlined" rounded="lg" @click="askClear" :loading="wishlist.loading">
                    Vaciar lista
                </v-btn>
                <v-btn color="primary" rounded="lg" @click="router.push('/shop')">
                    Ir a tienda
                </v-btn>
            </div>

            <div v-else>
                <v-btn v-if="!loading" color="primary" rounded="lg" @click="router.push('/shop')">
                    Explorar tienda
                </v-btn>
            </div>
        </div>
        <v-progress-linear v-if="loading" indeterminate class="mb-4" />

        <!-- Error -->
        <v-alert v-if="wishlist.error" type="error" variant="tonal" class="mb-4">
            {{ wishlist.error }}
        </v-alert>

        <!-- Empty -->
        <v-card v-if="!loading && items.length === 0" rounded="xl" class="pa-6 text-center" elevation="0" border>
            <v-icon icon="mdi-heart-outline" size="48" class="mb-3" color="primary" />
            <div class="text-h6 font-weight-bold mb-2">Tu wishlist está vacía</div>
            <div class="text-medium-emphasis mb-5">
                Guarda productos para verlos aquí y comprarlos cuando quieras.
            </div>

            <div class="d-flex justify-center ga-2 flex-wrap">
                <v-btn color="primary" rounded="lg" @click="router.push('/shop')">Ir a tienda</v-btn>
                <v-btn variant="outlined" rounded="lg" @click="router.push('/')">Volver a Home</v-btn>
            </div>
        </v-card>

        <!-- Grid por categorías -->
        <div v-if="!loading && items.length" class="mt-2">
            <section v-for="sec in sections" :key="sec.id" class="mb-10">
                <div class="d-flex align-center justify-space-between mb-3">
                    <h2 class="text-h6 font-weight-bold">{{ sec.title }}</h2>
                    <div class="text-medium-emphasis" v-if="sec.items.length">
                        {{ sec.items.length }} {{ sec.items.length === 1 ? 'prenda' : 'prendas' }}
                    </div>
                </div>

                <transition-group name="fade-move" tag="div" class="v-row v-row--dense">
                    <div v-for="p in sectionPagedItems(sec)" :key="p.id"
                        class="v-col-12 v-col-sm-6 v-col-md-4 v-col-lg-3">
                        <div class="d-flex flex-column h-100">
                            <ProductCard :product="p" wishlist-controlled @quick-view="openQuickView"
                                @wishlist-click="onWishlistClick" />
                        </div>
                    </div>
                </transition-group>

                <div class="d-flex justify-center mt-4" v-if="sectionPageCount(sec) > 1">
                    <v-pagination :model-value="getSectionPage(sec.id)"
                        @update:model-value="setSectionPage(sec.id, $event)" :length="sectionPageCount(sec)"
                        :total-visible="5" prev-icon="mdi-chevron-left" next-icon="mdi-chevron-right" rounded="lg" />
                </div>

            </section>
        </div>


        <!-- Skeleton mientras carga -->
        <v-row v-else-if="loading" class="mt-2" dense>
            <v-col v-for="n in 4" :key="n" cols="12" sm="6" md="4" lg="3">
                <v-card rounded="xl" elevation="2" class="h-100">
                    <v-skeleton-loader type="image, article, actions" />
                </v-card>
            </v-col>
        </v-row>

        <VistaRapidaDialog v-model="quickViewOpen" :product="quickProduct" :colors-palette="colors"
            @add="onAddToCart" />

        <v-snackbar v-model="snackbar.open" timeout="2500" color="success" rounded="lg">
            {{ snackbar.text }}
        </v-snackbar>

        <v-dialog v-model="confirmOpen" max-width="420">
            <v-card rounded="xl">
                <v-card-title class="text-h6 font-weight-bold">
                    Quitar de favoritos
                </v-card-title>

                <v-card-text class="text-medium-emphasis">
                    ¿Seguro que quieres eliminar <strong>{{ pendingRemove?.name }}</strong> de tu wishlist?
                </v-card-text>

                <v-card-actions class="px-6 pb-5">
                    <v-spacer />
                    <v-btn variant="text" @click="cancelRemove">
                        Cancelar
                    </v-btn>
                    <v-btn color="error" variant="flat" rounded="lg" @click="confirmRemove" :loading="wishlist.loading">
                        Quitar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="confirmClearOpen" max-width="420">
            <v-card rounded="xl">
                <v-card-title class="text-h6 font-weight-bold">
                    Vaciar wishlist
                </v-card-title>

                <v-card-text class="text-medium-emphasis">
                    ¿Seguro que quieres vaciar toda tu wishlist? Esta acción no se puede deshacer.
                </v-card-text>

                <v-card-actions class="px-6 pb-5">
                    <v-spacer />
                    <v-btn variant="text" @click="cancelClear">
                        Cancelar
                    </v-btn>
                    <v-btn color="error" variant="flat" rounded="lg" @click="confirmClear" :loading="wishlist.loading">
                        Vaciar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>


<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { useWishlistStore } from '../stores/wishlist'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import VistaRapidaDialog from '../components/VistaRapidaDialog.vue'
import ProductCard from '../components/ProductCard.vue'


const router = useRouter()
const auth = useAuthStore()
const wishlist = useWishlistStore()
const cart = useCartStore()

const groupedBase = ref([])
const groupedLoading = ref(false)
let groupedTimer = null


const prendasFavoritas = computed(() => {
    return groupedBase.value.filter(p => wishlist.isGroupInWishlist(p.product_ids))
})

const items = computed(() => prendasFavoritas.value)
const CATEGORY_SECTIONS = [
    { id: 39, title: 'Mujer' },
    { id: 40, title: 'Hombre' },
    { id: 41, title: 'Niño' },
    { id: 42, title: 'Niña' },
    { id: 43, title: 'Zapatos Mujer' },
    { id: 44, title: 'Zapatos Hombre' },
    { id: 45, title: 'Zapatos Niño' },
    { id: 46, title: 'Zapatos Niña' },
]

function getCategoryId(p) {
    const n = Number(p?.category_id ?? p?.categoryId ?? p?.category?.id ?? p?.category ?? null)
    return Number.isFinite(n) ? n : null
}

const sections = computed(() => {
    const byId = new Map(CATEGORY_SECTIONS.map(s => [s.id, []]))
    const others = []

    for (const p of items.value) {
        const cid = getCategoryId(p)
        if (cid != null && byId.has(cid)) byId.get(cid).push(p)
        else others.push(p)
    }

    const out = CATEGORY_SECTIONS
        .map(s => ({ ...s, items: byId.get(s.id) }))
        .filter(s => s.items.length)

    if (others.length) out.push({ id: 'otros', title: 'Otros', items: others })
    return out
})

const PER_SECTION = 4
const pageBySection = ref({}) // { [secId]: pageNumber }

function getSectionPage(secId) {
    return Number(pageBySection.value?.[secId] ?? 1)
}

function setSectionPage(secId, n) {
    pageBySection.value = { ...pageBySection.value, [secId]: Number(n) }
}

function sectionPageCount(sec) {
    return Math.ceil((sec?.items?.length ?? 0) / PER_SECTION) || 1
}

function sectionPagedItems(sec) {
    const page = getSectionPage(sec.id)
    const start = (page - 1) * PER_SECTION
    return sec.items.slice(start, start + PER_SECTION)
}

const initialLoading = ref(true)

const loading = computed(() =>
    !auth.initialized || initialLoading.value
)


const quickViewOpen = ref(false)
const quickProduct = ref(null)

const confirmOpen = ref(false)
const pendingRemove = ref(null)

const confirmClearOpen = ref(false)

const snackbar = ref({ open: false, text: '' })

const colorHexMap = {
    Azul: '#2F6FED',
    Rojo: '#E53935',
    Verde: '#43A047',
    Amarillo: '#FDD835',
    Morado: '#8E24AA',
    Negro: '#111111',
    Blanco: '#FFFFFF',
    Beige: '#D6C3A5',
    Gris: '#9E9E9E',
    Marrón: '#6D4C41',
    Rosa: '#EC407A',
}
const colors = computed(() =>
    Object.entries(colorHexMap).map(([name, hex]) => ({ name, hex }))
)

onMounted(async () => {
    if (!auth.initialized) {
        await auth.fetchUser()
    }

    if (!auth.isAuthenticated) {
        router.push({ name: 'login', query: { redirect: '/wishlist' } })
        return
    }

    await wishlist.fetchWishlist()

    console.log('[Wishlist.vue] wishlist.items (raw):', wishlist.items)
    console.log('[Wishlist.vue] wishlist.ids:', wishlist.items.map(p => p?.id))

    await fetchGroupedBase()
    initialLoading.value = false
})

watch(
    () => sections.value.map(s => `${s.id}:${s.items.length}`).join('|'),
    () => {
        const next = { ...pageBySection.value }
        for (const sec of sections.value) {
            const max = sectionPageCount(sec)
            const cur = Number(next[sec.id] ?? 1)
            if (cur > max) next[sec.id] = max
            if (cur < 1) next[sec.id] = 1
        }
        pageBySection.value = next
    }
)


watch(
    () => wishlist.items.map(x => Number(x.product_id ?? x.id ?? x.product?.id)).join(','),
    async () => {
        if (!auth.isAuthenticated) return
        if (groupedTimer) clearTimeout(groupedTimer)
        groupedTimer = setTimeout(() => {
            fetchGroupedBase()
        }, 120)
    }
)

watch(
    [() => wishlist.items, () => groupedBase.value],
    () => {
        console.log('[Wishlist.vue] groupedBase.length:', groupedBase.value.length)
        console.log('[Wishlist.vue] prendasFavoritas.length:', prendasFavoritas.value.length)

        if (groupedBase.value[0]) {
            console.log('[Wishlist.vue] sample group product_ids:', groupedBase.value[0]?.product_ids)
        }
    },
    { deep: true }
)

async function fetchGroupedBase() {
    const productIds = wishlist.items
        .map(p => Number(p?.id))
        .filter(n => Number.isFinite(n))

    console.log('[Wishlist.vue] productIds -> grouped-by-ids:', productIds)

    if (productIds.length === 0) {
        groupedBase.value = []
        return
    }

    groupedLoading.value = true
    try {
        const payload = { ids: productIds, product_ids: productIds }
        console.log('[Wishlist.vue] POST /api/products/grouped-by-ids payload:', payload)

        const { data } = await axios.post('/api/products/grouped-by-ids', payload)

        console.log('[Wishlist.vue] grouped-by-ids response raw:', data)

        groupedBase.value = (Array.isArray(data) ? data : (data?.items ?? data?.products ?? [])).map(p => {
            let ids =
                Array.isArray(p.product_ids) ? p.product_ids :
                    Array.isArray(p.productIds) ? p.productIds :
                        typeof p.product_ids === 'string' ? p.product_ids.split(',') :
                            []

            ids = ids.map(x => Number(x)).filter(n => Number.isFinite(n))

            return {
                ...p,
                representative_id: p.representative_id ?? p.representativeId ?? p.id,
                product_ids: ids.length ? ids : [Number(p.id)].filter(n => Number.isFinite(n)),
            }
        })

        console.log('[Wishlist.vue] groupedBase normalized:', groupedBase.value)
    } catch (e) {
        console.error('[Wishlist.vue] grouped-by-ids ERROR:', e?.response?.data ?? e)
        groupedBase.value = []
    } finally {
        groupedLoading.value = false
    }
}

function openQuickView(prenda) {
    quickProduct.value = prenda
    quickViewOpen.value = true
}

async function removePrenda(prenda) {
    for (const id of prenda.product_ids ?? []) {
        if (wishlist.isInWishlist(id)) {
            await wishlist.remove(id)
        }
    }
}

function askRemove(prenda) {
    pendingRemove.value = prenda
    confirmOpen.value = true
}

function cancelRemove() {
    confirmOpen.value = false
    pendingRemove.value = null
}

async function confirmRemove() {
    if (!pendingRemove.value) return
    await removePrenda(pendingRemove.value)
    confirmOpen.value = false
    pendingRemove.value = null
}

function askClear() {
    confirmClearOpen.value = true
}

function cancelClear() {
    confirmClearOpen.value = false
}

async function onClear() {
    await wishlist.clear()
    groupedBase.value = []
}

async function confirmClear() {
    await onClear()
    confirmClearOpen.value = false
}


function onAddToCart(payload) {
    cart.addToCart(payload)
    snackbar.value.text = `Añadido: ${payload.product?.name} (x${payload.qty})`
    snackbar.value.open = true
}

function onWishlistClick(payload) {
    const prenda = payload?.product
    if (!prenda) return

    // Si NO está en wishlist -> añadir directo (sin confirmación)
    if (!payload.inWishlist) {
        wishlist.toggleGroup(payload.product_ids, payload.representative_id)
        return
    }

    // Si YA está en wishlist -> pedir confirmación
    pendingRemove.value = prenda
    confirmOpen.value = true
}

</script>


<style scoped>
/* Entrada / salida */
.fade-move-enter-active,
.fade-move-leave-active {
  transition: opacity 220ms ease, transform 220ms ease;
  will-change: opacity, transform;
}

/* Recolocación del resto (cuando desaparece una card) */
.fade-move-move {
  transition: transform 220ms ease;
  will-change: transform;
}

.fade-move-enter-from {
  opacity: 0;
  transform: translateY(10px) scale(0.985);
}

.fade-move-leave-to {
  opacity: 0;
  transform: scale(0.96);
}

</style>
