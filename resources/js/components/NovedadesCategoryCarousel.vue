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
            <Swiper :modules="modules" :slides-per-view="1.2" :space-between="16" :breakpoints="breakpoints"
                :navigation="true" :loop="shouldLoop" :autoplay="{
                    delay: autoplayDelay,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                }">
                <SwiperSlide v-for="p in products" :key="p.id">
                    <ProductCard :product="p" @quick-view="openQuickView" />
                </SwiperSlide>
            </Swiper>
        </div>
        <VistaRapidaDialog v-model="quickViewOpen" :product="quickViewProduct" :colors-palette="colors" />
    </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";
import { useWishlistStore } from "@/stores/wishlist";
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

const products = ref([]);
const loading = ref(false);
const error = ref(false);

const quickViewOpen = ref(false);
const quickViewProduct = ref(null);

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
    600: { slidesPerView: 2.2, spaceBetween: 16 },
    960: { slidesPerView: 3.2, spaceBetween: 18 },
    1280: { slidesPerView: 4.2, spaceBetween: 18 },
    1600: { slidesPerView: 5.2, spaceBetween: 18 },
};

// Para que loop no haga cosas raras si hay pocos productos:
const shouldLoop = computed(() => products.value.length >= 6);

function productImage(p) {
    const img = Array.isArray(p?.images) ? p.images[0] : null;
    return img?.url ?? img?.path ?? img?.src ?? img?.image_url ?? null;
}

function formatPrice(price) {
    if (price === null || price === undefined) return "";
    return `${Number(price).toFixed(2)} €`;
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
    -webkit-line-clamp: 2;
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
