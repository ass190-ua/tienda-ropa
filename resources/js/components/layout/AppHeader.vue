<template>
    <v-app-bar flat class="border-b" color="white" density="comfortable">
        <v-container class="d-flex align-center py-0">
            <router-link to="/" class="d-flex align-center text-decoration-none mr-4">
                <v-icon icon="mdi-feather" color="primary" size="large" class="mr-2" />
                <span class="text-h6 font-weight-bold text-black">TiendaModa</span>
            </router-link>

            <div class="d-none d-md-flex ml-4 gap-4">
                <router-link to="/" custom v-slot="{ navigate, href, isActive }">
                    <v-btn :href="href" @click="navigate" :color="isActive ? 'primary' : 'black'"
                        variant="text">Inicio</v-btn>
                </router-link>

                <v-menu open-on-hover :open-on-click="false" location="bottom start" :close-on-content-click="true"
                    offset="8">
                    <template #activator="{ props }">
                        <v-btn v-bind="props" :color="isShopActive ? 'primary' : 'black'" variant="text"
                            class="text-none" @click="router.push('/shop')">
                            TIENDA
                            <v-icon icon="mdi-chevron-down" size="small" class="ml-1" />
                        </v-btn>
                    </template>

                    <v-card rounded="xl" elevation="6" class="pa-4 shop-mega">
                        <div class="d-flex ga-6">
                            <!-- Columna 1: secciones -->
                            <div style="min-width: 170px">
                                <div class="text-caption text-medium-emphasis mb-2">SECCIONES</div>

                                <v-list density="compact" nav class="pa-0">
                                    <v-list-item v-for="d in departments" :key="d.key" :active="activeDept === d.key"
                                        rounded="lg" @mouseenter="activeDept = d.key"
                                        @click="goShop({ category: d.categoryRopa })">
                                        <v-list-item-title>{{ d.label }}</v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </div>

                            <!-- Columna 2: ropa -->
                            <div style="min-width: 240px">
                                <div class="text-caption text-medium-emphasis mb-2">ROPA</div>

                                <div class="d-flex flex-column ga-1">
                                    <v-btn v-for="t in clothingTypes" :key="t" variant="text"
                                        class="justify-start text-none"
                                        @click="goShop({ category: currentDept.categoryRopa, type: t })">
                                        {{ t }}
                                    </v-btn>
                                </div>
                            </div>

                            <!-- Columna 3: zapatos -->
                            <div style="min-width: 240px">
                                <div class="text-caption text-medium-emphasis mb-2">ZAPATOS</div>

                                <div class="d-flex flex-column ga-1">
                                    <v-btn variant="text" class="justify-start text-none"
                                        @click="goShop({ category: currentDept.categoryZapatos })">
                                        Ver todos
                                    </v-btn>

                                    <v-divider class="my-2" />

                                    <v-btn v-for="t in shoeTypes" :key="t" variant="text"
                                        class="justify-start text-none"
                                        @click="goShop({ category: currentDept.categoryZapatos, type: t })">
                                        {{ t }}
                                    </v-btn>
                                </div>
                            </div>
                        </div>
                    </v-card>
                </v-menu>

                <router-link to="/novedades" custom v-slot="{ navigate, href, isActive }">
                    <v-btn :href="href" @click="navigate" :color="isActive ? 'primary' : 'black'"
                        variant="text">Novedades</v-btn>
                </router-link>
                <router-link to="/destacados" custom v-slot="{ navigate, href, isActive }">
                    <v-btn :href="href" @click="navigate" :color="isActive ? 'primary' : 'black'"
                        variant="text">Destacados</v-btn>
                </router-link>

            </div>

            <v-spacer />

            <!-- <div v-if="!isHome" class="d-none d-sm-flex mr-4" style="width: 300px">
                <v-text-field density="compact" variant="outlined" label="Buscar productos..."
                    append-inner-icon="mdi-magnify" single-line hide-details rounded="lg" bg-color="grey-lighten-4" />
            </div> -->

            <div class="d-flex align-center">
                <v-badge :content="cart.totalItems" :model-value="cart.totalItems > 0" color="primary" offset-x="6"
                    offset-y="6">
                    <v-btn icon variant="text" aria-label="Carrito" class="cart-btn" @click="cartOpen = true">
                        <v-icon icon="mdi-cart-outline" />
                    </v-btn>
                </v-badge>

                <div v-if="!auth.isAuthenticated" class="d-flex align-center">
                    <v-btn to="/login" variant="text" class="mr-1 d-none d-sm-flex">Ingresar</v-btn>
                    <v-btn to="/register" color="primary" variant="flat">Registro</v-btn>
                </div>

                <v-menu v-else location="bottom end">
                    <template v-slot:activator="{ props }">
                        <v-btn v-bind="props" variant="text" class="text-none">
                            <v-avatar color="primary" size="32" class="mr-2">
                                <span class="text-white text-caption">
                                    {{ userInitial }}
                                </span>
                            </v-avatar>
                            <span class="d-none d-sm-block">{{ auth.user.name }}</span>
                            <v-icon icon="mdi-chevron-down" size="small" class="ml-1" />
                        </v-btn>
                    </template>

                    <v-list elevation="3" rounded="lg" class="mt-2" min-width="200">
                        <div class="px-4 py-2 bg-grey-lighten-4 mb-2">
                            <div class="text-caption text-medium-emphasis">Conectado como</div>
                            <div class="text-body-2 font-weight-bold text-truncate">{{ auth.user.email }}</div>
                        </div>

                        <v-list-item to="/profile" prepend-icon="mdi-account-outline" title="Mi Perfil" />
                        <v-list-item to="/wishlist" prepend-icon="mdi-heart-outline" title="Wishlist" />
                        <v-list-item to="/orders" prepend-icon="mdi-package-variant-closed" title="Mis Pedidos" />

                        <v-divider class="my-2" />

                        <v-list-item @click="handleLogout" prepend-icon="mdi-logout" title="Cerrar Sesión"
                            color="error" />
                    </v-list>
                </v-menu>
            </div>
        </v-container>
    </v-app-bar>

    <CartDrawer v-model="cartOpen" />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'
