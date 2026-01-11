<template>
    <div class="search-wrap">
        <!-- Cabecera -->
        <div class="d-flex flex-column flex-md-row align-start align-md-center justify-space-between ga-2 mb-4">
            <div>
                <div class="text-h5 font-weight-bold">
                    <template v-if="hasQuery">
                        Resultados para “<span class="quote">{{ query }}</span>”
                    </template>
                    <template v-else>
                        {{ contextTitle }}
                    </template>
                </div>

                <div class="text-medium-emphasis text-body-2">
                    Mostrando {{ showingLabel }} de {{ meta.total }}
                    <template v-if="pageCount > 1"> · Página {{ page }} de {{ pageCount }}</template>
                </div>
            </div>

            <div class="d-flex align-center ga-3">
                <div class="text-body-2 text-medium-emphasis">Ordenar:</div>
                <v-select v-model="sort" :items="sortItems" density="comfortable" variant="outlined" hide-details
                    class="sort" />
            </div>
        </div>

        <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
            {{ error }}
        </v-alert>

        <v-row class="gy-6">
            <!-- Filtros (sticky en desktop) -->
            <v-col cols="12" md="3">
                <div class="filters-sticky">
                    <v-card rounded="xl" elevation="2" class="pa-4 filter-card">
                        <div class="d-flex align-center justify-space-between mb-3">
                            <div class="text-subtitle-1 font-weight-bold d-flex align-center ga-2">
                                <v-icon icon="mdi-tune-variant" size="18" class="text-medium-emphasis" />
                                <span>Filtrar</span>
                                <v-chip v-if="activeFiltersCount > 0" size="x-small" color="primary" variant="tonal"
                                    class="ml-1">
                                    {{ activeFiltersCount }}
                                </v-chip>
                            </div>

                            <v-btn variant="text" class="text-none" :disabled="!hasAnyFilters" @click="clearAll">
                                Limpiar
                            </v-btn>
                        </div>

                        <div class="filter-section">
                            <div class="text-body-2 font-weight-bold mb-2">Talla</div>
                            <div class="d-flex flex-wrap ga-2">
                                <v-btn v-for="s in sizes" :key="s" size="small" rounded="lg" class="text-none"
                                    :variant="selectedSizes.includes(s) ? 'flat' : 'outlined'"
                                    :color="selectedSizes.includes(s) ? 'primary' : undefined" @click="toggleSize(s)">
                                    {{ s }}
                                </v-btn>
                            </div>
                        </div>

                        <v-divider class="my-4" />

                        <div class="filter-section">
                            <div class="text-body-2 font-weight-bold mb-2">Color</div>
                            <div class="d-flex flex-wrap ga-2">
                                <v-btn v-for="c in colors" :key="c.name" icon size="small"
                                    :variant="selectedColors.includes(c.name) ? 'flat' : 'text'"
                                    :color="selectedColors.includes(c.name) ? 'primary' : undefined" class="color-btn"
                                    @click="toggleColor(c.name)" :aria-label="c.name">
                                    <span class="dot" :class="{ selected: selectedColors.includes(c.name) }"
                                        :style="{ background: c.hex }" :title="c.name"></span>
                                </v-btn>
                            </div>
                        </div>

                        <v-divider class="my-4" />

                        <div class="filter-section">
                            <div class="d-flex align-center justify-space-between mb-2">
                                <div class="text-body-2 font-weight-bold">Rango de precio</div>
                                <v-chip v-if="!isPriceDefault" size="x-small" variant="tonal" color="primary">
                                    {{ priceLabel }}
                                </v-chip>
                            </div>

                            <v-range-slider v-model="price" :min="0" :max="defaultPrice[1]" :step="5"
                                density="comfortable" color="primary" :thumb-label="true" />
                        </div>

                        <v-divider class="my-4" />

                        <div class="filter-section">
                            <div class="text-body-2 font-weight-bold mb-2">Tipo</div>
                            <div class="d-flex flex-column ga-1">
                                <v-checkbox v-for="b in brands" :key="b" v-model="selectedBrands" :value="b"
                                    hide-details density="compact" :label="b" />
                            </div>
                        </div>
                    </v-card>
                </div>
            </v-col>

            <!-- Resultados -->
            <v-col cols="12" md="9">
                <!-- Chips activos (con transición) -->
                <TransitionGroup v-if="hasAnyFilters" name="chips" tag="div"
                    class="d-flex flex-wrap align-center ga-2 mb-4">
                    <v-chip v-for="s in selectedSizes" :key="'size-' + s" closable @click:close="removeSize(s)"
                        size="small">
                        Talla: {{ s }}
                    </v-chip>

                    <v-chip v-for="c in selectedColors" :key="'color-' + c" closable @click:close="removeColor(c)"
                        size="small">
                        Color: {{ c }}
                    </v-chip>

                    <v-chip v-for="b in selectedBrands" :key="'brand-' + b" closable @click:close="removeBrand(b)"
                        size="small">
                        Tipo: {{ b }}
                    </v-chip>

                    <v-chip v-if="!isPriceDefault" key="price-chip" closable @click:close="resetPrice" size="small">
                        Precio: {{ priceLabel }}
                    </v-chip>
                </TransitionGroup>


                <!-- Grid con transiciones -->
                <TransitionGroup name="grid" tag="div" class="v-row gy-6">
                    <v-col v-for="p in products" :key="p.id" cols="12" sm="6" lg="4">
                        <v-hover v-slot="{ isHovering, props: hoverProps }">
                            <v-card v-bind="hoverProps" rounded="xl" :elevation="isHovering ? 6 : 2"
                                class="product-card">
                                <div class="img-wrap">
                                    <v-img :src="productImage(p) || undefined" height="230" cover
                                        class="rounded-xl product-img" role="button" tabindex="0"
                                        @click="openQuickView(p)" @keydown.enter.prevent="openQuickView(p)">
                                        <!-- Overlay con solo “Añadir” -->
                                        <div class="img-overlay">
                                            <v-btn color="primary" variant="flat" rounded="lg" class="text-none"
                                                @click.stop="addFromCard(p)">
                                                <v-icon icon="mdi-cart-outline" class="mr-2" />
                                                Añadir
                                            </v-btn>
                                        </div>
                                    </v-img>
                                </div>

                                <v-card-text class="pt-4">
                                    <div class="text-medium-emphasis text-body-2">—</div>

                                    <v-tooltip location="top" :text="p.name">
                                        <template #activator="{ props: tip }">
                                            <div class="product-title text-subtitle-1 font-weight-bold" v-bind="tip">
                                                {{ p.name ?? ('Producto #' + p.id) }}
                                            </div>
                                        </template>
                                    </v-tooltip>

                                    <div class="mt-2 d-flex align-center justify-space-between">
                                        <div class="text-primary font-weight-bold">{{ Number(p.price).toFixed(2) }}€
                                        </div>

                                        <v-btn icon variant="text" size="small"
                                            :aria-label="wishlist.isInWishlist(p.id) ? 'Quitar de favoritos' : 'Añadir a favoritos'"
                                            @click.stop="toggleWishlist(p.id)">
                                            <v-icon
                                                :icon="wishlist.isInWishlist(p.id) ? 'mdi-heart' : 'mdi-heart-outline'" />
                                        </v-btn>

                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-hover>
                    </v-col>

                    <v-col v-if="products.length === 0 && !loading" cols="12" key="no-results">
                        <v-alert type="info" variant="tonal">
                            <template v-if="hasQuery">
                                No hay resultados para “{{ query }}”.
                            </template>
                            <template v-else>
                                No hay productos con los filtros seleccionados.
                            </template>

                            <div class="mt-3">
                                <v-btn v-if="hasAnyFilters" size="small" variant="flat" color="primary" rounded="lg"
                                    class="text-none" @click="clearAll">
                                    Limpiar filtros
                                </v-btn>
                            </div>
                        </v-alert>
                    </v-col>
                </TransitionGroup>

                <!-- Paginación -->
                <div class="pagination-wrap" v-if="pageCount > 1">
                    <v-pagination v-model="page" :length="pageCount" :total-visible="5" prev-icon="mdi-chevron-left"
                        next-icon="mdi-chevron-right" rounded="lg" />
                </div>

                <VistaRapidaDialog v-model="quickViewOpen" :product="quickViewProduct" :colors-palette="colors"
                    @add="onAddToCart" />

                <v-snackbar v-model="snackbar.open" :timeout="2500" color="success" rounded="lg">
                    {{ snackbar.text }}
                </v-snackbar>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import VistaRapidaDialog from './VistaRapidaDialog.vue'
