<template>
  <v-layout class="fill-height" style="height: 100vh; overflow: hidden;">

    <v-navigation-drawer
      v-model="drawer"
      color="white"
      width="280"
      border="r"
    >
      <div class="d-flex align-center pa-6">
        <v-avatar color="blue-darken-3" size="40" class="mr-3" variant="tonal">
          <v-icon icon="mdi-store"></v-icon>
        </v-avatar>
        <div>
          <h2 class="text-subtitle-1 font-weight-bold text-grey-darken-3">TiendaModa</h2>
          <div class="text-caption text-grey-darken-1">Panel de Administración</div>
        </div>
      </div>

      <v-divider class="mb-2"></v-divider>

      <v-list nav density="comfortable">
        <v-list-item
          v-for="(item, i) in menuItems"
          :key="i"
          :to="item.to"
          :title="item.title"
          class="mb-1 rounded-lg"
          active-class="bg-blue-lighten-5 text-blue-darken-2"
        >
          <template v-slot:prepend>

            <div
              v-if="item.title === 'Dashboard'"
              class="d-flex flex-wrap align-center justify-center mr-4"
              style="width: 24px; height: 24px; gap: 2px;"
            >
              <div class="bg-green-darken-1 rounded-sm" style="width: 10px; height: 10px;"></div>
              <div class="bg-orange-darken-1 rounded-sm" style="width: 10px; height: 10px;"></div>
              <div class="bg-blue-darken-1 rounded-sm" style="width: 10px; height: 10px;"></div>
              <div class="bg-purple-darken-1 rounded-sm" style="width: 10px; height: 10px;"></div>
            </div>

            <v-icon
              v-else
              :icon="item.icon"
              :color="item.color"
            ></v-icon>

          </template>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>


    <v-app-bar elevation="0" border="b" color="white" height="64">
      <v-app-bar-nav-icon @click="drawer = !drawer" color="grey-darken-2"></v-app-bar-nav-icon>
      <v-app-bar-title class="text-grey-darken-3 font-weight-medium">
        {{ currentRouteName }}
      </v-app-bar-title>
    </v-app-bar>

    <v-main class="bg-grey-lighten-5" style="overflow-y: auto;">
        <router-view></router-view>
    </v-main>

  </v-layout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useRouter, useRoute } from 'vue-router';

const drawer = ref(true);
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const menuItems = [
  {
    title: 'Dashboard',
    icon: 'mdi-view-dashboard', // Este icono se ignora visualmente por el v-if, pero lo dejamos por consistencia
    to: '/admin/dashboard',
    color: 'grey-darken-2'
  },
  {
    title: 'Pedidos',
    icon: 'mdi-cart',
    to: '/admin/orders',
    color: 'green-darken-1'
  },
  {
    title: 'Productos',
    icon: 'mdi-tshirt-crew',
    to: '/admin/products',
    color: 'orange-darken-1'
  },
  {
    title: 'Usuarios',
    icon: 'mdi-account-group',
    to: '/admin/users',
    color: 'blue-darken-1'
  },
  {
    title: 'Comentarios',
    icon: 'mdi-comment-text-multiple',
    to: '/admin/reviews',
    color: 'purple-darken-1'
  },
  {
    title: 'Cupones',
    icon: 'mdi-ticket-percent',
    to: '/admin/coupons',
    color: 'pink-darken-1'
  }
];

const currentRouteName = computed(() => {
  const names = {
    'dashboard': 'Panel de Control',
    'products': 'Gestión de Productos',
    'products-create': 'Nuevo Producto',
    'products-edit': 'Editar Producto',
    'orders': 'Pedidos',
    'users': 'Usuarios',
    'reviews': 'Reseñas',
    'coupons': 'Gestión de Cupones'
  };
  const match = Object.keys(names).find(key => route.path.includes(key));
  return match ? names[match] : 'Administración';
});
</script>
