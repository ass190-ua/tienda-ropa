<template>
    <v-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" max-width="980"
        :fullscreen="smAndDown">
        <v-card rounded="xl" class="dialog-card">
            <v-card-title class="d-flex align-center justify-space-between">
                <div class="d-flex flex-column">
                    <div class="text-subtitle-1 font-weight-bold">{{ product?.name }}</div>
                    <div class="text-body-2 text-medium-emphasis">{{ product?.brand }}</div>
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
                                <v-carousel v-if="images.length" hide-delimiters :height="mediaHeight"
                                    show-arrows="hover">
                                    <v-carousel-item v-for="(img, idx) in images" :key="idx">
                                        <v-img :src="img" :height="mediaHeight" cover class="rounded-xl" />
                                    </v-carousel-item>
                                </v-carousel>

                                <v-skeleton-loader v-else type="image" :height="mediaHeight" />
                            </v-card>
                        </v-col>

                        <!-- Derecha: compra -->
                        <v-col cols="12" md="6">
                            <div class="purchase-panel">
                                <div class="d-flex align-center justify-space-between mb-2">
                                    <div class="text-h6 font-weight-bold text-primary">
                                        {{ product?.price?.toFixed(2) }}€
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
                                <v-chip-group v-model="localColor" mandatory class="mb-4"
                                    selected-class="chip-selected">
                                    <v-chip v-for="c in (product?.colors ?? [])" :key="c" :value="c" size="small"
                                        variant="outlined" class="text-none">
                                        <span class="d-inline-flex align-center ga-2">
                                            <span class="dot" :style="{ background: colorHex(c) }"></span>
                                            {{ c }}
                                        </span>
                                    </v-chip>
                                </v-chip-group>

                                <!-- Talla -->
                                <div class="text-body-2 font-weight-bold mb-2">Talla</div>
                                <v-chip-group v-model="localSize" mandatory class="mb-4" selected-class="chip-selected">
                                    <v-chip v-for="s in (product?.sizes ?? [])" :key="s" :value="s" size="small"
                                        variant="outlined" class="text-none">
                                        {{ s }}
                                    </v-chip>
                                </v-chip-group>

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

                                    <v-btn variant="outlined" rounded="lg" class="cta-fav"
                                        :color="isFav ? 'primary' : undefined"
                                        :aria-label="isFav ? 'Quitar de favoritos' : 'Añadir a favoritos'"
                                        @click="toggleFav">
                                        <v-icon :icon="isFav ? 'mdi-heart' : 'mdi-heart-outline'" />
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

const images = computed(() => {
    if (!props.product) return []
    const p = props.product
    return [
        p.image,
        `https://picsum.photos/seed/tiendamoda-${p.id}-b/800/700`,
        `https://picsum.photos/seed/tiendamoda-${p.id}-c/800/700`,
    ]
})

const localSize = ref(null)
const localColor = ref(null)
const localQty = ref(1)
const isFav = ref(false)

watch(
    () => props.modelValue,
    (open) => {
        if (!open || !props.product) return
        localSize.value = props.product?.sizes?.[0] ?? null
        localColor.value = props.product?.colors?.[0] ?? null
        localQty.value = 1
        isFav.value = false
    }
)

function colorHex(name) {
    const found = props.colorsPalette.find(x => x.name === name)
    return found?.hex ?? '#999'
}

function toggleFav() {
    isFav.value = !isFav.value
}

function handleAdd() {
    emit('add', {
        product: props.product,
        qty: localQty.value,
        size: localSize.value,
        color: localColor.value,
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
