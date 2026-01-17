<template>
    <v-hover v-slot="{ isHovering, props: hoverProps }">
        <v-card v-bind="hoverProps" rounded="xl" :elevation="isHovering ? 6 : 2" class="product-card"
            @click="goToDetail" role="button" tabindex="0" @keydown.enter.prevent="goToDetail">
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
                    <div class="d-flex align-center ga-2">
                        <!-- Botón opcional (solo si quieres mostrarlo) -->
                        <v-btn v-if="showAddToCart" size="small" variant="text" class="text-none"
                            @click.stop="$emit('add-to-cart', product)">
                            <v-icon icon="mdi-cart-outline" class="mr-2" />
                            Añadir
                        </v-btn>

                        <!-- Corazón abajo derecha (estilo /shop) -->
                        <v-btn v-if="auth.isAuthenticated" icon variant="text" density="comfortable"
                            class="wishlist-inline"
                            :aria-label="inWishlist ? 'Quitar de favoritos' : 'Añadir a favoritos'"
                            @click.stop="onWishlistClick">
                            <v-icon :icon="inWishlist ? 'mdi-heart' : 'mdi-heart-outline'" color="red" />
                        </v-btn>
                    </div>

                </div>
            </v-card-text>
        </v-card>
    </v-hover>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useWishlistStore } from '../stores/wishlist'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
    product: { type: Object, required: true },
    showAddToCart: { type: Boolean, default: false },
    navigateToDetail: { type: Boolean, default: false },
    wishlistControlled: { type: Boolean, default: false },
})

const placeholderImg = 'https://via.placeholder.com/800x700?text=Sin+Imagen'

function productImage(p) {
    const imgs = Array.isArray(p?.images) ? p.images : []
    const first = imgs[0]
    return first?.url ?? first?.path ?? first?.src ?? p?.image ?? p?.image_url ?? null
}

const emit = defineEmits(['add-to-cart', 'quick-view', 'wishlist-click'])

const router = useRouter()
const wishlist = useWishlistStore()
const auth = useAuthStore()

function groupIds(p) {
    const ids = Array.isArray(p?.product_ids) ? p.product_ids : []
    if (ids.length) return ids
    const id = p?.id
    return id != null ? [id] : []
}

const inWishlist = computed(() => {
    const ids = groupIds(props.product)
    return ids.length > 1 || Array.isArray(props.product?.product_ids)
        ? wishlist.isGroupInWishlist(ids)
        : wishlist.isInWishlist(props.product.id)
})

function goToDetail() {
    // Por defecto: abrir vista rápida (la card de Shop)
    if (!props.navigateToDetail) {
        emit('quick-view', props.product)
        return
    }
    router.push(`/producto/${props.product.id}`)
}


async function onWishlistClick() {
    // Si el padre controla wishlist, solo emitimos y salimos
    if (props.wishlistControlled) {
        emit('wishlist-click', {
            product: props.product,
            inWishlist: inWishlist.value,
            product_ids: groupIds(props.product),
            representative_id: props.product?.representative_id ?? props.product?.id ?? null,
        })
        return
    }

    // comportamiento por defecto
    await toggleWishlist()
}



async function toggleWishlist() {
    await wishlist.fetchWishlist()

    const ids = groupIds(props.product)
    const isGrouped = Array.isArray(props.product?.product_ids) && ids.length

    if (isGrouped) {
        await wishlist.toggleGroup(ids, props.product?.representative_id ?? props.product?.id ?? null)
        return
    }

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

.wishlist-inline {
    min-width: 40px;
    width: 40px;
    height: 40px;
}
</style>
