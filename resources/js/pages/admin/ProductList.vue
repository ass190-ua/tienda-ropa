<template>
  <v-container fluid>
    <v-card elevation="2" class="rounded-lg">
      <v-card-title class="d-flex align-center py-4 px-6">
        <v-icon icon="mdi-tshirt-crew" class="mr-3" color="orange-darken-2"></v-icon>
        <span class="text-h5 font-weight-bold">Catálogo de Productos</span>
        <v-spacer></v-spacer>

        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          label="Buscar por nombre, SKU o categoría..."
          single-line
          hide-details
          variant="outlined"
          density="compact"
          class="mr-4"
          style="max-width: 350px"
          bg-color="white"
        ></v-text-field>

        <v-btn
          color="orange-darken-2"
          prepend-icon="mdi-plus"
          elevation="2"
          to="/admin/products/create"
        >
          Nuevo Producto
        </v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-data-table
        :headers="headers"
        :items="products"
        :search="search"
        :loading="loading"
        hover
        class="pa-2"
      >
        <template v-slot:item.image="{ item }">
          <div class="py-2">
             <v-img
                :src="item.image || '/placeholder-garment.jpg'"
                aspect-ratio="1"
                cover
                width="60"
                height="60"
                class="rounded bg-grey-lighten-3"
             >
                <template v-slot:placeholder>
                    <div class="d-flex align-center justify-center fill-height">
                        <v-icon color="grey">mdi-image-off</v-icon>
                    </div>
                </template>
             </v-img>
          </div>
        </template>

        <template v-slot:item.name="{ item }">
          <div>
            <div class="font-weight-bold text-body-1">{{ item.name }}</div>
            <v-chip size="x-small" label class="mt-1 text-caption" color="grey-darken-1">
                {{ item.category }}
            </v-chip>
          </div>
        </template>

        <template v-slot:item.price="{ item }">
            <span class="font-weight-bold text-body-1">
                {{ parseFloat(item.price).toFixed(2) }} €
            </span>
        </template>

        <template v-slot:item.stock_total="{ item }">
            <v-chip
              :color="getStockColor(item.stock_total)"
              size="small"
              variant="flat"
              class="font-weight-bold text-white"
            >
              {{ item.stock_total }} un.
            </v-chip>
            <div v-if="item.stock_total === 0" class="text-caption text-error mt-1">
                Agotado
            </div>
        </template>

        <template v-slot:item.actions="{ item }">
          <div class="d-flex justify-end">
             <v-btn icon size="small" variant="text" color="blue" class="mr-1" :to="`/admin/products/${item.id}/edit`">
              <v-icon color="orange-darken-1">mdi-pencil</v-icon>
              <v-tooltip activator="parent" location="top">Editar Producto</v-tooltip>
            </v-btn>

            <v-btn icon size="small" variant="text" color="error" @click="openDeleteDialog(item)">
              <v-icon>mdi-delete</v-icon>
              <v-tooltip activator="parent" location="top">Eliminar</v-tooltip>
            </v-btn>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="dialogDelete" max-width="400px">
      <v-card>
        <v-card-title class="text-h5 pa-4 bg-error text-white">
            ¿Eliminar producto?
        </v-card-title>
        <v-card-text class="pt-4">
          Estás a punto de eliminar <strong>{{ itemToDelete?.name }}</strong>.
          <br><br>
          <span class="text-caption text-grey-darken-1">
            ⚠️ Esto eliminará también todas sus variantes de stock e imágenes asociadas.
          </span>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="error" variant="elevated" @click="deleteProductConfirm" :loading="deleting">
            Eliminar Definitivamente
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="bottom right">
       <v-icon :icon="snackbar.icon" class="mr-2"></v-icon> {{ snackbar.message }}
    </v-snackbar>

  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

// ESTADO
const search = ref('');
const loading = ref(false);
const products = ref([]);
const deleting = ref(false);

// Borrado
const dialogDelete = ref(false);
const itemToDelete = ref(null);

// Notificaciones
const snackbar = reactive({ show: false, message: '', color: 'success', icon: 'mdi-check' });

// Configuración Tabla
const headers = [
  { title: 'Imagen', key: 'image', align: 'center', sortable: false, width: '80px' },
  { title: 'Producto', key: 'name', align: 'start' },
  { title: 'Referencia (SKU)', key: 'sku', align: 'start' },
  { title: 'Precio', key: 'price', align: 'end' },
  { title: 'Stock Total', key: 'stock_total', align: 'center' },
  { title: 'Acciones', key: 'actions', align: 'end', sortable: false },
];

// --- FUNCIONES ---

const showMessage = (msg, type = 'success') => {
    snackbar.message = msg;
    snackbar.color = type;
    snackbar.icon = type === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle';
    snackbar.show = true;
};

// Determinar color según stock
const getStockColor = (stock) => {
    if (stock === 0) return 'error';      // Rojo
    if (stock < 10) return 'warning';     // Naranja
    return 'success';                     // Verde
};

const fetchProducts = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/admin/products');
        products.value = response.data;
    } catch (e) {
        showMessage('Error al cargar el catálogo', 'error');
    } finally {
        loading.value = false;
    }
};

// Borrado
const openDeleteDialog = (item) => {
    itemToDelete.value = item;
    dialogDelete.value = true;
};

const deleteProductConfirm = async () => {
    if (!itemToDelete.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/api/admin/products/${itemToDelete.value.id}`);
        // Eliminar de la lista local
        products.value = products.value.filter(p => p.id !== itemToDelete.value.id);
        showMessage('Producto eliminado correctamente');
    } catch (e) {
        showMessage('Error al eliminar producto', 'error');
    } finally {
        dialogDelete.value = false;
        deleting.value = false;
        itemToDelete.value = null;
    }
};

onMounted(fetchProducts);
</script>
