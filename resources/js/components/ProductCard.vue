<template>
    <v-hover v-slot="{ isHovering, props: hoverProps }">
        <v-card v-bind="hoverProps" rounded="xl" :elevation="isHovering ? 6 : 2" class="product-card"
            @click="goToDetail" role="button" tabindex="0" @keydown.enter.prevent="goToDetail">
            <!-- Corazón arriba derecha -->
            <v-btn class="wishlist-btn" icon variant="flat" size="small"
                :aria-label="inWishlist ? 'Quitar de favoritos' : 'Añadir a favoritos'" @click.stop="toggleWishlist">
                <v-icon :icon="inWishlist ? 'mdi-heart' : 'mdi-heart-outline'" />
            </v-btn>

            <v-img :src="productImage(product) || placeholderImg" height="230" cover class="rounded-xl" />

            <v-card-text class="pt-4">
                <div class="text-medium-emphasis text-body-2">
                    {{ product.brandLabel ?? product.brand ?? '' }}
                </div>

                <v-tooltip location="top" :text="product.name ?? product.nombre ?? ''">
                    <template #activator="{ props: tip }">
                        <div class="product-title text-subtitle-1 font-weight-bold" v-bind="tip">
                            {{ product.name ?? product.nombre ?? 'Producto' }}
                        </div>
                    </template>
                </v-tooltip>

                <div class="mt-2 d-flex align-center justify-space-between">
                    <div class="text-primary font-weight-bold">
                        {{ Number(product.price ?? product.precio ?? 0).toFixed(2) }}€
                    </div>

                    <!-- Botón opcional (solo si quieres mostrarlo) -->
                    <v-btn v-if="showAddToCart" size="small" variant="text" class="text-none"
                        @click.stop="$emit('add-to-cart', product)">
                        <v-icon icon="mdi-cart-outline" class="mr-2" />
                        Añadir
                    </v-btn>
                </div>
            </v-card-text>
        </v-card>
    </v-hover>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useWishlistStore } from '@/stores/wishlist'

const props = defineProps({
    product: { type: Object, required: true },
    showAddToCart: { type: Boolean, default: false },
})

const placeholderImg = 'https://via.placeholder.com/800x700?text=Sin+Imagen'

function productImage(p) {
    const imgs = Array.isArray(p?.images) ? p.images : []
    const first = imgs[0]
    return first?.url ?? first?.path ?? first?.src ?? p?.image ?? p?.image_url ?? null
}

defineEmits(['add-to-cart'])

const router = useRouter()
const wishlist = useWishlistStore()

const inWishlist = computed(() => wishlist.isInWishlist(props.product.id))

function goToDetail() {
    router.push(`/producto/${props.product.id}`)
}

async function toggleWishlist() {
    if (inWishlist.value) await wishlist.remove(props.product.id)
    else await wishlist.add(props.product.id)
}
</script>

<style scoped>
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

.product-title {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Botón corazón flotante */
.wishlist-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 3;
}
</style>
