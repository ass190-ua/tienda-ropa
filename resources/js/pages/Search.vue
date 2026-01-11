<template>
    <v-container class="py-6">
        <!-- Header de la página -->
        <div class="d-flex flex-column flex-md-row align-start align-md-center justify-space-between ga-4 mb-6">
            <div>
                <h1 class="text-h4 font-weight-bold mb-1">Búsqueda</h1>
                <div class="text-medium-emphasis">Encuentra productos por nombre, categoría o precio.</div>
            </div>

            <!-- Buscador + orden -->
            <div class="d-flex flex-column flex-sm-row ga-3 w-100 w-md-auto">
                <v-text-field ref="searchRef" v-model="search" variant="outlined" density="comfortable" hide-details
                    placeholder="Buscar productos…" prepend-inner-icon="mdi-magnify" class="minw" />

                <v-select v-model="sort" :items="sortItems" variant="outlined" density="comfortable" hide-details
                    class="minw" label="Ordenar" />
            </div>
        </div>

        <v-row class="gy-6">
            <!-- Sidebar filtros -->
            <v-col cols="12" md="3">
                <v-card rounded="xl" elevation="2" class="pa-4">
                    <div class="text-subtitle-1 font-weight-bold mb-3">Filtros</div>

                    <div class="text-body-2 font-weight-bold mb-2">Categoría</div>
                    <v-chip-group v-model="category" column>
                        <v-chip v-for="c in categories" :key="c" :value="c" variant="outlined" class="mb-2">
                            {{ c }}
                        </v-chip>
                    </v-chip-group>

                    <v-divider class="my-4" />

                    <div class="d-flex align-center justify-space-between mb-2">
                        <div class="text-body-2 font-weight-bold">Precio</div>
                        <div class="text-medium-emphasis text-body-2">
                            {{ price[0] }}€ — {{ price[1] }}€
                        </div>
                    </div>

                    <v-range-slider v-model="price" :min="0" :max="200" :step="5" thumb-label="always"
                        density="comfortable" />

                    <v-divider class="my-4" />

                    <v-checkbox v-model="onlyDiscount" hide-details label="Solo ofertas" />
                    <v-checkbox v-model="onlyInStock" hide-details label="En stock" />

                    <v-btn class="mt-4" variant="outlined" block rounded="lg" @click="resetFilters">
                        Limpiar filtros
                    </v-btn>
                </v-card>
            </v-col>

            <!-- Grid productos -->
            <v-col cols="12" md="9">
                <v-row class="gy-6">
                    <v-col v-for="p in filteredProducts" :key="p.id" cols="12" sm="6" lg="4">
                        <v-card rounded="xl" elevation="3" class="product-card" @click="goToProduct(p.id)">
                            <div class="img-wrap">
                                <v-img :src="p.image" cover height="220" />
                                <v-chip v-if="p.discount" color="primary" variant="flat" class="discount-chip"
                                    size="small">
                                    -{{ p.discount }}%
                                </v-chip>
                            </div>

                            <v-card-text class="pt-4">
                                <div class="d-flex align-center justify-space-between mb-1">
                                    <div class="text-subtitle-1 font-weight-bold">{{ p.name }}</div>

                                    <v-btn icon variant="text" @click.stop="toggleWish(p)" aria-label="Favorito">
                                        <v-icon :icon="p.wish ? 'mdi-heart' : 'mdi-heart-outline'" />
                                    </v-btn>
                                </div>

                                <div class="text-medium-emphasis text-body-2 mb-3">
                                    {{ p.short }}
                                </div>

                                <div class="d-flex align-center justify-space-between">
                                    <div class="d-flex align-center ga-2">
                                        <div class="text-h6 font-weight-bold">{{ finalPrice(p).toFixed(2) }}€</div>
                                        <div v-if="p.discount" class="old-price">
                                            {{ p.price.toFixed(2) }}€
                                        </div>
                                    </div>

                                    <v-btn color="primary" variant="flat" rounded="lg" class="text-none"
                                        :disabled="!p.inStock" @click.stop="addToCart(p)">
                                        Añadir
                                    </v-btn>
                                </div>

                                <div v-if="!p.inStock" class="stock-out mt-3">
                                    Sin stock
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <v-col v-if="filteredProducts.length === 0" cols="12">
                        <v-alert type="info" variant="tonal">
                            No hay productos con esos filtros.
                        </v-alert>
                    </v-col>
                </v-row>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const router = useRouter()
const route = useRoute()

const searchRef = ref(null)

