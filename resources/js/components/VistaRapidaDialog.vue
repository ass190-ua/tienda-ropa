<template>
    <v-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" max-width="980"
        :fullscreen="smAndDown">
        <v-card rounded="xl" class="dialog-card">
            <v-card-title class="d-flex align-center justify-space-between">
                <div class="d-flex flex-column">
                    <div class="text-subtitle-1 font-weight-bold">{{ product?.name }}</div>
                    <div class="text-body-2 text-medium-emphasis">{{ productBrand }}</div>
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
                        </v-col>

                        <!-- Derecha: compra -->
                        <v-col cols="12" md="6">
                            <div class="purchase-panel">
                                <div class="d-flex align-center justify-space-between mb-2">
                                    <div class="text-h6 font-weight-bold text-primary">
                                        {{ priceText }}
                                    </div>

                                    <v-chip size="small" variant="tonal" color="success">
                                        Envío 24/48h
                                    </v-chip>
                                </div>

                                <div class="text-body-2 text-medium-emphasis">
                                    Diseño premium pensado para el día a día. Material suave, corte moderno y acabados
                                    limpios.
                                </div>

                                <v-divider class="my-4" />

                                <!-- Color -->
                                <div class="text-body-2 font-weight-bold mb-2">Color</div>
                                <v-chip-group v-if="colorOptions.length" v-model="localColorId" :mandatory="true"
                                    class="mb-4" selected-class="chip-selected">
                                    <v-chip v-for="c in colorOptions" :key="c.id" :value="c.id" size="small"
                                        variant="outlined" rounded="lg" class="text-none">
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
                                        variant="outlined" rounded="lg" class="text-none">
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
                                        @click="localQty = Math.max(1, localQty - 1)" aria-label="Disminuir cantidad">
                                        <v-icon icon="mdi-minus" />
                                    </v-btn>

                                    <div class="qty-box">{{ localQty }}</div>

                                    <v-btn icon variant="outlined" rounded="lg" class="qty-btn"
                                        @click="localQty = localQty + 1" aria-label="Aumentar cantidad">
                                        <v-icon icon="mdi-plus" />
                                    </v-btn>
                                </div>

                                <v-alert variant="tonal" type="info" class="mb-4">
                                    Devolución gratuita 30 días · Pago seguro
                                </v-alert>

                                <!-- CTA -->
                                <div class="d-flex ga-2">
                                    <v-btn color="primary" variant="flat" rounded="lg"
                                        class="text-none flex-grow-1 cta-btn" @click="handleAdd">
                                        <v-icon icon="mdi-cart-outline" class="mr-2" />
                                        Añadir al carrito
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

const { smAndDown, mdAndUp } = useDisplay()
const mediaHeight = computed(() => (mdAndUp.value ? 420 : 280))

const props = defineProps({
    modelValue: { type: Boolean, required: true },
    product: { type: Object, default: null },
    colorsPalette: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'add'])

const localSizeId = ref(null)
const localColorId = ref(null)
const localQty = ref(1)
const isFav = ref(false)

const productBrand = computed(() => props.product?.brand ?? 'TiendaModa')

const priceText = computed(() => {
    const n = Number(props.product?.price ?? 0)
    if (!Number.isFinite(n)) return '0,00€'
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n)
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

const colorOptions = computed(() => normalizeOptions(props.product?.colors))
const sizeOptions = computed(() => normalizeOptions(props.product?.sizes))

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

watch(
    () => props.modelValue,
    (open) => {
        if (!open) return
        localQty.value = 1
        isFav.value = false
        localColorId.value = colorOptions.value[0]?.id ?? null
        localSizeId.value = sizeOptions.value[0]?.id ?? null
    }
)

function colorHex(name) {
    const found = props.colorsPalette.find((x) => x.name === name)
    return found?.hex ?? '#999'
}

function handleAdd() {
    const selectedColor = colorOptions.value.find((c) => c.id === localColorId.value) ?? null
    const selectedSize = sizeOptions.value.find((s) => s.id === localSizeId.value) ?? null

    console.log('[Dialog add]', {
        productId: props.product?.id,
        qty: localQty.value,
        size_id: selectedSize?.id,
        size: selectedSize?.value,
        color_id: selectedColor?.id,
        color: selectedColor?.value,
    })

    emit('add', {
        product: props.product,
        qty: localQty.value,
        color: selectedColor?.value ?? null,
        color_id: selectedColor?.id ?? null,
        size: selectedSize?.value ?? null,
        size_id: selectedSize?.id ?? null,
    })

    emit('update:modelValue', false)
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
    width: 56px;
    height: 48px;
    min-width: 56px;
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
