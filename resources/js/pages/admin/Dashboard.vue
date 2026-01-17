<template>
  <v-container fluid class="pa-6">

    <div class="mb-8">
      <h1 class="text-h4 font-weight-bold text-grey-darken-3">
        Panel de Control
      </h1>
      <p class="text-body-1 text-grey-darken-1">
        Bienvenido de nuevo, {{ authStore.user?.name }}.
      </p>
    </div>

    <v-row justify="center">

      <v-col cols="12" sm="6" lg="4">
        <v-hover v-slot="{ isHovering, props }">
          <v-card
            v-bind="props"
            :elevation="isHovering ? 8 : 2"
            class="cursor-pointer transition-swing rounded-lg h-100"
            color="success"
            theme="dark"
            to="/admin/orders"
          >
            <div class="d-flex flex-column fill-height">
              <div class="d-flex justify-space-between pa-4">
                <div>
                  <div class="text-h4 font-weight-bold">{{ stats.orders_count || 0 }}</div>
                  <div class="text-subtitle-1 opacity-80 mt-1">Pedidos</div>
                  <div class="mt-2 pt-2 border-t-opacity-20 d-flex align-center text-green-lighten-4">
                     <v-icon size="small" class="mr-1">mdi-cash-multiple</v-icon>
                     <span class="text-h6 font-weight-bold">
                        {{ parseFloat(stats.revenue || 0).toFixed(2) }}€
                     </span>
                  </div>
                </div>
                <v-icon size="48" class="opacity-50">mdi-cart-outline</v-icon>
              </div>
              <v-spacer></v-spacer>
              <v-divider class="opacity-20"></v-divider>
              <div class="pa-3 bg-green-darken-2 d-flex align-center">
                <span class="text-caption font-weight-bold">Gestionar Pedidos</span>
                <v-spacer></v-spacer>
                <v-icon size="small">mdi-arrow-right</v-icon>
              </div>
            </div>
          </v-card>
        </v-hover>
      </v-col>

      <v-col cols="12" sm="6" lg="4">
        <v-hover v-slot="{ isHovering, props }">
          <v-card
            v-bind="props"
            :elevation="isHovering ? 8 : 2"
            class="cursor-pointer transition-swing rounded-lg h-100"
            color="orange-darken-1"
            theme="dark"
            to="/admin/products"
          >
            <div class="d-flex flex-column fill-height">
              <div class="d-flex justify-space-between pa-4">
                <div>
                  <div class="text-h4 font-weight-bold">{{ stats.products_count || 0 }}</div>
                  <div class="text-subtitle-1 opacity-80 mt-1">Productos</div>
                </div>
                <v-icon size="48" class="opacity-50">mdi-tshirt-crew-outline</v-icon>
              </div>
              <v-spacer></v-spacer>
              <v-divider class="opacity-20"></v-divider>
              <div class="pa-3 bg-orange-darken-2 d-flex align-center">
                <span class="text-caption font-weight-bold">Ver Catálogo</span>
                <v-spacer></v-spacer>
                <v-icon size="small">mdi-arrow-right</v-icon>
              </div>
            </div>
          </v-card>
        </v-hover>
      </v-col>

      <v-col cols="12" sm="6" lg="4">
        <v-hover v-slot="{ isHovering, props }">
          <v-card
            v-bind="props"
            :elevation="isHovering ? 8 : 2"
            class="cursor-pointer transition-swing rounded-lg h-100"
            color="blue-darken-1"
            theme="dark"
            to="/admin/users"
          >
            <div class="d-flex flex-column fill-height">
              <div class="d-flex justify-space-between pa-4">
                <div>
                  <div class="text-h4 font-weight-bold">{{ stats.users_count || 0 }}</div>
                  <div class="text-subtitle-1 opacity-80 mt-1">Usuarios</div>
                </div>
                <v-icon size="48" class="opacity-50">mdi-account-group-outline</v-icon>
              </div>
              <v-spacer></v-spacer>
              <v-divider class="opacity-20"></v-divider>
              <div class="pa-3 bg-blue-darken-2 d-flex align-center">
                <span class="text-caption font-weight-bold">Administrar Usuarios</span>
                <v-spacer></v-spacer>
                <v-icon size="small">mdi-arrow-right</v-icon>
              </div>
            </div>
          </v-card>
        </v-hover>
      </v-col>

      <v-col cols="12" sm="6" lg="4">
        <v-hover v-slot="{ isHovering, props }">
          <v-card
            v-bind="props"
            :elevation="isHovering ? 8 : 2"
            class="cursor-pointer transition-swing rounded-lg h-100"
            color="purple-darken-1"
            theme="dark"
            to="/admin/reviews"
          >
            <div class="d-flex flex-column fill-height">
              <div class="d-flex justify-space-between pa-4">
                <div>
                  <div class="text-h4 font-weight-bold">{{ stats.reviews_count || 0 }}</div>
                  <div class="text-subtitle-1 opacity-80 mt-1">Comentarios</div>
                </div>
                <v-icon size="48" class="opacity-50">mdi-comment-text-multiple-outline</v-icon>
              </div>
              <v-spacer></v-spacer>
              <v-divider class="opacity-20"></v-divider>
              <div class="pa-3 bg-purple-darken-2 d-flex align-center">
                <span class="text-caption font-weight-bold">Moderar Reseñas</span>
                <v-spacer></v-spacer>
                <v-icon size="small">mdi-arrow-right</v-icon>
              </div>
            </div>
          </v-card>
        </v-hover>
      </v-col>

      <v-col cols="12" sm="6" lg="4">
        <v-hover v-slot="{ isHovering, props }">
          <v-card
            v-bind="props"
            :elevation="isHovering ? 8 : 2"
            class="cursor-pointer transition-swing rounded-lg h-100"
            color="pink-darken-1"
            theme="dark"
            to="/admin/coupons"
          >
            <div class="d-flex flex-column fill-height">
              <div class="d-flex justify-space-between pa-4">
                <div>
                  <div class="text-h4 font-weight-bold">{{ stats.coupons_count || 0 }}</div>
                  <div class="text-subtitle-1 opacity-80 mt-1">Cupones</div>
                </div>
                <v-icon size="48" class="opacity-50">mdi-ticket-percent-outline</v-icon>
              </div>
              <v-spacer></v-spacer>
              <v-divider class="opacity-20"></v-divider>
              <div class="pa-3 bg-pink-darken-2 d-flex align-center">
                <span class="text-caption font-weight-bold">Gestionar Descuentos</span>
                <v-spacer></v-spacer>
                <v-icon size="small">mdi-arrow-right</v-icon>
              </div>
            </div>
          </v-card>
        </v-hover>
      </v-col>

    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const stats = ref({});

onMounted(async () => {
    try {
        const response = await axios.get('/api/admin/stats');
        stats.value = response.data;
    } catch (error) {
        console.error("Error cargando stats del dashboard", error);
    }
});
</script>

<style scoped>
.transition-swing {
  transition: 0.3s cubic-bezier(0.25, 0.8, 0.5, 1);
}
</style>
