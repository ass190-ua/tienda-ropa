<template>
  <v-container fluid class="pa-6">
    <div class="d-flex justify-space-between align-center mb-6">
      <h1 class="text-h4 font-weight-bold text-grey-darken-3">Comentarios</h1>
      <v-btn icon variant="text" color="grey-darken-1" @click="loadReviews">
        <v-icon>mdi-refresh</v-icon>
      </v-btn>
    </div>

    <v-card class="elevation-2 rounded-lg border-0">
      <v-data-table-server
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="reviews"
        :items-length="totalReviews"
        :loading="loading"
        item-value="id"
        @update:options="loadReviews"
        hover
        class="rounded-lg"
      >
        <template v-slot:item.product="{ item }">
          <div class="d-flex align-center py-2" v-if="item.product">
            <v-avatar rounded size="40" class="mr-3 bg-grey-lighten-4">
              <v-img
                :src="getProductImage(item.product)"
                cover
                error-icon="mdi-image-off"
              ></v-img>
            </v-avatar>
            <div class="d-flex flex-column">
              <span class="font-weight-medium text-body-2">{{ item.product.name }}</span>
              <span class="text-caption text-grey">ID: {{ item.product.id }}</span>
            </div>
          </div>
          <span v-else class="text-grey font-italic">Producto eliminado</span>
        </template>

        <template v-slot:item.user="{ item }">
           <div class="d-flex align-center">
             <v-avatar color="blue-lighten-5" size="28" class="mr-2">
               <span class="text-caption font-weight-bold text-blue-darken-2">
                 {{ item.user?.name?.charAt(0).toUpperCase() || 'U' }}
               </span>
             </v-avatar>
             <span class="text-body-2">{{ item.user?.name || 'Anónimo' }}</span>
           </div>
        </template>

        <template v-slot:item.rating="{ item }">
          <v-rating
            :model-value="item.rating"
            color="amber-darken-2"
            density="compact"
            size="small"
            readonly
            half-increments
          ></v-rating>
        </template>

        <template v-slot:item.comment="{ item }">
           <div style="max-width: 300px;" class="text-truncate">
             {{ item.comment || 'Sin texto' }}
           </div>
        </template>

        <template v-slot:item.status="{ item }">
           <v-chip
             v-if="item.status === 'approved'"
             color="green"
             size="small"
             variant="flat"
             class="font-weight-bold"
           >
             Aprobado
           </v-chip>
           <v-chip
             v-else
             color="orange"
             size="small"
             variant="flat"
             class="font-weight-bold"
           >
             Pendiente
           </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <div class="d-flex">
            <v-btn
              v-if="item.status !== 'approved'"
              icon
              variant="text"
              size="small"
              color="green-darken-1"
              @click="approveReview(item)"
              :loading="actionLoading === item.id"
            >
              <v-icon>mdi-check-circle-outline</v-icon>
              <v-tooltip activator="parent" location="top">Aprobar</v-tooltip>
            </v-btn>

            <v-btn
              icon
              variant="text"
              size="small"
              color="red-lighten-1"
              @click="deleteReview(item)"
              :loading="actionLoading === item.id"
            >
              <v-icon>mdi-delete-outline</v-icon>
              <v-tooltip activator="parent" location="top">Eliminar</v-tooltip>
            </v-btn>
          </div>
        </template>
      </v-data-table-server>
    </v-card>

    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card title="¿Eliminar comentario?" text="Esta acción no se puede deshacer.">
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text="Cancelar" @click="deleteDialog = false"></v-btn>
          <v-btn color="error" text="Eliminar" @click="confirmDelete"></v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const reviews = ref([]);
const totalReviews = ref(0);
const loading = ref(false);
const itemsPerPage = ref(10);
const actionLoading = ref(null); // ID de la review que se está procesando

// Para el borrado
const deleteDialog = ref(false);
const reviewToDelete = ref(null);

const headers = [
  { title: 'Producto', key: 'product', sortable: false },
  { title: 'Usuario', key: 'user', sortable: false },
  { title: 'Valoración', key: 'rating', align: 'center' },
  { title: 'Comentario', key: 'comment', sortable: false },
  { title: 'Estado', key: 'status', align: 'center' },
  { title: 'Acciones', key: 'actions', align: 'end', sortable: false },
];

// Cargar reseñas
const loadReviews = async ({ page, itemsPerPage } = {}) => {
  loading.value = true;
  try {
    const pageNumber = page || 1;
    const response = await axios.get(`/api/admin/reviews?page=${pageNumber}`);

    reviews.value = response.data.data;
    totalReviews.value = response.data.total;
  } catch (error) {
    console.error("Error cargando reviews:", error);
  } finally {
    loading.value = false;
  }
};

// Aprobar reseña
const approveReview = async (item) => {
  actionLoading.value = item.id;
  try {
    await axios.patch(`/api/admin/reviews/${item.id}/approve`);
    // Actualizamos localmente para que se vea rápido
    item.status = 'approved';
  } catch (error) {
    console.error("Error aprobando review:", error);
    alert("Error al aprobar");
  } finally {
    actionLoading.value = null;
  }
};

// Borrar reseña
const deleteReview = (item) => {
  reviewToDelete.value = item;
  deleteDialog.value = true;
};

const confirmDelete = async () => {
  if (!reviewToDelete.value) return;

  const id = reviewToDelete.value.id;
  deleteDialog.value = false;
  actionLoading.value = id;

  try {
    await axios.delete(`/api/admin/reviews/${id}`);
    // Recargamos la tabla
    loadReviews();
  } catch (error) {
    console.error("Error eliminando review:", error);
    alert("Error al eliminar");
  } finally {
    actionLoading.value = null;
    reviewToDelete.value = null;
  }
};

// Helper para imagen
const getProductImage = (product) => {
  // Ajusta esto según cómo guardes tus imágenes
  if (product.image) return `/storage/${product.image}`;
  // Si usas una relación de imágenes:
  // if (product.images && product.images.length > 0) return `/storage/${product.images[0].path}`;
  return '/placeholder-image.jpg'; // Imagen por defecto
};

onMounted(() => {
  // loadReviews(); // El data-table-server lo llama automáticamente al iniciar
});
</script>
