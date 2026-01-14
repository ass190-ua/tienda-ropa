<template>
  <v-container fluid class="pa-6">
    <div class="d-flex justify-space-between align-center mb-6">
      <h1 class="text-h4 font-weight-bold text-grey-darken-3">Pedidos</h1>
      <v-btn icon variant="text" color="grey-darken-1" @click="loadOrders">
        <v-icon>mdi-refresh</v-icon>
      </v-btn>
    </div>

    <v-card class="elevation-2 rounded-lg border-0">
      <v-data-table-server
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="orders"
        :items-length="totalOrders"
        :loading="loading"
        :search="search"
        item-value="id"
        @update:options="loadOrders"
        hover
        class="rounded-lg"
      >
        <template v-slot:top>
          <v-card-title class="d-flex align-center flex-wrap py-4 px-4 gap-4 bg-white">
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Buscar (ID, Cliente, Email)..."
              single-line
              hide-details
              density="compact"
              variant="outlined"
              style="max-width: 300px; min-width: 200px;"
              class="mr-2 my-1"
            ></v-text-field>

            <v-spacer class="d-none d-md-flex"></v-spacer>

            <v-select
              v-model="filters.status"
              :items="statusOptions"
              label="Estado"
              hide-details
              density="compact"
              variant="outlined"
              style="max-width: 180px; min-width: 150px;"
              class="mr-2 my-1"
              @update:model-value="loadOrders"
              clearable
            ></v-select>

            <v-select
              v-model="filters.date"
              :items="dateOptions"
              label="Fecha"
              hide-details
              density="compact"
              variant="outlined"
              style="max-width: 180px;"
              class="mr-2 my-1"
              @update:model-value="loadOrders"
            ></v-select>

            <v-btn
              color="green-darken-1"
              variant="tonal"
              prepend-icon="mdi-microsoft-excel"
              @click="exportOrders"
              class="my-1"
            >
              Exportar
            </v-btn>
          </v-card-title>
        </template>

        <template v-slot:item.id="{ item }">
          <span class="font-weight-bold text-grey-darken-3">#{{ item.id }}</span>
        </template>

        <template v-slot:item.user="{ item }">
           <div class="d-flex align-center py-2">
             <v-avatar color="grey-lighten-3" size="32" class="mr-3">
               <span class="text-caption font-weight-bold text-grey-darken-3">
                 {{ item.user?.name?.charAt(0).toUpperCase() || 'U' }}
               </span>
             </v-avatar>
             <div>
               <div class="font-weight-medium text-body-2">{{ item.user?.name || 'Invitado' }}</div>
               <div class="text-caption text-grey">{{ item.user?.email }}</div>
             </div>
           </div>
        </template>

        <template v-slot:item.total_amount="{ item }">
          <span class="font-weight-bold text-green-darken-1">
             {{ formatCurrency(item.subtotal || item.total_amount || 0) }}
          </span>
        </template>

        <template v-slot:item.status="{ item }">
          <v-chip
            :color="getStatusColor(item.status)"
            size="small"
            class="font-weight-bold text-capitalize"
            variant="flat"
          >
            {{ translateStatus(item.status) }}
          </v-chip>
        </template>

        <template v-slot:item.created_at="{ item }">
          <span class="text-body-2 text-grey-darken-1">
            {{ new Date(item.created_at).toLocaleDateString() }}
          </span>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="blue-darken-1" @click="openDetails(item)">
            <v-icon>mdi-eye-outline</v-icon>
            <v-tooltip activator="parent" location="top">Ver Detalles</v-tooltip>
          </v-btn>
        </template>
      </v-data-table-server>
    </v-card>

    <v-dialog v-model="detailsDialog" max-width="900px">
      <v-card v-if="selectedOrder" class="rounded-xl">
        <v-card-title class="d-flex justify-space-between align-center pa-6 bg-grey-lighten-5">
          <span class="text-h6 font-weight-bold">Pedido #{{ selectedOrder.id }}</span>
          <v-btn icon variant="text" @click="detailsDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider></v-divider>

        <v-card-text class="pa-6">
          <v-row>
            <v-col cols="12" md="4">
              <h3 class="text-subtitle-2 font-weight-bold text-uppercase text-grey mb-3">Cliente</h3>
              <div class="d-flex align-center mb-4">
                 <v-avatar color="blue-lighten-5" size="48" class="mr-3">
                   <span class="text-h6 text-blue-darken-2">
                     {{ selectedOrder.user?.name?.charAt(0).toUpperCase() || '?' }}
                   </span>
                 </v-avatar>
                 <div>
                   <div class="font-weight-bold">{{ selectedOrder.user?.name || 'Invitado' }}</div>
                   <div class="text-caption text-grey">{{ selectedOrder.user?.email }}</div>
                 </div>
              </div>

              <h3 class="text-subtitle-2 font-weight-bold text-uppercase text-grey mb-3 mt-6">Envío</h3>
              <p class="text-body-2" v-if="selectedOrder.address">
                 {{ selectedOrder.address.address_line_1 }}<br>
                 {{ selectedOrder.address.city }}, {{ selectedOrder.address.zip_code }}<br>
                 {{ selectedOrder.address.country || 'España' }}
              </p>
              <p v-else class="text-caption text-grey font-italic">Sin dirección registrada</p>

              <div class="mt-8 pa-4 bg-grey-lighten-5 rounded-lg border">
                <label class="text-caption font-weight-bold mb-2 d-block">Actualizar Estado</label>
                <v-select
                  v-model="selectedOrder.status"
                  :items="['pending', 'paid', 'shipped', 'delivered', 'cancelled']"
                  density="compact"
                  variant="outlined"
                  hide-details
                  bg-color="white"
                  @update:model-value="updateStatus"
                ></v-select>
              </div>
            </v-col>

            <v-col cols="12" md="8">
               <h3 class="text-subtitle-2 font-weight-bold text-uppercase text-grey mb-3">Productos</h3>

               <v-table density="comfortable" class="border rounded-lg">
                 <thead>
                   <tr>
                     <th width="60">Imagen</th> <th>Producto</th>
                     <th class="text-center">Cant.</th>
                     <th class="text-end">Total</th>
                   </tr>
                 </thead>
                 <tbody>
                   <tr v-for="line in selectedOrder.lines" :key="line.id">
                     <td class="py-2">
                        <v-avatar rounded size="48" class="bg-grey-lighten-4">
                            <v-img
                                :src="getProductImage(line.product)"
                                cover
                                error-icon="mdi-image-off"
                            ></v-img>
                        </v-avatar>
                     </td>
                     <td class="py-2">
                       <div class="font-weight-medium">{{ line.product?.name || 'Producto borrado' }}</div>
                       <div class="text-caption text-grey">{{ formatCurrency(line.unit_price) }} / ud.</div>
                     </td>
                     <td class="text-center">{{ line.quantity }}</td>
                     <td class="text-end font-weight-bold">{{ formatCurrency(line.line_total) }}</td>
                   </tr>
                 </tbody>
               </v-table>

               <div class="d-flex justify-end mt-4">
                 <div class="text-right">
                   <div class="text-h5 font-weight-bold text-green-darken-2">
                     {{ formatCurrency(selectedOrder.subtotal || selectedOrder.total_amount) }}
                   </div>
                   <div class="text-caption text-grey">Total Pagado</div>
                 </div>
               </div>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