import { useCartStore } from '../stores/cart'
import { useWishlistStore } from '../stores/wishlist'
import axios from 'axios'

const route = useRoute();

const props = defineProps({
    query: { type: String, required: true },
})

const cart = useCartStore()

const syncing = ref(false)


const routeCategory = computed(() => (route.query.category ?? '').toString().trim())
const routeType = computed(() => (route.query.type ?? '').toString().trim())

const deptLower = {
    Hombre: 'hombre',
    Mujer: 'mujer',
    Niño: 'niño',
    Niña: 'niña',
}

const contextTitle = computed(() => {
    const cat = routeCategory.value
    if (!cat) return 'Tienda'

    if (deptLower[cat]) return `Tienda de ${deptLower[cat]}`

    if (cat.startsWith('Zapatos ')) {
        const d = cat.replace(/^Zapatos\s+/, '')
        const dL = deptLower[d] ?? d.toLowerCase()
        return `Zapatos de ${dL}`
    }

    return `Tienda · ${cat}`
})

const contextLine = computed(() => {
    const parts = []
    if (routeCategory.value) parts.push(`Sección: ${routeCategory.value}`)
    if (routeType.value) parts.push(`Tipo: ${routeType.value}`)
    return parts.join(' · ')
})

const sortItems = ['Más relevantes', 'Precio: menor a mayor', 'Precio: mayor a menor']
const sort = ref('Más relevantes')

