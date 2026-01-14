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
                <v-btn color="primary" rounded="lg" @click="router.push('/shop')">
                    Explorar tienda
                </v-btn>
            </div>
        </div>

        <!-- Loading -->
        <v-alert v-if="loading" type="info" variant="tonal" class="mb-4">
            Cargando wishlist...
        </v-alert>

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

        <!-- Grid -->
        <v-row v-if="!loading && items.length" class="mt-2" dense>
            <v-col v-for="p in pagedItems" :key="p.id" cols="12" sm="6" md="4" lg="3">
                <v-card rounded="xl" elevation="2" class="h-100 d-flex flex-column">
                    <div class="img-wrap">
                        <v-img :src="productImage(p) || undefined" height="230" cover class="rounded-xl product-img"
                            role="button" tabindex="0" @click="openQuickView(p)"
                            @keydown.enter.prevent="openQuickView(p)">
                        </v-img>
                    </div>

                    <v-card-text class="pb-0">
                        <div class="text-subtitle-1 font-weight-bold">
                            {{ p.name }}
                        </div>
                        <div class="text-medium-emphasis mt-1">
                            {{ formatPrice(p.price) }}
                        </div>
                    </v-card-text>

                    <v-spacer />

                    <v-card-actions class="pa-4 pt-3 d-flex ga-2">
                        <v-btn color="primary" variant="flat" rounded="lg" class="flex-grow-1"
                            @click="openQuickView(p)">
                            Vista rápida
                        </v-btn>

                        <v-btn variant="outlined" rounded="lg" color="error" @click="askRemove(p)"
                            :loading="wishlist.loading">
                            <v-icon icon="mdi-delete-outline" />
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>

        <div class="pagination-wrap" v-if="pageCount > 1">
            <v-pagination v-model="page" :length="pageCount" :total-visible="5" prev-icon="mdi-chevron-left"
                next-icon="mdi-chevron-right" rounded="lg" />
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
import VistaRapidaDialog from '../components/VistaRapidaDialog.vue'

const router = useRouter()
const wishlist = useWishlistStore()
const cart = useCartStore()

const items = computed(() => prendasFavoritas.value)

const loading = ref(false)
const groupedBase = ref([])

const quickViewOpen = ref(false)
const quickProduct = ref(null)

const confirmOpen = ref(false)
const pendingRemove = ref(null)

const prendasFavoritas = computed(() => {
    return groupedBase.value.filter(p => wishlist.isGroupInWishlist(p.product_ids))
})

const perPage = 8
const page = ref(1)
const pageCount = computed(() => Math.ceil(items.value.length / perPage) || 1)
const pagedItems = computed(() => {
    const start = (page.value - 1) * perPage
    return items.value.slice(start, start + perPage)
})

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

const snackbar = ref({ open: false, text: '' })

const confirmClearOpen = ref(false)


onMounted(async () => {
    loading.value = true
    try {
        await wishlist.fetchWishlist()
        console.log('[Wishlist] wishlist.items:', wishlist.items)

        await fetchGroupedBase()
    } catch (e) {
        console.error('[Wishlist] error onMounted:', e)
    } finally {
        loading.value = false
    }
})

watch(
    () => items.value.length,
    () => {
        if (page.value > pageCount.value) page.value = pageCount.value
    }
)


async function fetchGroupedBase() {
    const ids = wishlist.items.map(p => Number(p.id)).filter(n => Number.isFinite(n))
    const { data } = await axios.post('/api/products/grouped-by-ids', { ids })

    groupedBase.value = (Array.isArray(data) ? data : []).map(p => ({
        ...p,
        representative_id: p.representative_id ?? p.id,
        product_ids: Array.isArray(p.product_ids) ? p.product_ids : [p.id],
    }))
}

function openQuickView(prenda) {
    console.log('[Wishlist] openQuickView prenda:', prenda)
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

function formatPrice(value) {
    const n = Number(value ?? 0)
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n)
}

function productImage(p) {
    const img = p?.images?.[0]
    const url = img?.url || img?.image_url || img?.path || null
    return url || fallbackImage(p)
}

function fallbackImage(p) {
    const id = p?.representative_id ?? p?.id ?? 'x'
    return `https://picsum.photos/seed/tiendamoda-${id}-a/800/700`
}

function askClear() {
    confirmClearOpen.value = true
}

function cancelClear() {
    confirmClearOpen.value = false
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
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

.img-wrap {
    position: relative;
}

.product-img {
    cursor: pointer;
}

.pagination-wrap {
    width: 100%;
    display: flex;
    justify-content: center;
    margin-top: 24px;
}

.pagination-wrap :deep(.v-pagination) {
    min-width: 340px;
}

.pagination-wrap :deep(.v-pagination__list) {
    flex-wrap: nowrap;
}
</style>