// Datos de la tabla
const orders = ref([]);
const totalOrders = ref(0);
const loading = ref(false);
const itemsPerPage = ref(10);
const search = ref('');

const filters = ref({
  status: null,
  date: 'all'
});

const statusOptions = [
  { title: 'Todos', value: null },
  { title: 'Pendiente', value: 'pending' },
  { title: 'Pagado', value: 'paid' },
  { title: 'Enviado', value: 'shipped' },
  { title: 'Cancelado', value: 'cancelled' },
];

const dateOptions = [
  { title: 'Cualquier fecha', value: 'all' },
  { title: 'Hoy', value: 'today' },
  { title: 'Última semana', value: 'week' },
  { title: 'Último mes', value: 'month' },
];

const headers = [
  { title: 'ID', key: 'id', align: 'start' },
  { title: 'Usuario', key: 'user', sortable: false },
  { title: 'Fecha', key: 'created_at' },
  { title: 'Estado', key: 'status' },
  { title: 'Total', key: 'total_amount', align: 'end' },
  { title: '', key: 'actions', sortable: false, align: 'end' },
];

const detailsDialog = ref(false);
const selectedOrder = ref(null);

const loadOrders = async ({ page, itemsPerPage, sortBy } = {}) => {
  loading.value = true;
  try {
    const pageNumber = page || 1;
    const response = await axios.get('/api/admin/orders', {
        params: {
            page: pageNumber,
            search: search.value,
            status: filters.value.status,
            date: filters.value.date
        }
    });
    orders.value = response.data.data;
    totalOrders.value = response.data.total;
  } catch (error) {
    console.error("Error cargando pedidos:", error);
  } finally {
    loading.value = false;
  }
};

const openDetails = async (item) => {
    selectedOrder.value = item;
    detailsDialog.value = true;

    try {
        const response = await axios.get(`/api/admin/orders/${item.id}`);
        selectedOrder.value = response.data;
    } catch (e) {
        console.error("Error cargando detalle", e);
    }
};

const updateStatus = async (newStatus) => {
    if (!selectedOrder.value) return;
    try {
        await axios.put(`/api/admin/orders/${selectedOrder.value.id}`, {
            status: newStatus
        });
        loadOrders();
    } catch (error) {
        console.error("Error actualizando estado", error);
        alert("No se pudo actualizar el estado");
    }
};

const exportOrders = () => {
  let url = '/api/admin/orders/export?';
  const params = new URLSearchParams();
  if (search.value) params.append('search', search.value);
  if (filters.value.status) params.append('status', filters.value.status);
  if (filters.value.date) params.append('date', filters.value.date);
  window.open(url + params.toString(), '_blank');
};

// NUEVO: Helper para recuperar imagen
const getProductImage = (product) => {
    if (!product || !product.images || product.images.length === 0) {
        return '/placeholder-image.jpg'; // Imagen por defecto si no hay
    }
    const path = product.images[0].path;
    // Si la ruta ya es completa (http) la usamos, si no, le pegamos /storage/
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const getStatusColor = (status) => {
  const map = {
    pending: 'orange-darken-1',
    paid: 'green-darken-1',
    shipped: 'blue-darken-1',
    delivered: 'purple-darken-1',
    cancelled: 'red-darken-1'
  };
  return map[status] || 'grey';
};

const translateStatus = (status) => {
  const map = {
    pending: 'Pendiente',
    paid: 'Pagado',
    shipped: 'Enviado',
    delivered: 'Entregado',
    cancelled: 'Cancelado'
  };
  return map[status] || status;
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
};

onMounted(() => {
    // La tabla carga datos automáticamente
});
</script>
