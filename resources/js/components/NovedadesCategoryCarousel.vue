<template>
    <section class="mb-10">
        <div class="d-flex align-center justify-space-between mb-3">
            <h2 class="text-h6 font-weight-bold">{{ title }}</h2>
        </div>

        <!-- Loading -->
        <v-skeleton-loader v-if="loading" type="image, image, image" />

        <!-- Error -->
        <v-alert v-else-if="error" type="error" variant="tonal" density="comfortable">
            No se pudieron cargar las novedades de {{ title }}.
            <template #append>
                <v-btn variant="text" density="comfortable" @click="fetchProducts">
                    Reintentar
                </v-btn>
            </template>
        </v-alert>

        <!-- Empty -->
        <v-alert v-else-if="products.length === 0" type="info" variant="tonal" density="comfortable">
            No hay novedades en {{ title }}.
        </v-alert>

        <!-- OK: carrusel real -->
        <div v-else class="carousel-wrap">
            <div class="carousel-shell">
                <v-btn class="nav-btn nav-prev" icon="mdi-chevron-left" variant="flat" @click="swiperRef?.slidePrev()"
                    :disabled="!canPrev" />
                <Swiper @swiper="onSwiper" @slideChange="syncNav" :modules="modules" :slides-per-view="1"
                    :space-between="18" :breakpoints="breakpoints" :navigation="false" :watch-overflow="true"
                    :loop="shouldLoop" :speed="550" :autoplay="{
                        delay: autoplayDelay,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true
                    }">
                    <SwiperSlide v-for="p in products" :key="p.id">
                        <ProductCard :product="p" @quick-view="openQuickView" />
                    </SwiperSlide>
                </Swiper>
                <v-btn class="nav-btn nav-next" icon="mdi-chevron-right" variant="flat" @click="swiperRef?.slideNext()"
                    :disabled="!canNext" />
            </div>
        </div>
        <VistaRapidaDialog v-model="quickViewOpen" :product="quickViewProduct" :colors-palette="colors"
            :syncing="cart.syncing" @add="onAddToCart" />

        <v-snackbar v-model="snackbar.open" :timeout="2500" :color="snackbar.color" rounded="lg">
            {{ snackbar.text }}
        </v-snackbar>

    </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";
import { useWishlistStore } from "@/stores/wishlist";
import { useCartStore } from "../stores/cart";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Autoplay } from "swiper/modules";
import VistaRapidaDialog from "@/components/VistaRapidaDialog.vue";
import ProductCard from "@/components/ProductCard.vue";


import "swiper/css";
import "swiper/css/navigation";

const props = defineProps({
    title: { type: String, required: true },
    categoryId: { type: Number, required: true },
    limit: { type: Number, default: 12 },
    autoplayDelay: { type: Number, default: 2600 }, // velocidad “tienda”
});

const modules = [Navigation, Autoplay];
const wishlist = useWishlistStore();
const cart = useCartStore();

const products = ref([]);
const loading = ref(false);
const error = ref(false);

const quickViewOpen = ref(false);
const quickViewProduct = ref(null);

const snackbar = ref({ open: false, text: '', color: 'success' })

const swiperRef = ref(null)
const canPrev = ref(false)
const canNext = ref(true)

function openQuickView(p) {
    quickViewProduct.value = p;
    quickViewOpen.value = true;
}

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

const breakpoints = {
    600: { slidesPerView: 2, spaceBetween: 18 },
    960: { slidesPerView: 3, spaceBetween: 18 },
    1280: { slidesPerView: 4, spaceBetween: 18 },
    1600: { slidesPerView: 5, spaceBetween: 18 },
};

const shouldLoop = computed(() => products.value.length >= 6);

function onSwiper(swiper) {
    swiperRef.value = swiper
    syncNav(swiper)
}

function syncNav(swiper) {
    // Si loop está activo, siempre "puede"
    if (shouldLoop.value) {
        canPrev.value = true
        canNext.value = true
        return
    }
    canPrev.value = !swiper.isBeginning
    canNext.value = !swiper.isEnd
}

