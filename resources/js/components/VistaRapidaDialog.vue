<template>
    <v-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" max-width="980"
        :fullscreen="smAndDown">
        <v-card rounded="xl" class="dialog-card">
            <v-card-title class="d-flex align-center justify-space-between">
                <div class="d-flex flex-column">
                    <div class="text-subtitle-1 font-weight-bold">{{ product?.name }}</div>
                </div>

                <v-btn icon variant="text" @click="emit('update:modelValue', false)" aria-label="Cerrar">
                    <v-icon icon="mdi-close" />
                </v-btn>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-0 dialog-body">
                <v-container fluid class="pa-4">
                    <v-row class="gy-4" align="stretch">
                        <!-- Izquierda: imágenes -->
                        <v-col cols="12" md="6">
                            <v-card rounded="xl" elevation="0" class="media-card">
                                <!-- Si hay varias -->
                                <v-carousel v-if="images.length > 1" hide-delimiters :height="mediaHeight"
                                    show-arrows="hover">
                                    <v-carousel-item v-for="(img, idx) in images" :key="img + '-' + idx">
                                        <v-img :src="img" :height="mediaHeight" cover class="rounded-xl">
                                            <template #placeholder>
                                                <v-skeleton-loader type="image" :height="mediaHeight" />
                                            </template>
                                        </v-img>
                                    </v-carousel-item>
                                </v-carousel>

                                <!-- Si hay solo 1 -->
                                <v-img v-else-if="images.length === 1" :src="images[0]" :height="mediaHeight" cover
                                    class="rounded-xl">
                                    <template #placeholder>
                                        <v-skeleton-loader type="image" :height="mediaHeight" />
                                    </template>
                                </v-img>

                                <!-- Si no hay -->
                                <div v-else class="d-flex align-center justify-center"
                                    :style="{ height: mediaHeight + 'px' }">
                                    <v-icon icon="mdi-tshirt-crew-outline" size="72" class="text-medium-emphasis" />
                                </div>
                            </v-card>

                            <!-- Acciones debajo de las fotos -->
                            <div class="d-flex align-center justify-space-between mt-3">
                            <v-btn
                                size="small"
                                variant="text"
                                prepend-icon="mdi-star-outline"
                                class="text-none"
                                @click="toggleReviews()"
                            >
                                Ver reviews
                            </v-btn>

                            <v-btn
                                size="small"
                                variant="text"
                                prepend-icon="mdi-open-in-new"
                                class="text-none"
                                @click="goToDetails()"
                            >
                                Ver detalles
                            </v-btn>
                            </div>

                            <!-- Panel reviews debajo de los botones -->
                            <v-expand-transition>
                            <div v-show="reviewsOpen" class="mt-2">
                                <v-alert v-if="reviewsError" type="error" variant="tonal" class="mb-2">
                                {{ reviewsError }}
                                </v-alert>

                                <div v-if="reviewsLoading">
                                <v-skeleton-loader type="list-item@3" />
                                </div>

                                <v-alert
                                v-else-if="reviews.length === 0"
                                type="info"
                                variant="tonal"
                                rounded="lg"
                                class="mb-2"
                                >
                                Aún no hay valoraciones.
                                </v-alert>

                                <v-list v-else density="compact" class="px-0">
                                <v-list-item v-for="r in reviews" :key="r.id" class="px-0">
                                    <template #prepend>
                                    <v-avatar size="32" class="mr-2">
                                        <v-img v-if="r.user?.avatar_url" :src="r.user.avatar_url" cover />
                                        <span v-else class="text-caption font-weight-bold">
                                        {{ initials(r.user?.name) }}
                                        </span>
                                    </v-avatar>
                                    </template>

                                    <v-list-item-title class="text-body-2 font-weight-bold">
                                    {{ r.user?.name ?? "Usuario" }}
                                    </v-list-item-title>

                                    <v-list-item-subtitle>
                                    <div class="d-flex align-center ga-2">
                                        <v-rating :model-value="Number(r.rating ?? 0)" density="compact" readonly />
                                        <span class="text-caption" v-if="r.created_at">
                                        {{ new Date(r.created_at).toLocaleDateString("es-ES") }}
                                        </span>
                                    </div>

                                    <div class="text-body-2 mt-1" v-if="r.comment">
                                        {{ r.comment }}
                                    </div>
                                    </v-list-item-subtitle>
                                </v-list-item>
                                </v-list>
                            </div>
                            </v-expand-transition>

                        </v-col>

                        <!-- Derecha: compra -->
                        <v-col cols="12" md="6">
                            <div class="purchase-panel">
                                <div class="d-flex align-center justify-space-between mb-2">
                                    <div class="text-h6 font-weight-bold text-primary">
                                        {{ priceText }}
                                    </div>

                                    <div class="d-flex ga-2">
                                        <v-chip size="small" variant="tonal" color="success">
                                            Envío 24/48h
                                        </v-chip>

                                        <v-chip v-if="stockLabel" size="small" variant="tonal" :color="stockChipColor">
                                            {{ stockLabel }}
                                        </v-chip>
                                    </div>
                                </div>

                                <div class="text-body-2 text-medium-emphasis">
                                    Diseño premium pensado para el día a día. Material suave, corte moderno y acabados
                                    limpios.
                                </div>

                                <v-divider class="my-4" />



                                <!-- Panel reviews -->
                                <v-expand-transition>
                                <div v-show="reviewsOpen" class="mt-2">
                                    <v-alert v-if="reviewsError" type="error" variant="tonal" class="mb-2">
                                    {{ reviewsError }}
                                    </v-alert>

                                    <div v-if="reviewsLoading">
                                    <v-skeleton-loader type="list-item@3" />
                                    </div>

                                    <v-alert
                                    v-else-if="reviews.length === 0"
                                    type="info"
                                    variant="tonal"
                                    rounded="lg"
                                    class="mb-2"
                                    >
                                    Aún no hay valoraciones.
                                    </v-alert>

                                    <v-list v-else density="compact" class="px-0">
                                    <v-list-item v-for="r in reviews" :key="r.id" class="px-0">
                                        <template #prepend>
                                        <v-avatar size="32" class="mr-2">
                                            <v-img v-if="r.user?.avatar_url" :src="r.user.avatar_url" cover />
                                            <span v-else class="text-caption font-weight-bold">
                                            {{ initials(r.user?.name) }}
                                            </span>
                                        </v-avatar>
                                        </template>

                                        <v-list-item-title class="text-body-2 font-weight-bold">
                                        {{ r.user?.name ?? "Usuario" }}
                                        </v-list-item-title>

                                        <v-list-item-subtitle>
                                        <div class="d-flex align-center ga-2">
                                            <v-rating :model-value="Number(r.rating ?? 0)" density="compact" readonly />
                                            <span class="text-caption" v-if="r.created_at">
                                            {{ new Date(r.created_at).toLocaleDateString("es-ES") }}
                                            </span>
                                        </div>

                                        <div class="text-body-2 mt-1" v-if="r.comment">
                                            {{ r.comment }}
                                        </div>
                                        </v-list-item-subtitle>
                                    </v-list-item>
                                    </v-list>
                                </div>
                                </v-expand-transition>

                                <!-- Color -->
                                <div class="text-body-2 font-weight-bold mb-2">Color</div>
                                <v-chip-group v-if="colorOptions.length" v-model="localColorId" :mandatory="true"
                                    class="mb-4" selected-class="chip-selected">
                                    <v-chip v-for="c in colorOptions" :key="c.id" :value="c.id" size="small"
                                        variant="outlined" rounded="lg" class="text-none"
                                        :disabled="c.disabled || syncing || variantsLoading">
                                        <span class="d-inline-flex align-center ga-2">
                                            <span class="dot" :style="{ background: colorHex(c.value) }"></span>
                                            {{ c.value }}
                                        </span>
                                    </v-chip>
                                </v-chip-group>

                                <div v-else class="text-body-2 text-medium-emphasis mb-4">
                                    No hay colores disponibles
                                </div>

                                <!-- Talla -->
                                <div class="text-body-2 font-weight-bold mb-2">Talla</div>
                                <v-chip-group v-if="sizeOptions.length" v-model="localSizeId" :mandatory="true"
                                    class="mb-4" selected-class="chip-selected">
                                    <v-chip v-for="s in sizeOptions" :key="s.id" :value="s.id" size="small"
                                        variant="outlined" rounded="lg" class="text-none"
                                        :disabled="s.disabled || syncing || variantsLoading">
                                        {{ s.value }}
                                    </v-chip>
                                </v-chip-group>

                                <div v-else class="text-body-2 text-medium-emphasis mb-4">
                                    No hay tallas disponibles
                                </div>

                                <!-- Cantidad -->
                                <div class="text-body-2 font-weight-bold mb-2">Cantidad</div>
                                <div class="d-flex align-center ga-2 mb-4">
                                    <v-btn icon variant="outlined" rounded="lg" class="qty-btn"
                                        @click="localQty = Math.max(1, localQty - 1)" aria-label="Disminuir cantidad"
                                        :disabled="!canDec">
                                        <v-icon icon="mdi-minus" />
                                    </v-btn>

                                    <div class="qty-box">{{ localQty }}</div>

                                    <v-btn icon variant="outlined" rounded="lg" class="qty-btn"
                                        @click="localQty = Math.min(Math.max(1, maxQty), localQty + 1)"
                                        aria-label="Aumentar cantidad" :disabled="!canInc">
                                        <v-icon icon="mdi-plus" />
                                    </v-btn>
                                </div>

                                <v-alert variant="tonal" type="info" class="mb-4">
                                    Devolución gratuita 30 días · Pago seguro
                                </v-alert>

                                <!-- CTA -->
                                <div class="d-flex ga-2">
                                    <v-btn color="primary" variant="flat" rounded="lg"
                                        class="text-none flex-grow-1 cta-btn" @click="handleAdd"
                                        :disabled="!canAdd || !selectedVariant || maxQty <= 0">
                                        <v-icon icon="mdi-cart-outline" class="mr-2" />
                                        Añadir al carrito
                                    </v-btn>

                                    <v-btn class="cta-fav" icon variant="text"
                                        :aria-label="isWishlisted ? 'Quitar de favoritos' : 'Añadir a favoritos'"
                                        :disabled="syncing || variantsLoading" @click.stop="toggleWishlist">
                                        <v-icon :icon="isWishlisted ? 'mdi-heart' : 'mdi-heart-outline'" color="red" />
                                    </v-btn>
                                </div>
                            </div>
                        </v-col>
                    </v-row>
                </v-container>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
