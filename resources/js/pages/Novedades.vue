<template>
    <v-container class="py-6">
        <NovedadesCategoryCarousel title="Mujer" :category-id="39" :limit="12" />
        <NovedadesCategoryCarousel title="Hombre" :category-id="40" :limit="12" />
        <NovedadesCategoryCarousel title="Zapatos Mujer" :category-id="43" :limit="12" />
        <NovedadesCategoryCarousel title="Zapatos Hombre" :category-id="44" :limit="12" />
    </v-container>
</template>

<script setup>
import { onMounted } from "vue";
import NovedadesCategoryCarousel from "../components/NovedadesCategoryCarousel.vue";
import { useWishlistStore } from "@/stores/wishlist";

const wishlist = useWishlistStore();
onMounted(async () => {
    try { await wishlist.fetchWishlist(); } catch (_) { }
});
</script>
