<template>
    <v-container class="py-10">
        <!-- Breadcrumbs -->
        <v-breadcrumbs :items="breadcrumbs" class="px-0 mb-4">
            <template #divider>
                <v-icon size="16" class="mx-2">mdi-chevron-right</v-icon>
            </template>
        </v-breadcrumbs>

        <!-- Loading -->
        <div v-if="loading">
            <v-row dense>
                <v-col cols="12" md="6">
                    <v-skeleton-loader type="image" height="520" rounded="xl" />
                    <v-skeleton-loader class="mt-4" type="chip@6" />
                </v-col>
                <v-col cols="12" md="6">
                    <v-skeleton-loader type="heading, paragraph, paragraph, button" />
                    <v-skeleton-loader class="mt-4" type="text@6" />
                </v-col>
            </v-row>
        </div>

        <!-- Not found -->
        <v-alert v-else-if="notFound" type="info" variant="tonal" rounded="xl" class="mb-6">
            <div class="d-flex flex-column ga-3">
                <div>
                    <div class="text-h6 font-weight-bold">Producto no encontrado</div>
                    <div class="text-body-2 text-medium-emphasis">
                        No existe un producto con ID: <b>{{ productId }}</b>
                    </div>
                </div>

                <div class="d-flex flex-wrap ga-3">
                    <v-btn color="primary" @click="router.push('/shop')">Volver a tienda</v-btn>
                    <v-btn variant="outlined" @click="router.back()">Atrás</v-btn>
                </div>
            </div>
        </v-alert>

        <!-- Error -->
        <v-alert v-else-if="error" type="error" variant="tonal" rounded="xl" class="mb-6">
            <div class="d-flex flex-column ga-3">
                <div>
                    <div class="text-h6 font-weight-bold">Error al cargar el producto</div>
                    <div class="text-body-2 text-medium-emphasis">
                        Inténtalo de nuevo en unos segundos.
                    </div>
                </div>

                <div class="d-flex flex-wrap ga-3">
                    <v-btn color="primary" @click="fetchProduct()">Reintentar</v-btn>
                    <v-btn variant="outlined" @click="router.back()">Atrás</v-btn>
                </div>
            </div>
        </v-alert>

        <!-- Content -->
        <div v-else-if="product">
            <v-row dense>
                <!-- Gallery -->
                <v-col cols="12" md="6">
                    <v-card rounded="xl" variant="outlined" class="overflow-hidden">
                        <v-img :src="images[activeImage] ?? placeholderImg" height="520" cover
                            class="bg-grey-lighten-4">
                            <template #placeholder>
                                <div class="d-flex flex-column align-center justify-center h-100 text-medium-emphasis">
                                    <v-icon size="56">mdi-image-off-outline</v-icon>
                                    <div class="text-caption mt-2">Sin imagen</div>
                                </div>
                            </template>
                        </v-img>
                    </v-card>

                    <!-- Thumbs -->
                    <v-slide-group v-if="images.length > 1" class="mt-4" show-arrows>
                        <v-slide-group-item v-for="(src, idx) in images" :key="src + idx">
                            <v-card class="mr-3 thumb" :class="{ 'thumb--active': idx === activeImage }" rounded="lg"
                                variant="outlined" width="90" @click="activeImage = idx">
                                <v-img :src="src" height="110" cover class="bg-grey-lighten-4" />
                            </v-card>
                        </v-slide-group-item>
                    </v-slide-group>
                </v-col>

                <!-- Info -->
                <v-col cols="12" md="6">
                    <div class="d-flex flex-wrap ga-2 mb-3">
                        <v-chip v-if="product.categoryLabel" color="primary" variant="tonal" label>
                            {{ product.categoryLabel }}
                        </v-chip>
                        <v-chip v-if="product.typeLabel" variant="tonal" label>
                            {{ product.typeLabel }}
                        </v-chip>
                        <v-chip v-if="product.brandLabel" variant="outlined" label>
                            {{ product.brandLabel }}
                        </v-chip>
                    </div>

                    <h1 class="text-h4 font-weight-black mb-2">
                        {{ product.name }}
                    </h1>

                    <div class="d-flex align-center ga-3 mb-3">
                        <div class="text-h5 font-weight-bold">
                            {{ formatPrice(product.price) }}
                        </div>

                        <div class="d-flex align-center ga-1">
                            <v-rating :model-value="product.rating" density="compact" half-increments readonly />
                            <span class="text-body-2 text-medium-emphasis">
                                ({{ product.reviews.length }})
                            </span>
                        </div>
                    </div>

                    <p v-if="product.shortDescription" class="text-body-1 text-medium-emphasis mb-6">
                        {{ product.shortDescription }}
                    </p>

                    <!-- Color -->
                    <div class="mb-4">
                        <div class="text-subtitle-2 font-weight-bold mb-2">
                            Color
                        </div>

                        <v-chip-group v-model="selectedColorKey" selected-class="chip--selected" mandatory>
                            <v-chip v-for="c in product.colors" :key="c.key" :value="c.key" variant="outlined" label>
                                <span class="color-dot mr-2" :style="{ background: c.hex }" />
                                {{ c.label }}
                            </v-chip>
                        </v-chip-group>
                    </div>

                    <!-- Size -->
                    <div class="mb-5">
                        <div class="text-subtitle-2 font-weight-bold mb-2">
                            Talla
                        </div>

                        <v-chip-group v-model="selectedSizeKey" selected-class="chip--selected" mandatory>
                            <v-chip v-for="s in product.sizes" :key="s.key" :value="s.key" :disabled="!s.available"
                                variant="outlined" label>
                                {{ s.label }}
                            </v-chip>
                        </v-chip-group>
                    </div>

                    <!-- Qty -->
                    <div class="mb-6">
                        <div class="text-subtitle-2 font-weight-bold mb-2">
                            Cantidad
                        </div>

                        <div class="d-flex align-center ga-2">
                            <v-btn icon="mdi-minus" variant="outlined" :disabled="qty <= 1"
                                @click="qty = Math.max(1, qty - 1)" />
                            <v-text-field v-model="qty" type="number" density="compact" variant="outlined"
                                style="max-width: 110px" hide-details />
                            <v-btn icon="mdi-plus" variant="outlined" @click="qty = qty + 1" />
                        </div>
                    </div>

                    <!-- CTA -->
                    <div class="d-flex flex-wrap ga-3">
                        <v-btn color="primary" size="large" prepend-icon="mdi-cart" @click="addToCart()">
                            Añadir al carrito
                        </v-btn>

                        <v-btn size="large" variant="outlined"
                            :prepend-icon="wishlisted ? 'mdi-heart' : 'mdi-heart-outline'" @click="toggleWishlist()">
                            {{ wishlisted ? 'En favoritos' : 'Favorito' }}
                        </v-btn>
                    </div>

                    <v-alert class="mt-6" rounded="xl" variant="tonal" type="info">
                        Envío gratis en 2-4 días · Devoluciones gratuitas.
                    </v-alert>
                </v-col>
            </v-row>

            <!-- Details / Reviews -->
            <v-row class="mt-8" dense>
                <v-col cols="12" md="7">
                    <v-card rounded="xl" variant="outlined" class="pa-6">
                        <h2 class="text-h6 font-weight-bold mb-3">Detalles del producto</h2>

                        <p v-if="product.longDescription" class="text-body-1 text-medium-emphasis mb-4">
                            {{ product.longDescription }}
                        </p>

                        <v-list density="comfortable" class="px-0">
                            <v-list-item title="Referencia" :subtitle="product.ref || '—'" />
                            <v-list-item title="Marca" :subtitle="product.brandLabel || '—'" />
                            <v-list-item title="Categoría" :subtitle="product.categoryLabel || '—'" />
                            <v-list-item title="Tipo" :subtitle="product.typeLabel || '—'" />
                        </v-list>
                    </v-card>
                </v-col>

                <v-col cols="12" md="5">
                    <v-card rounded="xl" variant="outlined" class="pa-6">
                        <div class="d-flex align-center justify-space-between mb-3">
                            <h2 class="text-h6 font-weight-bold mb-0">Valoraciones</h2>
                            <v-btn size="small" variant="text" @click="writeReview()">
                                Escribir
                            </v-btn>
                        </div>

                        <v-alert v-if="product.reviews.length === 0" type="info" variant="tonal" rounded="xl">
                            Aún no hay valoraciones.
                        </v-alert>

                        <v-list v-else density="comfortable" class="px-0">
                            <v-list-item v-for="r in product.reviews" :key="r.id" class="px-0">
                                <template #prepend>
                                    <v-avatar size="40" class="mr-3">
                                        <v-img v-if="r.avatar" :src="r.avatar" cover />
                                        <span v-else class="text-caption font-weight-bold">
                                            {{ initials(r.author) }}
                                        </span>
                                    </v-avatar>
                                </template>

                                <v-list-item-title class="font-weight-bold">
                                    {{ r.author }}
                                </v-list-item-title>

                                <v-list-item-subtitle class="text-medium-emphasis">
                                    <div class="d-flex align-center ga-2">
                                        <v-rating :model-value="r.stars" density="compact" readonly />
                                        <span v-if="r.time" class="text-caption">{{ r.time }}</span>
                                    </div>
                                    <div v-if="r.text" class="mt-1">
                                        {{ r.text }}
                                    </div>
                                </v-list-item-subtitle>
                            </v-list-item>
                        </v-list>
                    </v-card>
                </v-col>
            </v-row>

            <div class="d-flex justify-end ga-3 mt-8">
                <v-btn variant="outlined" @click="router.push('/shop')">Seguir comprando</v-btn>
                <v-btn color="primary" @click="router.push('/carrito')">Ir al carrito</v-btn>
            </div>
        </div>
    </v-container>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "axios";