import axios from 'axios'
import { useWishlistStore } from '@/stores/wishlist'
import { useRouter } from "vue-router"


const { smAndDown, mdAndUp } = useDisplay()
const mediaHeight = computed(() => (mdAndUp.value ? 420 : 280))

const props = defineProps({
    modelValue: { type: Boolean, required: true },
    product: { type: Object, default: null },
    colorsPalette: { type: Array, default: () => [] },
    syncing: { type: Boolean, default: false },
})

const syncing = computed(() => props.syncing)

const emit = defineEmits(['update:modelValue', 'add'])
const wishlist = useWishlistStore()

const localSizeId = ref(null)
const localColorId = ref(null)
const localQty = ref(1)

const variants = ref([]) // [{ id, size_id, size, color_id, color, available, product }]
const variantsLoading = ref(false)
const variantsError = ref('')

const reviewsOpen = ref(false)
const reviewsLoading = ref(false)
const reviewsError = ref('')
const reviews = ref([])

const router = useRouter()

const selectedVariant = computed(() => {
    const c = localColorId.value
    const s = localSizeId.value
    return variants.value.find(v => String(v.color_id) === String(c) && String(v.size_id) === String(s)) ?? null
})

const maxQty = computed(() => {
    const a = Number(selectedVariant.value?.available ?? 0)
    return Number.isFinite(a) ? a : 0
})