async function onAddToCart(payload) {
    try {
        await cart.addToCart(payload)

        if (cart.stockWarning?.message) {
            snackbar.value.text = cart.stockWarning.message
            snackbar.value.color = 'warning'
            snackbar.value.open = true
            cart.clearStockWarning?.()
            return
        }

        snackbar.value.text = `Añadido: ${payload.product?.name} (x${payload.qty})`
        snackbar.value.color = 'success'
        snackbar.value.open = true

        quickViewOpen.value = false
    } catch (e) {
        snackbar.value.text =
            e?.response?.data?.message ||
            e?.response?.data?.error ||
            e?.message ||
            'No se pudo añadir al carrito.'
        snackbar.value.color = 'error'
        snackbar.value.open = true
    }
}

async function fetchProducts() {
    loading.value = true;
    error.value = false;

    try {
        const res = await axios.get("/api/products/novedades-grouped", {
            params: { category_id: props.categoryId, limit: props.limit },
        });

        const raw = Array.isArray(res.data)
            ? res.data
            : (Array.isArray(res.data?.data) ? res.data.data : []);

        products.value = raw.map(p => ({
            ...p,
            images: Array.isArray(p.images) ? p.images : [],
            product_ids: Array.isArray(p.product_ids) ? p.product_ids : [],
            colors: Array.isArray(p.colors) ? p.colors : [],
            sizes: Array.isArray(p.sizes) ? p.sizes : [],
            representative_id: p.representative_id ?? p.id,
        }));
    } catch (e) {
        error.value = true;
        products.value = [];
    } finally {
        loading.value = false;
    }
}


function groupIds(p) {
    const ids = Array.isArray(p?.product_ids) ? p.product_ids : [];
    return ids.length ? ids : [p.id];
}

function isWishlisted(p) {
    return wishlist.isGroupInWishlist(groupIds(p));
}

async function toggleWishlistGroup(p) {
    try {
        // Buscamos si YA hay alguna variante de esta prenda en wishlist
        const existingId = wishlist.findWishlistedProductId(groupIds(p));

        if (existingId) {
            await wishlist.remove(existingId);
        } else {
            await wishlist.add(p.representative_id ?? p.id);
        }
        await wishlist.toggleGroup(groupIds(p), p.representative_id ?? p.id);
    } catch (e) {
        // No rompemos el carrusel si no hay sesión o falla el backend
    }
}


onMounted(fetchProducts);

watch(() => [props.categoryId, props.limit], fetchProducts);
</script>

<style scoped>
.carousel-wrap {
    position: relative;
}

.carousel-wrap :deep(.swiper-slide) {
    height: auto;
    display: flex;
}

.carousel-wrap :deep(.swiper-slide > *) {
    width: 100%;
}

.carousel-shell :deep(.swiper) {
    padding: 6px 0 22px;
    /* ya no necesitamos padding lateral por flechas */
    overflow: visible;
}

.nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;

    width: 42px;
    height: 42px;
    border-radius: 999px;

    background: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(0, 0, 0, 0.10);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);

    transition: transform 140ms ease, box-shadow 140ms ease, opacity 140ms ease;
}

.nav-btn:hover {
    transform: translateY(-50%) scale(1.04);
    box-shadow: 0 16px 34px rgba(0, 0, 0, 0.16);
}

.nav-prev {
    left: 10px;
}

.nav-next {
    right: 10px;
}

.nav-btn:disabled {
    opacity: 0.35;
}

.card-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.product-card {
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 16px;
    overflow: hidden;
    transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
    background: white;
}

.product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.10);
    border-color: rgba(0, 0, 0, 0.14);
}

.product-img {
    background: rgba(0, 0, 0, 0.03);
}

.placeholder {
    height: 170px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.name {
    font-weight: 600;
    font-size: 0.95rem;
    line-height: 1.2rem;
    min-height: 2.4rem;
    /* 2 líneas */
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.price {
    margin-top: 6px;
    font-weight: 700;
}

.product-card {
    position: relative;
}

.wishlist-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;
}
</style>