import { useWishlistStore } from "@/stores/wishlist";
import { useCartStore } from "../stores/cart";
import { useUiStore } from "../stores/ui";


const route = useRoute();
const router = useRouter();
const cart = useCartStore();
const ui = useUiStore();

const productId = computed(() => String(route.params.id ?? ""));

const product = ref(null);
const loading = ref(false);
const error = ref(false);
const notFound = ref(false);

const images = ref([]);
const activeImage = ref(0);

const qty = ref(1);
const wishlist = useWishlistStore();

const wishlisted = computed(() =>
    wishlist.isInWishlist(productId.value)
);

const selectedColorKey = ref(null);
const selectedSizeKey = ref(null);

const placeholderImg =
    "data:image/svg+xml;charset=UTF-8," +
    encodeURIComponent(`
    <svg xmlns="http://www.w3.org/2000/svg" width="900" height="1200">
      <rect width="100%" height="100%" fill="#EEF2F7"/>
      <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
            font-family="Arial, Helvetica, sans-serif" font-size="44" fill="#94A3B8">
        Sin imagen
      </text>
    </svg>
  `);

const breadcrumbs = computed(() => [
    { title: "Inicio", to: "/" },
    { title: "Tienda", to: "/shop" },
    { title: product.value?.name ?? "Producto", disabled: true },
]);