const page = ref(1)
const perPage = 12

const products = ref([])
const loading = ref(false)
const error = ref(null)

const meta = ref({ total: 0, from: 0, to: 0, last_page: 1 })

const selectedSizes = ref([])
const selectedColors = ref([])
const selectedBrands = ref([])

const defaultPrice = ref([0, 200])
const price = ref([0, 200])

const isPriceDefault = computed(
    () => price.value[0] === defaultPrice.value[0] && price.value[1] === defaultPrice.value[1]
)
const priceLabel = computed(() => `${price.value[0]}€–${price.value[1]}€`)

const filterOptions = ref({
    price: { min: 0, max: 200 },
    sizes: [],
    colors: [],
    types: [],
})

const maps = ref({
    sizeById: {},
    colorById: {},
    typeById: {},
    sizeIdsByName: {},
    colorIdsByName: {},
    typeIdsByName: {},
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
function colorHex(name) {
    return colorHexMap[name] ?? '#9E9E9E'
}

const sizes = computed(() => Object.keys(maps.value.sizeIdsByName).sort())
const colors = computed(() =>
    Object.keys(maps.value.colorIdsByName).sort().map(name => ({ name, hex: colorHex(name) }))
)
const brands = computed(() => Object.keys(maps.value.typeIdsByName).sort())

const hasQuery = computed(() => (props.query ?? '').trim().length > 0)

const hasAnyFilters = computed(() =>
    selectedSizes.value.length > 0 ||
    selectedColors.value.length > 0 ||
    selectedBrands.value.length > 0 ||
    !isPriceDefault.value
)

const activeFiltersCount = computed(() =>
    selectedSizes.value.length +
    selectedColors.value.length +
    selectedBrands.value.length +
    (isPriceDefault.value ? 0 : 1)
)

const pageCount = computed(() => meta.value.last_page || 1)

const showingLabel = computed(() => {
    const from = meta.value.from ?? 0
    const to = meta.value.to ?? 0
    if (!meta.value.total) return '0'
    return `${from}–${to}`
})

const placeholderImg =
    'https://via.placeholder.com/800x700?text=Sin+Imagen'

function productImage(p) {
    const imgs = Array.isArray(p?.images) ? p.images : []
    const first = imgs[0]
    return first?.url ?? first?.path ?? first?.src ?? p?.image ?? p?.image_url ?? null
}

onMounted(async () => {
    syncing.value = true
    try {
        await fetchFilters()
        applyRoutePreset()
        await fetchFilters(true)
    } finally {
        syncing.value = false
    }

    await fetchProducts()
})

async function fetchFilters(includeSelectedTypes = false) {
    syncing.value = true
    try {
        const wasDefaultPrice = isPriceDefault.value

        const params = {}
        if (routeCategory.value) params.category = routeCategory.value

        if (includeSelectedTypes && selectedBrands.value.length > 0) {
            params.types = buildIdsParam(selectedBrands.value, maps.value.typeIdsByName)
        }

        const { data } = await axios.get('/api/products/filters', { params })
        filterOptions.value = data

        defaultPrice.value = [0, data.price.max]
        if (wasDefaultPrice) {
            price.value = [...defaultPrice.value]
        } else {
            if (price.value[1] > defaultPrice.value[1]) price.value[1] = defaultPrice.value[1]
            if (price.value[0] < 0) price.value[0] = 0
            if (price.value[0] > price.value[1]) price.value[0] = price.value[1]
        }

        const sizeById = {}
        const colorById = {}
        const typeById = {}

        const sizeIdsByName = {}
        const colorIdsByName = {}
        const typeIdsByName = {}

        for (const s of (data.sizes ?? [])) {
            sizeById[s.id] = s.name
                ; (sizeIdsByName[s.name] ||= []).push(s.id)
        }
        for (const c of (data.colors ?? [])) {
            colorById[c.id] = c.name
                ; (colorIdsByName[c.name] ||= []).push(c.id)
        }
        for (const t of (data.types ?? [])) {
            typeById[t.id] = t.name
                ; (typeIdsByName[t.name] ||= []).push(t.id)
        }

        maps.value = { sizeById, colorById, typeById, sizeIdsByName, colorIdsByName, typeIdsByName }
    } catch (e) {
        console.error(e)
        error.value = "No se pudieron cargar los filtros.";
        filterOptions.value = { price: { min: 0, max: 200 }, sizes: [], colors: [], types: [] }
        maps.value = { sizeById: {}, colorById: {}, typeById: {}, sizeIdsByName: {}, colorIdsByName: {}, typeIdsByName: {} }
        defaultPrice.value = [0, 200];
        price.value = [0, 200];
    } finally {
        syncing.value = false
    }
}

function buildIdsParam(selectedNames, idsByName) {
    const ids = selectedNames.flatMap(n => idsByName[n] ?? [])
    return ids.join(',')
}

function normalizeProduct(p) {
    const img =
        (p.images?.[0]?.url ?? p.images?.[0]?.path ?? null) ||
        `https://picsum.photos/seed/${p.url ?? `tiendamoda-${p.id}`}/800/700`

    const sizeName = maps.value.sizeById[p.size_id] ?? (p.size_id ? `Talla ${p.size_id}` : null)
    const colorName = maps.value.colorById[p.color_id] ?? (p.color_id ? `Color ${p.color_id}` : null)
    const typeName = maps.value.typeById[p.type_id] ?? 'TiendaModa'

    return {
        id: p.id,
        name: p.name ?? '',
        brand: typeName,
        price: Number(p.price ?? 0),
        sizes: sizeName ? [sizeName] : [],
        colors: colorName ? [colorName] : [],
        image: img,
        raw: p,
    }
}

async function fetchProducts() {
    loading.value = true
    error.value = null

    try {
        const sortParam =
            sort.value === 'Precio: menor a mayor' ? 'price_asc' :
                sort.value === 'Precio: mayor a menor' ? 'price_desc' :
                    'relevance'

        const params = {
            page: page.value,
            per_page: perPage,
            q: (props.query ?? '').trim(),
            sort: sortParam,
            price_min: price.value[0],
            price_max: price.value[1],
            sizes: buildIdsParam(selectedSizes.value, maps.value.sizeIdsByName),
            colors: buildIdsParam(selectedColors.value, maps.value.colorIdsByName),
            types: buildIdsParam(selectedBrands.value, maps.value.typeIdsByName),
        }
        if (routeCategory.value) params.category = routeCategory.value

        const { data } = await axios.get('/api/products', { params })

        products.value = (data.data ?? []).map(normalizeProduct)
        meta.value = {
            total: data.total ?? 0,
            from: data.from ?? 0,
            to: data.to ?? 0,
            last_page: data.last_page ?? 1,
        }

        console.log('[SearchPanel] total según API:', meta.value.total, ' | página:', page.value, '/', meta.value.last_page)
    } catch (e) {
        console.error(e)
        error.value = 'No se pudieron cargar los productos.'
        products.value = []
        meta.value = { total: 0, from: 0, to: 0, last_page: 1 }
    } finally {
        loading.value = false
    }
}

function applyRoutePreset() {
    selectedSizes.value = []
    selectedColors.value = []
    selectedBrands.value = []
    resetPrice()

    if (routeType.value) {
        selectedBrands.value = [routeType.value]
    }
}


function toggleSize(name) {
    selectedSizes.value = selectedSizes.value.includes(name)
        ? selectedSizes.value.filter(x => x !== name)
        : [...selectedSizes.value, name]
}
function toggleColor(name) {
    selectedColors.value = selectedColors.value.includes(name)
        ? selectedColors.value.filter(x => x !== name)
        : [...selectedColors.value, name]
}

function removeSize(s) { selectedSizes.value = selectedSizes.value.filter(x => x !== s) }
function removeColor(c) { selectedColors.value = selectedColors.value.filter(x => x !== c) }
function removeBrand(b) { selectedBrands.value = selectedBrands.value.filter(x => x !== b) }

function resetPrice() {
    price.value = [...defaultPrice.value]
}

function clearAll() {
    selectedSizes.value = []
    selectedColors.value = []
    selectedBrands.value = []
    resetPrice()
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const quickViewOpen = ref(false)
const quickViewProduct = ref(null)
const snackbar = ref({ open: false, text: '' })

function openQuickView(p) {
    quickViewProduct.value = p
    quickViewOpen.value = true
}

function onAddToCart(payload) {
    cart.addToCart(payload)
    snackbar.value.text = `Añadido: ${payload.product?.name} (x${payload.qty})`
    snackbar.value.open = true
}

function addFromCard(product) {
    onAddToCart({
        product,
        qty: 1,
        size: product?.size_id ?? null,
        color: product?.color_id ?? null,
    })
}

const wishlist = useWishlistStore()


async function toggleWishlist(productId) {
    try {
        if (wishlist.isInWishlist(productId)) {
            await wishlist.remove(productId)
            snackbar.value.text = 'Eliminado de favoritos'
        } else {
            await wishlist.add(productId)
            snackbar.value.text = 'Añadido a favoritos'
        }
        snackbar.value.open = true
    } catch (e) {
        // Si no hay sesión o falla la petición, no rompemos UI
        snackbar.value.text = 'No se pudo actualizar favoritos'
        snackbar.value.open = true
    }
}

let debounceTimer = null
function scheduleFetch() {
    if (debounceTimer) clearTimeout(debounceTimer)

    debounceTimer = setTimeout(async () => {
        if (page.value !== 1) {
            page.value = 1
            return
        }

        await fetchProducts()
    }, 250)
}

watch(
    [() => props.query, sort, selectedSizes, selectedColors, price],
    () => {
        if (syncing.value) return
        scheduleFetch()
    },
    { deep: true }
)

watch(
    selectedBrands,
    async () => {
        if (syncing.value) return

        syncing.value = true
        try {
            await fetchFilters(true)
        } finally {
            syncing.value = false
        }

        scheduleFetch()
    },
    { deep: true }
)

watch(page, async () => {
    await fetchProducts()
    scrollToTop()
})

watch(pageCount, (pc) => {
    if (page.value > pc) page.value = pc
    if (page.value < 1) page.value = 1
})

watch([routeCategory, routeType], async () => {
    syncing.value = true
    try {
        page.value = 1
        await fetchFilters()
        applyRoutePreset()
        await fetchFilters(true)
        await fetchProducts()
    } finally {
        syncing.value = false
    }
})

onBeforeUnmount(() => {
    if (debounceTimer) clearTimeout(debounceTimer)
})
</script>

<style scoped>
.search-wrap {
    width: 100%;
}

.quote {
    font-weight: 800;
}

.sort {
    min-width: 220px;
}

.filters-sticky {
    position: sticky;
    top: 92px;
}

.filter-card {
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.product-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.06);
    transition: transform 140ms ease;
}

.product-card:hover {
    transform: translateY(-2px);
}

.product-card .v-card-text {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.product-title {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.img-wrap {
    position: relative;
}

.product-img {
    cursor: pointer;
}

.img-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 12px;
    opacity: 0;
    transition: opacity 160ms ease;
    background: linear-gradient(to top, rgba(0, 0, 0, .35), rgba(0, 0, 0, 0) 60%);
}

:deep(.v-img:hover) .img-overlay {
    opacity: 1;
}

.img-overlay.show {
    opacity: 1;
    transform: translateY(0);
}

.btn-overlay {
    border-color: rgba(255, 255, 255, 0.85) !important;
    color: #fff !important;
}

.btn-overlay:hover {
    background: rgba(255, 255, 255, 0.12);
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

.grid-enter-active,
.grid-leave-active {
    transition: opacity 220ms ease, transform 220ms ease;
}

.grid-enter-from,
.grid-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.grid-move {
    transition: transform 220ms ease;
}

.color-btn {
    padding: 0;
}

.dot {
    width: 16px;
    height: 16px;
    border-radius: 999px;
    display: inline-block;
    border: 2px solid rgba(0, 0, 0, .15);
}

.dot.selected {
    border-color: rgba(19, 127, 236, 0.95);
    box-shadow: 0 0 0 2px rgba(19, 127, 236, 0.18);
}

:deep(.v-slider-thumb__label) {
    background: rgb(var(--v-theme-primary)) !important;
    color: white !important;
}

:deep(.v-slider-thumb__label::before) {
    background: rgb(var(--v-theme-primary)) !important;
}

.chips-enter-active,
.chips-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.chips-enter-from,
.chips-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

.chips-move {
    transition: transform 180ms ease;
}
</style>