import { useUiStore } from '../../stores/ui'
import { useRouter, useRoute } from 'vue-router'
import CartDrawer from '../CartDrawer.vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const cart = useCartStore()
const ui = useUiStore()

// Lógica para detectar si estamos en la home
const isHome = computed(() => {
    return route.path === '/'
})

const departments = [
    {
        key: 'hombre',
        label: 'Hombre',
        categoryRopa: 'Hombre',
        categoryZapatos: 'Zapatos Hombre',
        clothingTypes: ['Camiseta', 'Camisa', 'Chaqueta', 'Pantalón'],
        shoeTypes: ['Zapatilla deportiva', 'Zapato casual', 'Zapato náutico', 'Bota'],
    },
    {
        key: 'mujer',
        label: 'Mujer',
        categoryRopa: 'Mujer',
        categoryZapatos: 'Zapatos Mujer',
        clothingTypes: ['Camiseta', 'Camisa', 'Top', 'Chaqueta', 'Vestido', 'Pantalón', 'Falda'],
        shoeTypes: ['Zapatilla deportiva', 'Zapato casual', 'Zapato náutico', 'Bota', 'Bota de tacón'],
    },
    {
        key: 'nino',
        label: 'Niño',
        categoryRopa: 'Niño',
        categoryZapatos: 'Zapatos Niño',
        clothingTypes: ['Camiseta', 'Camisa', 'Chaqueta', 'Pantalón'],
        shoeTypes: ['Zapatilla deportiva', 'Zapato casual', 'Bota'],
    },
    {
        key: 'nina',
        label: 'Niña',
        categoryRopa: 'Niña',
        categoryZapatos: 'Zapatos Niña',
        clothingTypes: ['Camiseta', 'Top', 'Chaqueta', 'Vestido', 'Pantalón', 'Falda'],
        shoeTypes: ['Zapatilla deportiva', 'Zapato casual', 'Bota'],
    },
]

const activeDept = ref('hombre')

const currentDept = computed(() => departments.find(d => d.key === activeDept.value) ?? departments[0])
const clothingTypes = computed(() => currentDept.value.clothingTypes)
const shoeTypes = computed(() => currentDept.value.shoeTypes)

const isShopActive = computed(() => route.path.startsWith('/shop'))

function goShop({ category, type }) {
    router.push({
        path: '/shop',
        query: {
            ...(category ? { category } : {}),
            ...(type ? { type } : {}),
        },
    })
}

// Obtener inicial del usuario
const userInitial = computed(() => {
    return auth.user?.name?.charAt(0).toUpperCase() || 'U'
})

async function handleLogout() {
    try {
        await auth.logout()
    } finally {
        // Aunque falle el backend, dejamos al usuario fuera en el frontend
        router.push('/login')
    }
}


const cartOpen = computed({
    get: () => ui.cartOpen,
    set: (v) => (ui.cartOpen = v),
})
</script>

<style scoped>
.shop-mega {
    border: 1px solid rgba(0, 0, 0, .06);
}

.cart-btn {
    position: relative;
}
</style>