function formatPrice(value) {
    const n = Number(value);
    if (Number.isNaN(n)) return "";
    return new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" }).format(n);
}

function initials(name) {
    const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return "U";
    const a = parts[0]?.[0] ?? "";
    const b = parts[1]?.[0] ?? "";
    return (a + b).toUpperCase();
}

function normalizeImageUrl(src) {
    if (!src) return null;
    const s = String(src);
    if (s.startsWith("http")) return s;
    if (s.startsWith("/")) return `${window.location.origin}${s}`;
    return `${window.location.origin}/${s}`;
}

function colorHex(label) {
    const k = String(label || "").toLowerCase();
    if (k.includes("negro")) return "#111827";
    if (k.includes("blanco")) return "#F8FAFC";
    if (k.includes("azul")) return "#2563EB";
    if (k.includes("rojo")) return "#DC2626";
    if (k.includes("verde")) return "#16A34A";
    if (k.includes("gris")) return "#64748B";
    if (k.includes("marrón") || k.includes("marron")) return "#7C4A2D";
    if (k.includes("beige")) return "#EAD7C5";
    return "#94A3B8";
}

function mapApiToUi(api) {
    // imágenes (api.images puede ser [] o array de objetos)
    const imgs = Array.isArray(api?.images) ? api.images : [];
    const urls = imgs
        .map((img) => normalizeImageUrl(img?.url || img?.path || img?.src || img))
        .filter(Boolean);

    // reviews
    const rs = Array.isArray(api?.reviews) ? api.reviews : [];
    const reviews = rs.map((r) => ({
        id: String(r.id ?? Math.random()),
        author: r.user?.name ?? "Usuario",
        stars: Number(r.rating ?? r.stars ?? 0),
        time: r.created_at ? new Date(r.created_at).toLocaleDateString("es-ES") : "",
        text: r.comment ?? r.body ?? r.text ?? "",
        avatar: r.user?.avatar_url ?? r.user?.avatar ?? null,
    }));

    const rating =
        reviews.length > 0
            ? reviews.reduce((sum, r) => sum + (Number(r.stars) || 0), 0) / reviews.length
            : 0;

    // relaciones (según vuestro modelo: color/size/category/type suelen ser AttributeValue)
    const colorLabel = api?.color?.value ?? api?.color?.name ?? "Único";
    const sizeLabel = api?.size?.value ?? api?.size?.name ?? "Única";

    const colors = [
        {
            key: String(api?.color_id ?? api?.color?.id ?? "default"),
            label: colorLabel,
            hex: colorHex(colorLabel),
        },
    ];

    const sizes = [
        {
            key: String(api?.size_id ?? api?.size?.id ?? "U"),
            label: sizeLabel,
            available: true,
        },
    ];

    return {
        id: String(api?.id ?? ""),
        name: api?.name ?? "Producto",
        price: api?.price ?? 0,
        shortDescription: api?.description_short ?? "",
        longDescription: api?.description_long ?? "",
        ref: api?.url ?? String(api?.id ?? ""),
        brandLabel: api?.brand?.value ?? api?.brand ?? "",
        categoryLabel: api?.category?.value ?? api?.category?.name ?? "",
        typeLabel: api?.type?.value ?? api?.type?.name ?? "",

        images: urls.length ? urls : [placeholderImg],
        colors,
        sizes,

        reviews,
        rating,
    };
}