const priceText = computed(() => {
    const n = Number(props.product?.price ?? 0)
    if (!Number.isFinite(n)) return '0,00€'
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n)
})

const canInc = computed(() => {
    if (syncing.value || variantsLoading.value) return false
    if (!selectedVariant.value) return false
    return localQty.value < maxQty.value
})

const canDec = computed(() => {
    if (syncing.value || variantsLoading.value) return false
    return localQty.value > 1
})

const canAdd = computed(() => {
    if (syncing.value || variantsLoading.value) return false
    if (!selectedVariant.value) return false
    return maxQty.value > 0 && localQty.value <= maxQty.value
})

const stockLabel = computed(() => {
    if (variantsLoading.value) return 'Comprobando stock…'
    if (!selectedVariant.value) return ''
    if (maxQty.value <= 0) return 'Sin stock'
    return `Disponibles: ${maxQty.value}`
})

const stockChipColor = computed(() => {
    if (variantsLoading.value) return 'info'
    if (!selectedVariant.value) return 'info'
    if (maxQty.value <= 0) return 'error'
    return 'success'
})

const images = computed(() => {
    const p = props.product
    if (!p) return []

    const list = []

    // 1) Imágenes reales del backend (grouped -> images[])
    if (Array.isArray(p.images)) {
        for (const im of p.images) {
            const u = pickImageUrl(im)
            if (u) list.push(u)
        }
    }

    // 2) Si existe p.image (a veces no), lo añadimos SIN romper
    const one = pickImageUrl(p.image)
    if (one) list.unshift(one)

    // Quitar vacíos y duplicados
    const uniq = []
    const seen = new Set()
    for (const u of list) {
        if (!u) continue
        if (seen.has(u)) continue
        seen.add(u)
        uniq.push(u)
    }

    // 3) Fallback si no hay ninguna
    if (uniq.length === 0) {
        const id = p?.id ?? 'x'
        return [
            `https://picsum.photos/seed/tiendamoda-${id}-a/800/700`,
            `https://picsum.photos/seed/tiendamoda-${id}-b/800/700`,
            `https://picsum.photos/seed/tiendamoda-${id}-c/800/700`,
        ]
    }

    return uniq
})

