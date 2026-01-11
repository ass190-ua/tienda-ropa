<template>
    <v-app>
        <AppHeader />

        <v-main>
            <router-view />
        </v-main>

        <AppFooter />

        <ScrollToTop />
    </v-app>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { useAuthStore } from './stores/auth'
import { useCartStore } from './stores/cart'
import { useWishlistStore } from './stores/wishlist'
import AppHeader from './components/layout/AppHeader.vue'
import AppFooter from './components/layout/AppFooter.vue'
import ScrollToTop from './components/ScrollToTop.vue'

const auth = useAuthStore()
const cart = useCartStore()
const wishlist = useWishlistStore()

watch(
    () => auth.user?.id,
    (id) => cart.setOwner(id ?? null),
    { immediate: true }
)

onMounted(async () => {
    await auth.fetchUser()
    try { await wishlist.fetchWishlist() } catch (_) { }
})
</script>

<style>
.app-shell {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.app-main {
    flex: 1 1 auto;
    display: flex;
    background: rgb(var(--v-theme-background));
}

.page-wrap {
    flex: 1 1 auto;
    display: flex;
    padding: 32px 0;
}

.page {
    flex: 1 1 auto;
}
</style>
