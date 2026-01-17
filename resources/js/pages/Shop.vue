<template>
    <v-container class="py-6 px-8">
        <div class="ga-4 mt-6 mb-6">
            <div class="w-md-auto busqueda">
                <v-text-field v-model="q" clearable variant="outlined" density="comfortable" rounded="lg" hide-details
                    prepend-inner-icon="mdi-magnify" placeholder="Buscar prendas, zapatos…" />
            </div>
        </div>

        <SearchPanel :query="q" />
    </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import SearchPanel from '../components/SearchPanel.vue'
import { useWishlistStore } from '@/stores/wishlist'


const wishlist = useWishlistStore()
const q = ref('')

onMounted(async () => {
    // Si ya lo haces globalmente en App.vue, esto no molesta,
    // pero si no, esto asegura que los corazones se pinten bien.
    try { await wishlist.fetchWishlist() } catch (_) { }
})

</script>

<style scoped>
.busqueda {
    min-width: 420px;
    width: 100%;
}
</style>