const colorOptions = computed(() => {
    // si no hemos cargado variantes, fallback a lo que había
    if (!variants.value.length) {
        return normalizeOptions(props.product?.colors).map(o => ({ ...o, disabled: false }))
    }

    const currentSize = localSizeId.value
    const byId = new Map()
    for (const v of variants.value) {
        if (v.color_id == null) continue
        if (!byId.has(String(v.color_id))) byId.set(String(v.color_id), { id: v.color_id, value: v.color })
    }

    return Array.from(byId.values()).map(o => {
        const enabled = variants.value.some(v =>
            String(v.color_id) === String(o.id) &&
            (!currentSize || String(v.size_id) === String(currentSize)) &&
            Number(v.available ?? 0) > 0
        )
        return { ...o, disabled: !enabled }
    })
})

const sizeOptions = computed(() => {
    if (!variants.value.length) {
        return normalizeOptions(props.product?.sizes).map(o => ({ ...o, disabled: false }))
    }

    const currentColor = localColorId.value
    const byId = new Map()
    for (const v of variants.value) {
        if (v.size_id == null) continue
        if (!byId.has(String(v.size_id))) byId.set(String(v.size_id), { id: v.size_id, value: v.size })
    }

    return Array.from(byId.values()).map(o => {
        const enabled = variants.value.some(v =>
            String(v.size_id) === String(o.id) &&
            (!currentColor || String(v.color_id) === String(currentColor)) &&
            Number(v.available ?? 0) > 0
        )
        return { ...o, disabled: !enabled }
    })
})

function pickImageUrl(x) {
    if (!x) return null
    if (typeof x === 'string') return x
    return x.url ?? x.path ?? x.src ?? x.image_url ?? x.image ?? null
}

function normalizeOptions(arr) {
    if (!Array.isArray(arr)) return []
    return arr
        .map((o) => {
            if (!o) return null
            if (typeof o === 'string') return { id: o, value: o }
            if (typeof o === 'object') {
                const id = o.id ?? o.value ?? o.name
                const value = o.value ?? o.name ?? String(o.id ?? '')
                return { id, value }
            }
            return { id: String(o), value: String(o) }
        })
        .filter(Boolean)
}

function initials(name) {
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return 'U'
  const a = parts[0]?.[0] ?? ''
  const b = parts[1]?.[0] ?? ''
  return (a + b).toUpperCase()
}

const representativeId = computed(() => {
  return props.product?.representative_id ?? props.product?.id ?? null
})

async function fetchReviews() {
  if (!representativeId.value) return

  reviewsLoading.value = true
  reviewsError.value = ''

  try {
    const { data } = await axios.get(`/api/products/${representativeId.value}/reviews`)
    reviews.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('QUICKVIEW REVIEWS ERROR:', e)
    reviews.value = []
    reviewsError.value = 'No se pudieron cargar las valoraciones.'
  } finally {
    reviewsLoading.value = false
  }
}

async function toggleReviews() {
  reviewsOpen.value = !reviewsOpen.value

  // si se abre y aún no hay cargadas => fetch
  if (reviewsOpen.value && reviews.value.length === 0) {
    await fetchReviews()
  }
}


function groupIds() {
    const ids = Array.isArray(props.product?.product_ids) ? props.product.product_ids : []
    if (ids.length) return ids
    const id = props.product?.id
    return id != null ? [id] : []
}

const isWishlisted = computed(() => {
    const ids = groupIds()
    if (!ids.length) return false
    return wishlist.isGroupInWishlist(ids)
})

async function toggleWishlist() {
    try {
        const ids = groupIds()
        if (!ids.length) return
        await wishlist.toggleGroup(ids, props.product?.representative_id ?? props.product?.id ?? null)
    } catch (e) {
        // No rompemos el diálogo si falla backend / no hay sesión
    }
}


