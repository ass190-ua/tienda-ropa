<template>
    <div>
        <v-carousel height="500" hide-delimiter-background show-arrows="hover" cycle interval="6000">
            <v-carousel-item v-for="(slide, i) in slides" :key="i" :src="slide.src" cover>
                <div class="d-flex fill-height justify-center align-center text-center text-white bg-black-opacity">
                    <div>
                        <div class="text-h2 font-weight-bold mb-4 text-shadow">
                            {{ slide.title }}
                        </div>
                        <div class="text-h5 mb-6 text-shadow">{{ slide.subtitle }}</div>
                        <v-btn color="white" variant="outlined" size="x-large" to="/shop" class="text-white">
                            Explorar Colección
                        </v-btn>
                    </div>
                </div>
            </v-carousel-item>
        </v-carousel>

        <v-container class="py-12">
            <v-row class="mb-12">
                <v-col cols="12" md="4" v-for="cat in categories" :key="cat.title">
                    <v-card hover class="mx-auto rounded-lg overflow-hidden position-relative" height="250"
                        @click="$router.push('/shop')">
                        <v-img :src="cat.image" cover height="100%" class="align-end zoom-effect">
                            <v-card-title class="text-white bg-gradient text-h5 font-weight-bold">
                                {{ cat.title }}
                            </v-card-title>
                        </v-img>
                    </v-card>
                </v-col>
            </v-row>

            <div class="d-flex align-center justify-space-between mb-6">
                <h2 class="text-h4 font-weight-bold">Últimas Novedades</h2>
                <v-btn variant="text" color="primary" to="/shop" append-icon="mdi-arrow-right">
                    Ver todo
                </v-btn>
            </div>

            <v-row v-if="loading">
                <v-col v-for="n in 4" :key="n" cols="12" sm="6" md="3">
                    <v-skeleton-loader type="card, article"></v-skeleton-loader>
                </v-col>
            </v-row>

            <v-row class="gy-6">
                <v-col v-for="p in products" :key="p.id" cols="12" sm="6" lg="4">
                    <ProductCard :product="p" />
                </v-col>
            </v-row>

        </v-container>

        <v-sheet color="grey-lighten-4" class="py-12 mt-6">
            <v-container class="text-center">
                <v-icon icon="mdi-email-outline" size="50" class="mb-4" color="primary"></v-icon>
                <h3 class="text-h4 font-weight-bold mb-2">Únete a nuestra Newsletter</h3>
                <p class="text-body-1 text-medium-emphasis mb-6">
                    Recibe ofertas exclusivas y novedades antes que nadie.
                </p>

                <v-responsive max-width="500" class="mx-auto">
                    <v-form @submit.prevent="onSubscribe">
                        <v-text-field
                            v-model="email"
                            label="Tu correo electrónico"
                            variant="solo"
                            single-line
                            hide-details
                            :loading="submitting"
                            :disabled="submitting"
                        >
                            <template v-slot:append-inner>
                                <v-icon
                                    icon="mdi-arrow-right"
                                    color="primary"
                                    class="cursor-pointer"
                                    @click="onSubscribe"
                                ></v-icon>
                            </template>
                        </v-text-field>
                    </v-form>

                    <div v-if="newsletterMessage"
                         class="mt-3 font-weight-bold"
                         :class="isError ? 'text-red' : 'text-green-darken-1'">
                        {{ newsletterMessage }}
                    </div>
                </v-responsive>
            </v-container>
        </v-sheet>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ProductCard from '../components/ProductCard.vue'
import axios from 'axios'

// --- ESTADO PRODUCTOS ---
const products = ref([])
const loading = ref(true)

// --- ESTADO NEWSLETTER (NUEVO) ---
const email = ref('')
const submitting = ref(false)
const newsletterMessage = ref('')
const isError = ref(false)

// --- DATOS ESTÁTICOS (Visuales) ---
const slides = [
    {
        src: 'https://images.unsplash.com/photo-1483985988355-763728e1935b?q=80&w=2070&auto=format&fit=crop',
        title: 'Nueva Colección 2026',
        subtitle: 'Tendencias que definen tu estilo'
    },
    {
        src: 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?q=80&w=2070&auto=format&fit=crop',
        title: 'Ofertas de Temporada',
        subtitle: 'Hasta 50% de descuento en seleccionados'
    }
]

const categories = [
    { title: 'Mujer', image: 'https://images.unsplash.com/photo-1525845859779-54d477ff291f?q=80&w=600&auto=format&fit=crop' },
    { title: 'Hombre', image: 'https://images.unsplash.com/photo-1516257984-b1b4d8c9230c?q=80&w=600&auto=format&fit=crop' },
    { title: 'Accesorios', image: 'https://images.unsplash.com/photo-1576053139778-7e32f2ae3cfd?q=80&w=600&auto=format&fit=crop' }
]

// --- LÓGICA ---

async function fetchHomeProducts() {
    try {
        const { data } = await axios.get('/api/products/home')
        products.value = data
    } catch (e) {
        console.error("Error cargando productos:", e)
    } finally {
        loading.value = false
    }
}

// --- LÓGICA NEWSLETTER (NUEVO) ---
async function onSubscribe() {
    if (!email.value) return

    submitting.value = true
    newsletterMessage.value = ''
    isError.value = false

    try {
        const response = await axios.post('/api/newsletter/subscribe', {
            email: email.value
        })

        newsletterMessage.value = response.data.message
        email.value = '' // Limpiar campo
    } catch (error) {
        isError.value = true
        newsletterMessage.value = error.response?.data?.message || "Hubo un error. Inténtalo de nuevo."
    } finally {
        submitting.value = false
        // Borrar mensaje a los 5 segundos si fue éxito
        if (!isError.value) {
            setTimeout(() => { newsletterMessage.value = '' }, 5000)
        }
    }
}

onMounted(() => {
  fetchHomeProducts()
})
</script>

<style scoped>
/* Efecto de oscurecimiento para leer texto sobre imagen */
.bg-black-opacity {
    background: rgba(0, 0, 0, 0.4);
}

.text-shadow {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

/* Gradiente para títulos de categorías */
.bg-gradient {
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
    width: 100%;
}

/* Efecto Zoom suave */
.zoom-effect .v-img__img {
    transition: transform 0.3s ease;
}

.v-card:hover .zoom-effect .v-img__img {
    transform: scale(1.1);
}
</style>
