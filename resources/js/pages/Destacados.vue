<template>
    <v-container class="py-6">
        <!-- Estado de carga o error -->
        <v-alert v-if="loading" type="info" variant="tonal">Cargando productos...</v-alert>
        <v-alert v-else-if="error" type="error" variant="tonal">
            Ha ocurrido un error cargando los destacados.
            <div class="text-caption mt-2">{{ error }}</div>
        </v-alert>

        <div v-else>
            <!-- ===================== -->
            <!--       MÁS COMPRADOS    -->
            <!-- ===================== -->
            <div class="mt-8">
                <div class="d-flex align-center justify-space-between mb-4">
                    <h2 class="text-h5 font-weight-bold">Más comprados</h2>
                    <div class="text-medium-emphasis text-caption">Top 10</div>
                </div>

                <v-alert v-if="topPurchased.length === 0" type="warning" variant="tonal">
                    No hay datos de compras aún.
                </v-alert>

                <template v-else>
                    <!-- Podio -->
                    <div class="podium">
                        <v-row class="gy-6" align="end" justify="center">
                            <!-- 2º -->
                            <v-col cols="12" sm="4" class="d-flex justify-center">
                                <div v-if="purchasedPodium[1]" class="podium-card podium-2 medal silver">
                                    <div class="podium-badge">2</div>
                                    <ProductCard :product="purchasedPodium[1]" />
                    </div>
                    </v-col>

                    <!-- 1º -->
                    <v-col cols="12" sm="4" class="d-flex justify-center">
                        <div v-if="purchasedPodium[0]" class="podium-card podium-1 medal gold">
                            <div class="podium-badge">1</div>
                            <ProductCard :product="purchasedPodium[0]" />
                        </div>
                    </v-col>

                    <!-- 3º -->
                    <v-col cols="12" sm="4" class="d-flex justify-center">
                        <div v-if="purchasedPodium[2]" class="podium-card podium-3 medal bronze">
                            <div class="podium-badge">3</div>
                            <ProductCard :product="purchasedPodium[2]" />
                        </div>
                    </v-col>
                    </v-row>
            </div>

            <!-- Resto (lista) -->
            <div class="mt-8">
                <!-- Resto (grid compacto) -->
                <v-row class="gy-6 mt-2">
                    <v-col v-for="(p, idx) in purchasedRest" :key="'purchased-rest-' + p.id" cols="12" sm="6" md="4"
                        lg="3">
                        <div class="rank-wrap">
                            <div class="rank-badge">{{ idx + 4 }}</div>
                            <ProductCard :product="p" class="rank-card" />
                        </div>
                    </v-col>
                </v-row>

            </div>
</template>
</div>

<!-- ===================== -->
<!--  MÁS GUARDADOS WISHLIST -->
<!-- ===================== -->
<div class="mt-12">
    <div class="d-flex align-center justify-space-between mb-4">
        <h2 class="text-h5 font-weight-bold">Más guardados en wishlist</h2>
        <div class="text-medium-emphasis text-caption">Top 10</div>
    </div>

    <v-alert v-if="topWishlisted.length === 0" type="warning" variant="tonal">
        No hay datos de wishlist aún.
    </v-alert>

    <template v-else>
        <!-- Podio -->
        <div class="podium">
            <v-row class="gy-6" align="end" justify="center">
                <!-- 2º -->
                <v-col cols="12" sm="4" class="d-flex justify-center">
                     <<div v-if="purchasedPodium[1]" class="podium-card podium-2 medal silver">
                        <div class="podium-badge">2</div>
                        <ProductCard :product="wishlistedPodium[1]" />
                    </div>
                </v-col>

                <!-- 1º -->
                <v-col cols="12" sm="4" class="d-flex justify-center">
                    <div v-if="purchasedPodium[0]" class="podium-card podium-1 medal gold">
                        <div class="podium-badge">1</div>
                        <ProductCard :product="wishlistedPodium[0]" />
                    </div>
                </v-col>

                <!-- 3º -->
                <v-col cols="12" sm="4" class="d-flex justify-center">
                    <div v-if="wishlistedPodium[2]" class="podium-card podium-3 medal bronze">
                        <div class="podium-badge">3</div>
                        <ProductCard :product="wishlistedPodium[2]" />
                    </div>
                </v-col>
            </v-row>
        </div>

        <!-- Resto (lista) -->
        <div class="mt-8">
            <!-- Resto (grid compacto) -->
            <v-row class="gy-6 mt-2">
                <v-col v-for="(p, idx) in purchasedRest" :key="'purchased-rest-' + p.id" cols="12" sm="6" md="4" lg="3">
                    <div class="rank-wrap">
                        <div class="rank-badge">{{ idx + 4 }}</div>
                        <ProductCard :product="p" class="rank-card" />
                    </div>
                </v-col>
            </v-row>

        </div>
    </template>
