<template>
  <v-container class="py-6 px-8">
    <div class="d-flex flex-column flex-md-row align-start align-md-center justify-space-between ga-4 mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold mb-1">Wishlist</h1>
        <div class="text-medium-emphasis">
          <span v-if="items.length">
            Guardados: <strong>{{ items.length }}</strong> {{ items.length === 1 ? 'producto' : 'productos' }}
          </span>
          <span v-else>
            Aún no has guardado ningún producto.
          </span>
        </div>
      </div>

      <div class="d-flex ga-2" v-if="items.length">
        <v-btn variant="outlined" rounded="lg" @click="onClear" :loading="wishlist.loading">
          Vaciar lista
        </v-btn>
        <v-btn color="primary" rounded="lg" @click="router.push('/shop')">
          Ir a tienda
        </v-btn>
      </div>

      <div v-else>
        <v-btn color="primary" rounded="lg" @click="router.push('/shop')">
          Explorar tienda
        </v-btn>
      </div>
    </div>

    <!-- Loading -->
    <v-alert v-if="wishlist.loading" type="info" variant="tonal" class="mb-4">
      Cargando wishlist...
    </v-alert>

    <!-- Error -->
    <v-alert v-if="wishlist.error" type="error" variant="tonal" class="mb-4">
      {{ wishlist.error }}
    </v-alert>

    <!-- Empty -->
    <v-card v-if="!wishlist.loading && items.length === 0" rounded="xl" class="pa-6 text-center" elevation="0" border>
      <v-icon icon="mdi-heart-outline" size="48" class="mb-3" color="primary" />
      <div class="text-h6 font-weight-bold mb-2">Tu wishlist está vacía</div>
      <div class="text-medium-emphasis mb-5">
        Guarda productos para verlos aquí y comprarlos cuando quieras.
      </div>

      <div class="d-flex justify-center ga-2 flex-wrap">
        <v-btn color="primary" rounded="lg" @click="router.push('/shop')">Ir a tienda</v-btn>
        <v-btn variant="outlined" rounded="lg" @click="router.push('/')">Volver a Home</v-btn>
      </div>
    </v-card>

    <!-- Grid -->
    <v-row v-else class="mt-2" dense>
      <v-col v-for="p in items" :key="p.id" cols="12" sm="6" md="4" lg="3">
        <v-card rounded="xl" elevation="2" class="h-100 d-flex flex-column">
          <v-img
            :src="productImage(p)"
            height="220"
            cover
            class="cursor-pointer"
            @click="goProduct(p.id)"
          />

          <v-card-text class="pb-0">
            <div class="text-subtitle-1 font-weight-bold">
              {{ p.name }}
            </div>
            <div class="text-medium-emphasis mt-1">
              {{ formatPrice(p.price) }}
            </div>
          </v-card-text>

          <v-spacer />

          <v-card-actions class="pa-4 pt-3 d-flex ga-2">
            <v-btn color="primary" variant="flat" rounded="lg" class="flex-grow-1" @click="goProduct(p.id)">
              Ver
            </v-btn>

            <v-btn variant="outlined" rounded="lg" color="error" @click="onRemove(p.id)" :loading="wishlist.loading">
              <v-icon icon="mdi-delete-outline" />
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWishlistStore } from '../stores/wishlist'

const router = useRouter()
const wishlist = useWishlistStore()

const items = computed(() => wishlist.items)

onMounted(async () => {
  await wishlist.fetchWishlist()
})

function formatPrice(value) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value)
}

function productImage(p) {
  const img = p?.images?.[0]
  return img?.url || img?.image_url || img?.path || 'https://via.placeholder.com/600x600?text=Producto'
}

function goProduct(id) {
  router.push(`/producto/${id}`)
}

async function onRemove(productId) {
  await wishlist.remove(productId)
}

async function onClear() {
  await wishlist.clear()
}
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