async function loadVariantsForGroup() {
    variants.value = []
    variantsError.value = ''

    const ids = Array.isArray(props.product?.product_ids) ? props.product.product_ids : []
    if (!ids.length) return

    variantsLoading.value = true
    try {
        const resp = await axios.post('/api/products/variants-by-ids', { product_ids: ids })
        const products = Array.isArray(resp.data) ? resp.data : []

        variants.value = products.map((p) => ({
            id: p.id,
            size_id: p.size_id ?? null,
            size: p.size ?? null,
            color_id: p.color_id ?? null,
            color: p.color ?? null,
            available: Number(p.available ?? p.stock_total ?? 0),
            product: p,
        }))
    } catch (e) {
        variantsError.value =
            e?.response?.data?.message ||
            e?.response?.data?.error ||
            e?.message ||
            'No se pudieron cargar variantes.'
        variants.value = []
    } finally {
        variantsLoading.value = false
    }
}

function pickFirstValidSelection() {
    if (!variants.value.length) return

    const v = variants.value.find(x => Number(x.available ?? 0) > 0) ?? variants.value[0]
    localColorId.value = v?.color_id ?? null
    localSizeId.value = v?.size_id ?? null
    clampQty()
}

function clampQty() {
    const m = maxQty.value
    if (m <= 0) {
        localQty.value = 1
        return
    }
    if (localQty.value < 1) localQty.value = 1
    if (localQty.value > m) localQty.value = m
}

watch(
    () => props.modelValue,
    async (open) => {
        if (!open) {
            if (refreshTimer) clearTimeout(refreshTimer)
            return
        }

        localQty.value = 1
        localColorId.value = null
        localSizeId.value = null

        await loadVariantsForGroup()
        pickFirstValidSelection()
    }
)

let refreshTimer = null
const fixingSelection = ref(false)
const initializing = ref(false)

watch([localColorId, localSizeId], () => {
    if (!props.modelValue) return
    if (!variants.value.length) return

    if (refreshTimer) clearTimeout(refreshTimer)
    refreshTimer = setTimeout(() => {
        clampQty()
    }, 80)
})

function colorHex(name) {
    const found = props.colorsPalette.find((x) => x.name === name)
    return found?.hex ?? '#999'
}

function handleAdd() {
    if (!selectedVariant.value) return
    if (maxQty.value <= 0) return

    clampQty() // usa maxQty (ya lo tienes)

    const v = selectedVariant.value
    const pObj = v.product ?? props.product

    emit('add', {
        product: { ...pObj, id: v.id },   // <- ID REAL DE LA VARIANTE
        qty: localQty.value,
        color: v.color ?? null,
        color_id: v.color_id ?? null,
        size: v.size ?? null,
        size_id: v.size_id ?? null,
    })

    emit('update:modelValue', false)
}

function goToDetails() {
  const id = props.product?.representative_id ?? props.product?.id
  if (!id) return

  emit('update:modelValue', false) // cerrar quickview
  router.push(`/producto/${id}`)   // ajusta si tu ruta real es distinta
}

</script>

<style scoped>
.dialog-card {
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.dialog-body {
    overflow: auto;
}

.media-card {
    border: 1px solid rgba(0, 0, 0, 0.06);
    overflow: hidden;
    background: rgba(0, 0, 0, .02);
}

/* Panel derecho más “premium” */
.purchase-panel {
    border: 1px solid rgba(0, 0, 0, .06);
    border-radius: 16px;
    padding: 16px;
    background: rgba(0, 0, 0, .015);
}

/* Cantidad más elegante (menos “círculo gigante”) */
.qty-btn {
    width: 42px;
    height: 42px;
    min-width: 42px;
}

.qty-box {
    min-width: 60px;
    height: 42px;
    border: 1px solid rgba(0, 0, 0, .18);
    border-radius: 10px;
    display: grid;
    place-items: center;
    font-weight: 700;
}

/* CTA: mismo alto para ambos botones */
.cta-btn {
    height: 48px;
}

.cta-fav {
    width: 48px;
    height: 48px;
    min-width: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 120ms ease;
}

.cta-fav:hover {
    transform: translateY(-1px);
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 999px;
    display: inline-block;
    border: 2px solid rgba(0, 0, 0, .12);
}

:deep(.chip-selected) {
    border-color: rgba(19, 127, 236, 0.95) !important;
    box-shadow: 0 0 0 2px rgba(19, 127, 236, 0.12) inset;
}
</style>