const search = ref('')
const sort = ref('Relevancia')
const sortItems = ['Relevancia', 'Precio: menor a mayor', 'Precio: mayor a menor']

const categories = ['Todos', 'Hombre', 'Mujer', 'Niños', 'Ofertas']
const category = ref('Todos')

const price = ref([0, 200])
const onlyDiscount = ref(false)
const onlyInStock = ref(false)

// Mock products (luego se conecta a backend)
const products = ref([
    { id: 1, name: 'Sudadera Essential', short: 'Algodón premium, corte regular.', price: 49.99, discount: 20, cat: 'Hombre', inStock: true, wish: false, image: 'https://picsum.photos/seed/hoodie/600/500' },
    { id: 2, name: 'Zapatillas Urban', short: 'Comodidad diaria y estilo.', price: 79.99, discount: 0, cat: 'Mujer', inStock: true, wish: false, image: 'https://picsum.photos/seed/shoes/600/500' },
    { id: 3, name: 'Camiseta Basic', short: 'Suave, ligera y versátil.', price: 19.99, discount: 10, cat: 'Hombre', inStock: true, wish: false, image: 'https://picsum.photos/seed/tshirt/600/500' },
    { id: 4, name: 'Chaqueta Denim', short: 'Clásica, resistente, atemporal.', price: 89.99, discount: 0, cat: 'Mujer', inStock: false, wish: false, image: 'https://picsum.photos/seed/denim/600/500' },
    { id: 5, name: 'Pack Kids', short: 'Conjunto cómodo para diario.', price: 34.99, discount: 15, cat: 'Niños', inStock: true, wish: false, image: 'https://picsum.photos/seed/kids/600/500' },
    { id: 6, name: 'Pantalón Cargo', short: 'Bolsillos amplios, look urbano.', price: 59.99, discount: 0, cat: 'Hombre', inStock: true, wish: false, image: 'https://picsum.photos/seed/cargo/600/500' },
])

onMounted(async () => {
    // Si vienes desde Home con /search?focus=1, enfocamos el input.
    if (route.query.focus) {
        await nextTick()
        focusSearch()
    }
})

function focusSearch() {
    // Vuetify a veces expone focus(), si no buscamos el input
    searchRef.value?.focus?.()
    searchRef.value?.$el?.querySelector('input')?.focus?.()
}

const filteredProducts = computed(() => {
    const s = search.value.trim().toLowerCase()

    let list = products.value.filter(p => {
        const matchesSearch =
            !s || p.name.toLowerCase().includes(s) || p.short.toLowerCase().includes(s)

        const matchesCat =
            category.value === 'Todos'
                ? true
                : category.value === 'Ofertas'
                    ? p.discount > 0
                    : p.cat === category.value

        const pFinal = finalPrice(p)
        const priceOk = pFinal >= price.value[0] && pFinal <= price.value[1]

        const discOk = !onlyDiscount.value || p.discount > 0
        const stockOk = !onlyInStock.value || p.inStock

        return matchesSearch && matchesCat && priceOk && discOk && stockOk
    })

    if (sort.value === 'Precio: menor a mayor') list.sort((a, b) => finalPrice(a) - finalPrice(b))
    if (sort.value === 'Precio: mayor a menor') list.sort((a, b) => finalPrice(b) - finalPrice(a))

    return list
})

function finalPrice(p) {
    if (!p.discount) return p.price
    return p.price * (1 - p.discount / 100)
}

function resetFilters() {
    search.value = ''
    sort.value = 'Relevancia'
    category.value = 'Todos'
    price.value = [0, 200]
    onlyDiscount.value = false
    onlyInStock.value = false
}

function toggleWish(p) {
    p.wish = !p.wish
}

function addToCart(p) {
    // UI-only por ahora
    console.log('add to cart', p.id)
}

function goToProduct(id) {
    // Cuando hagamos detalle de producto, crearemos /product/:id
    router.push(`/product/${id}`)
}
</script>

<style scoped>
.minw {
    min-width: 220px;
}

.product-card {
    cursor: pointer;
    transition: transform 120ms ease;
}

.product-card:hover {
    transform: translateY(-2px);
}

.img-wrap {
    position: relative;
}

.discount-chip {
    position: absolute;
    top: 12px;
    left: 12px;
}

.old-price {
    color: rgba(0, 0, 0, 0.5);
    text-decoration: line-through;
    font-size: 14px;
}

.stock-out {
    font-weight: 700;
    color: rgba(0, 0, 0, 0.55);
}
</style>
