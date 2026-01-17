<template>
  <v-container fluid>
    <v-card elevation="2" class="rounded-lg">
      <v-card-title class="d-flex align-center py-4 px-6">
        <v-icon icon="mdi-ticket-percent" class="mr-3" color="pink-darken-1"></v-icon>
        <span class="text-h5 font-weight-bold">Gestión de Cupones</span>
        <v-spacer></v-spacer>
        <v-btn color="pink-darken-1" prepend-icon="mdi-plus" elevation="2" @click="openDialog()">
          Nuevo Cupón
        </v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-data-table
        :headers="headers"
        :items="coupons"
        :loading="loading"
        hover
        class="pa-2"
      >
        <template v-slot:item.is_active="{ item }">
          <v-switch
            v-model="item.is_active"
            color="success"
            hide-details
            density="compact"
            @update:model-value="toggleStatus(item)"
          ></v-switch>
        </template>

        <template v-slot:item.value="{ item }">
          <span v-if="item.discount_type === 'percent'" class="font-weight-bold text-blue">
             -{{ item.discount_value }}%
          </span>
          <span v-else class="font-weight-bold text-green">
             -{{ item.discount_value }}€
          </span>
        </template>

        <template v-slot:item.dates="{ item }">
           <div class="text-caption">
             <div v-if="item.start_date">Inicio: {{ formatDate(item.start_date) }}</div>
             <div v-if="item.end_date" class="text-red">Fin: {{ formatDate(item.end_date) }}</div>
             <div v-else class="text-green">Indefinido</div>
           </div>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="blue" @click="openDialog(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="red" @click="deleteCoupon(item)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="600px">
      <v-card class="rounded-xl">
        <v-card-title class="pa-4 bg-grey-lighten-4">
          <span class="text-h6">{{ editedIndex === -1 ? 'Crear Cupón' : 'Editar Cupón' }}</span>
        </v-card-title>

        <v-card-text class="pa-6">
          <v-form ref="formRef" @submit.prevent="save">
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="editedItem.code"
                  label="Código (Ej: VERANO2025)"
                  variant="outlined"
                  density="comfortable"
                  prepend-inner-icon="mdi-barcode"
                  :rules="[v => !!v || 'El código es obligatorio']"
                  class="mb-2"
                ></v-text-field>
              </v-col>

              <v-col cols="6">
                <v-select
                  v-model="editedItem.discount_type"
                  :items="typeOptions"
                  label="Tipo de Descuento"
                  variant="outlined"
                  density="comfortable"
                ></v-select>
              </v-col>

              <v-col cols="6">
                <v-text-field
                  v-model.number="editedItem.discount_value"
                  label="Valor (Euros o %)"
                  type="number"
                  variant="outlined"
                  density="comfortable"
                  :rules="[v => v > 0 || 'Debe ser mayor que 0']"
                ></v-text-field>
              </v-col>

              <v-col cols="12">
                <v-text-field
                  v-model.number="editedItem.min_order_total"
                  label="Gasto Mínimo (Opcional)"
                  prefix="€"
                  type="number"
                  variant="outlined"
                  density="comfortable"
                  hint="Deja 0 si no hay mínimo"
                  persistent-hint
                ></v-text-field>
              </v-col>

              <v-col cols="6">
                 <v-text-field
                    v-model="editedItem.start_date"
                    label="Fecha Inicio"
                    type="date"
                    variant="outlined"
                 ></v-text-field>
              </v-col>

              <v-col cols="6">
                 <v-text-field
                    v-model="editedItem.end_date"
                    label="Fecha Fin (Opcional)"
                    type="date"
                    variant="outlined"
                 ></v-text-field>
              </v-col>

              <v-col cols="12">
                <v-switch
                    v-model="editedItem.is_active"
                    label="Cupón Activo"
                    color="success"
                ></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" color="grey-darken-1" @click="close">Cancelar</v-btn>
          <v-btn color="pink-darken-1" variant="elevated" @click="save" :loading="saving">
            Guardar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar.show = false">Cerrar</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';

const coupons = ref([]);
const loading = ref(false);
const dialog = ref(false);
const saving = ref(false);
const formRef = ref(null);
const editedIndex = ref(-1);

const snackbar = reactive({ show: false, text: '', color: 'success' });

const headers = [
  { title: 'Código', key: 'code', align: 'start' },
  { title: 'Descuento', key: 'value', align: 'start' },
  { title: 'Mínimo', key: 'min_order_total', align: 'end' },
  { title: 'Vigencia', key: 'dates', align: 'start' },
  { title: 'Activo', key: 'is_active', align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
];

const typeOptions = [
    { title: 'Porcentaje (%)', value: 'percent' },
    { title: 'Fijo (€)', value: 'fixed' }
];

const defaultItem = {
  id: null,
  code: '',
  discount_type: 'percent',
  discount_value: 0,
  min_order_total: 0,
  start_date: null,
  end_date: null,
  is_active: true
};

const editedItem = ref({ ...defaultItem });

const loadCoupons = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/admin/coupons');
    coupons.value = response.data;
  } catch (error) {
    showMsg("Error cargando cupones", "error");
  } finally {
    loading.value = false;
  }
};

const openDialog = (item) => {
  if (item) {
    editedIndex.value = coupons.value.indexOf(item);
    // Clonamos y formateamos fechas si hace falta (YYYY-MM-DD)
    editedItem.value = { ...item };
    // Asegurar que las fechas cortas funcionen en input type="date"
    if(editedItem.value.start_date) editedItem.value.start_date = editedItem.value.start_date.split('T')[0];
    if(editedItem.value.end_date) editedItem.value.end_date = editedItem.value.end_date.split('T')[0];
  } else {
    editedIndex.value = -1;
    editedItem.value = { ...defaultItem };
  }
  dialog.value = true;
};

const close = () => {
  dialog.value = false;
  setTimeout(() => {
    editedItem.value = { ...defaultItem };
    editedIndex.value = -1;
  }, 300);
};

const save = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  saving.value = true;
  try {
    if (editedIndex.value > -1) {
      // EDITAR
      const res = await axios.put(`/api/admin/coupons/${editedItem.value.id}`, editedItem.value);
      Object.assign(coupons.value[editedIndex.value], res.data);
      showMsg("Cupón actualizado");
    } else {
      // CREAR
      const res = await axios.post('/api/admin/coupons', editedItem.value);
      coupons.value.unshift(res.data);
      showMsg("Cupón creado");
    }
    close();
  } catch (error) {
    showMsg(error.response?.data?.message || "Error al guardar", "error");
  } finally {
    saving.value = false;
  }
};

const toggleStatus = async (item) => {
    try {
        await axios.patch(`/api/admin/coupons/${item.id}/toggle`);
        showMsg(item.is_active ? "Cupón activado" : "Cupón desactivado");
    } catch (e) {
        item.is_active = !item.is_active; // Revertir visualmente si falla
        showMsg("Error al cambiar estado", "error");
    }
};

const deleteCoupon = async (item) => {
    if(!confirm('¿Seguro que quieres borrar este cupón?')) return;
    try {
        await axios.delete(`/api/admin/coupons/${item.id}`);
        coupons.value = coupons.value.filter(c => c.id !== item.id);
        showMsg("Cupón eliminado");
    } catch(e) {
        showMsg("Error al eliminar", "error");
    }
};

const showMsg = (text, color = 'success') => {
  snackbar.text = text;
  snackbar.color = color;
  snackbar.show = true;
};

const formatDate = (dateStr) => {
    if(!dateStr) return '';
    return new Date(dateStr).toLocaleDateString();
};

onMounted(() => {
  loadCoupons();
});
</script>