async function fetchProduct() {
    const id = productId.value;
    if (!id) return;

    loading.value = true;
    error.value = false;
    notFound.value = false;

    try {
        const { data } = await axios.get(`/api/products/${id}`);
        const ui = mapApiToUi(data);

        product.value = ui;
        images.value = ui.images;
        activeImage.value = 0;

        selectedColorKey.value = ui.colors[0]?.key ?? null;
        selectedSizeKey.value = ui.sizes[0]?.key ?? null;

        qty.value = 1;
    } catch (e) {
        console.error("PRODUCT DETAIL ERROR:", e);
        product.value = null;
        images.value = [];
        activeImage.value = 0;

        const status = e?.response?.status;
        if (status === 404) notFound.value = true;
        else error.value = true;
    } finally {
        loading.value = false;
    }
}

watch(productId, async () => {
    try { await wishlist.fetchWishlist(); } catch (_) { }
}, { immediate: true })

watch(productId, fetchProduct, { immediate: true });

function addToCart() {
    if (!product.value) return

    const sizeLabel =
        product.value.sizes?.find(s => s.key === selectedSizeKey.value)?.label ?? null
    const colorLabel =
        product.value.colors?.find(c => c.key === selectedColorKey.value)?.label ?? null

    cart.addToCart({
        product: product.value,
        qty: qty.value,
        size: sizeLabel,
        color: colorLabel,
    })

    ui.cartOpen = true
}

async function toggleWishlist() {
    try {
        // Aseguramos tener wishlist cargada al menos una vez
        if (!wishlist.loading && wishlist.items.length === 0) {
            try { await wishlist.fetchWishlist(); } catch (_) { }
        }

        if (wishlisted.value) {
            await wishlist.remove(productId.value);
        } else {
            await wishlist.add(productId.value);
        }
    } catch (e) {
        console.log("WISHLIST ERROR", e);
    }
}

function writeReview() {
    alert("Más adelante conectaremos esto con valoraciones reales 🙂");
}
</script>

<style scoped>
.thumb {
    cursor: pointer;
    transition: transform 120ms ease, box-shadow 120ms ease;
}

.thumb:hover {
    transform: translateY(-2px);
}

.thumb--active {
    outline: 2px solid rgba(25, 118, 210, 0.6);
    outline-offset: 2px;
}

.color-dot {
    width: 14px;
    height: 14px;
    border-radius: 999px;
    border: 1px solid rgba(0, 0, 0, 0.15);
    display: inline-block;
}

.chip--selected {
    border-color: rgba(25, 118, 210, 0.7) !important;
    background: rgba(25, 118, 210, 0.08) !important;
}
</style>
