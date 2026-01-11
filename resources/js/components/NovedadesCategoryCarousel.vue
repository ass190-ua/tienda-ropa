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
                    <router-link :to="`/producto/${p.id}`" class="card-link">
                        <v-card class="product-card" elevation="0">
                            <v-btn class="wishlist-btn" icon variant="flat" size="small"
                                :aria-label="wishlist.isInWishlist(p.id) ? 'Quitar de favoritos' : 'Añadir a favoritos'"
                                @click.prevent.stop="toggleWishlist(p.id)">
                                <v-icon :icon="wishlist.isInWishlist(p.id) ? 'mdi-heart' : 'mdi-heart-outline'" />
                            </v-btn>
                            <v-img height="170" cover :src="productImage(p) || undefined" class="product-img">
                                <template #placeholder>
                                    <div class="placeholder">
                                        <v-icon size="34" class="mb-2">mdi-image-off-outline</v-icon>
                                        <div class="text-medium-emphasis">Sin imagen</div>
                                    </div>
                                </template>
                            </v-img>

                            <v-card-text class="pt-3">
                                <div class="name">
                                    {{ p.name }}
                                </div>
                                <div class="price">
                                    {{ formatPrice(p.price) }}
                                </div>
                            </v-card-text>
                        </v-card>
                    </router-link>
                </SwiperSlide>
            </Swiper>
        </div>
    </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";
import { useWishlistStore } from "@/stores/wishlist";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Autoplay } from "swiper/modules";

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
        const res = await axios.get("/api/products/home", {
            params: { category_id: props.categoryId, limit: props.limit },
        });

        const raw = Array.isArray(res.data)
            ? res.data
            : (Array.isArray(res.data?.data) ? res.data.data : []);

        products.value = raw.map(p => ({
            ...p,
            images: Array.isArray(p.images) ? p.images : [],
        }));
    } catch (e) {
        error.value = true;
        products.value = [];
    } finally {
        loading.value = false;
    }
}


async function toggleWishlist(productId) {
    try {
        if (wishlist.isInWishlist(productId)) await wishlist.remove(productId);
        else await wishlist.add(productId);
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