</div>

<!-- Snackbar (si tu ProductCard lo usa, puedes quitarlo) -->
<v-snackbar v-model="snackbar.open" :timeout="2200" rounded="lg">
    {{ snackbar.text }}
</v-snackbar>
</div>
</v-container>
</template>


<script setup>
import { onMounted, ref, computed } from 'vue'
import axios from 'axios'
import { useWishlistStore } from '@/stores/wishlist'
import ProductCard from '@/components/ProductCard.vue'

const wishlist = useWishlistStore()

const loading = ref(true)
const error = ref('')

const topPurchased = ref([])
const topWishlisted = ref([])

const snackbar = ref({ open: false, text: '' })

const purchasedPodium = computed(() => topPurchased.value.slice(0, 3))
const purchasedRest = computed(() => topPurchased.value.slice(3, 10))

const wishlistedPodium = computed(() => topWishlisted.value.slice(0, 3))
const wishlistedRest = computed(() => topWishlisted.value.slice(3, 10))

onMounted(async () => {
    // wishlist para corazones (si ProductCard depende del store)
    try { await wishlist.fetchWishlist() } catch (_) { }

    try {
        const [resPurchased, resWishlisted] = await Promise.all([
            axios.get('/api/products/top-purchased'),
            axios.get('/api/products/top-wishlisted'),
        ])

        topPurchased.value = Array.isArray(resPurchased.data) ? resPurchased.data : []
        topWishlisted.value = Array.isArray(resWishlisted.data) ? resWishlisted.data : []
    } catch (e) {
        error.value = e?.message ?? String(e)
    } finally {
        loading.value = false
    }
})
</script>


<style scoped>
/* --- Podio (lo tuyo) --- */
.podium {
    margin-top: 8px;
}

.podium-card {
    position: relative;
    width: 100%;
    max-width: 420px;
}

.podium-1 {
    transform: translateY(-6px) scale(1.03);
}

.podium-2 {
    transform: translateY(6px) scale(0.98);
}

.podium-3 {
    transform: translateY(10px) scale(0.97);
}

.podium-badge {
    position: absolute;
    top: -10px;
    left: -10px;
    z-index: 5;
    width: 34px;
    height: 34px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    font-weight: 800;
    background: rgba(0, 0, 0, 0.85);
    color: white;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
}

/* --- Resto (grid con ranking) --- */
.rank-wrap {
    position: relative;
}

/* badge elegante tipo “ranking” */
.rank-badge {
    position: absolute;
    top: -10px;
    left: -10px;
    z-index: 6;
    width: 34px;
    height: 34px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    font-weight: 800;
    background: rgba(0, 0, 0, 0.78);
    color: #fff;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
}

/* un poquito de “premium feel” en el grid */
.rank-card {
    transition: transform 140ms ease, box-shadow 140ms ease;
}

.rank-wrap:hover .rank-card {
    transform: translateY(-2px);
}

/* --- Medallas podio (wrap alrededor del ProductCard) --- */
.medal {
    border-radius: 24px;
    /* redondeo para que acompañe a la card */
    padding: 10px;
    /* “marco” */
    position: relative;
}

/* Dorado */
.medal.gold {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.35), rgba(255, 215, 0, 0.08));
    box-shadow: 0 18px 40px rgba(255, 215, 0, 0.12);
    border: 1px solid rgba(255, 215, 0, 0.45);
}

/* Plata */
.medal.silver {
    background: linear-gradient(135deg, rgba(192, 192, 192, 0.38), rgba(192, 192, 192, 0.10));
    box-shadow: 0 18px 40px rgba(160, 160, 160, 0.10);
    border: 1px solid rgba(192, 192, 192, 0.55);
}

/* Bronce */
.medal.bronze {
    background: linear-gradient(135deg, rgba(205, 127, 50, 0.35), rgba(205, 127, 50, 0.10));
    box-shadow: 0 18px 40px rgba(205, 127, 50, 0.10);
    border: 1px solid rgba(205, 127, 50, 0.55);
}

/* Badge del número: lo pintamos según la medalla */
.medal.gold .podium-badge {
    background: rgba(255, 215, 0, 0.95);
    color: rgba(0, 0, 0, 0.85);
}

.medal.silver .podium-badge {
    background: rgba(192, 192, 192, 0.95);
    color: rgba(0, 0, 0, 0.85);
}

.medal.bronze .podium-badge {
    background: rgba(205, 127, 50, 0.95);
    color: rgba(0, 0, 0, 0.90);
}
</style>
