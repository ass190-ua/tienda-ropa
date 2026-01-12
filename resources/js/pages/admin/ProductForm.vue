<template>
  <v-container fluid>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" to="/admin/products" class="mr-2"></v-btn>
      <h1 class="text-h4 font-weight-bold">
        {{ isEditing ? 'Editar Producto' : 'Nuevo Producto' }}
      </h1>
      <v-spacer></v-spacer>
      <v-btn
        color="grey-darken-1"
        variant="text"
        class="mr-2"
        to="/admin/products"
      >
        Cancelar
      </v-btn>
      <v-btn
        color="orange-darken-2"
        prepend-icon="mdi-content-save"
        @click="saveProduct"
        :loading="saving"
      >
        Guardar Producto
      </v-btn>
    </div>

    <v-form ref="formRef" @submit.prevent="saveProduct">
      <v-row>
        <v-col cols="12" md="8">

          <v-card class="mb-6 rounded-lg" elevation="1">
            <v-card-title class="pa-4 font-weight-bold text-subtitle-1">
              Información Básica
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="pa-4">
              <v-text-field
                v-model="form.name"
                label="Nombre del Producto"
                variant="outlined"
                :rules="[v => !!v || 'El nombre es obligatorio']"
                placeholder="Ej: Camiseta Algodón Premium"
              ></v-text-field>

              <v-textarea
                v-model="form.description_short"
                label="Descripción Corta (Resumen)"
                variant="outlined"
                rows="2"
                hint="Se muestra en los listados"
                persistent-hint
                class="mb-4"
              ></v-textarea>

              <v-textarea
                v-model="form.description_long"
                label="Descripción Detallada"
                variant="outlined"
                rows="6"
                placeholder="Detalles del producto, materiales, cuidados..."
              ></v-textarea>
            </v-card-text>
          </v-card>

          <v-card class="mb-6 rounded-lg" elevation="1">
            <v-card-title class="pa-4 font-weight-bold text-subtitle-1">
              Precios y Inventario
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="pa-4">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.price"
                    label="Precio (€)"
                    type="number"
                    step="0.01"
                    variant="outlined"
                    prepend-inner-icon="mdi-currency-eur"
                    :rules="[v => !!v || 'El precio es obligatorio']"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                   <v-text-field
                    v-model="form.stock"
                    label="Stock Total"
                    type="number"
                    variant="outlined"
                    prepend-inner-icon="mdi-package-variant"
                    hint="Cantidad total disponible"
                  ></v-text-field>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

        </v-col>

        <v-col cols="12" md="4">

          <v-card class="mb-6 rounded-lg" elevation="1">
            <v-card-title class="pa-4 font-weight-bold text-subtitle-1">
              Imagen Principal
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="pa-4 text-center">

              <v-img
                :src="previewImage || '/placeholder-garment.jpg'"
                class="bg-grey-lighten-4 rounded mb-4 mx-auto"
                width="100%"
                max-height="250"
                cover
                aspect-ratio="1"
              >
                <template v-slot:placeholder>
                    <div class="d-flex align-center justify-center fill-height text-grey">
                        Sin imagen
                    </div>
                </template>
              </v-img>

              <v-file-input
                v-model="imageFile"
                label="Subir Imagen"
                accept="image/*"
                variant="outlined"
                density="compact"
                prepend-icon="mdi-camera"
                @update:model-value="handleImagePreview"
                :show-size="1000"
              ></v-file-input>
            </v-card-text>
          </v-card>

          <v-card class="rounded-lg" elevation="1">
            <v-card-title class="pa-4 font-weight-bold text-subtitle-1">
              Organización
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="pa-4">

              <v-select
                v-model="form.category_id"
                :items="categories"
                item-title="value"
                item-value="id"
                label="Categoría"
                variant="outlined"
                clearable
                prepend-inner-icon="mdi-shape"
              ></v-select>

              </v-card-text>
          </v-card>

        </v-col>
      </v-row>
    </v-form>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">
       {{ snackbar.message }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

// Estado
const isEditing = computed(() => route.params.id !== undefined);
const loading = ref(false);
const saving = ref(false);
const formRef = ref(null);

// Datos del formulario
const form = reactive({
    name: '',
    description_short: '',
    description_long: '',
    price: '',
    stock: 0,
    category_id: null,
});

// Imagen
const imageFile = ref(null);
const previewImage = ref(null);

// Desplegables
const categories = ref([]);

// Notificaciones
const snackbar = reactive({ show: false, message: '', color: 'success' });

// --- MÉTODOS ---

// 1. Cargar datos iniciales
const loadData = async () => {
    try {
        // Cargar opciones de desplegables (API que creamos paso 1)
        const attrResponse = await axios.get('/api/admin/products/form-data');
        // Filtramos para obtener solo las categorías (asumiendo que vienen todos los atributos)
        // Adaptar esto según cómo devuelva tu API los datos.
        // Si tu API devuelve array de atributos con sus valores:
        const catAttribute = attrResponse.data.find(a => a.name === 'category' || a.name === 'Categoría');
        if (catAttribute) {
            categories.value = catAttribute.values;
        } else {
            // Fallback si no encuentra estructura compleja, intenta ver si devuelve valores directos
             // O simplemente asigna todo si tu API devolvió solo categorías
             // categories.value = attrResponse.data;
        }

        // Si estamos editando, cargar producto
        if (isEditing.value) {
            const prodResponse = await axios.get(`/api/admin/products/${route.params.id}`);
            const p = prodResponse.data;

            form.name = p.name;
            form.description_short = p.description_short;
            form.description_long = p.description_long;
            form.price = p.price;
            form.stock = p.stock;
            form.category_id = p.category_id;

            if (p.image_url) {
                previewImage.value = p.image_url;
            }
        }
    } catch (e) {
        console.error(e);
        snackbar.message = "Error cargando datos";
        snackbar.color = "error";
        snackbar.show = true;
    }
};

// 2. Previsualizar Imagen al seleccionar
const handleImagePreview = (files) => {
    if (!files || !files.length) return; // En Vuetify 3 file-input devuelve array
    const file = files[0]; // Ojo: a veces devuelve el objeto directo dependiendo versión, verifica.
    // Corrección para Vuetify 3: v-model suele ser array
    const actualFile = Array.isArray(imageFile.value) ? imageFile.value[0] : imageFile.value;

    if (actualFile) {
        previewImage.value = URL.createObjectURL(actualFile);
    }
};

// 3. Guardar
const saveProduct = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    saving.value = true;

    // Usamos FormData para poder enviar ficheros
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('price', form.price);
    formData.append('stock', form.stock); // Backend debe manejar esto
    if(form.category_id) formData.append('category_id', form.category_id);
    if(form.description_short) formData.append('description_short', form.description_short);
    if(form.description_long) formData.append('description_long', form.description_long);

    // Imagen
    const actualFile = Array.isArray(imageFile.value) ? imageFile.value[0] : imageFile.value;
    if (actualFile) {
        formData.append('image_file', actualFile);
    }

    try {
        if (isEditing.value) {
            // EDITAR (POST a la ruta de update)
            await axios.post(`/api/admin/products/${route.params.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            snackbar.message = "Producto actualizado correctamente";
        } else {
            // CREAR
            await axios.post('/api/admin/products', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            snackbar.message = "Producto creado con éxito";
            router.push('/admin/products'); // Volver al listado
        }
        snackbar.show = true;

    } catch (error) {
        console.error(error);
        snackbar.message = error.response?.data?.message || "Error al guardar";
        snackbar.color = "error";
        snackbar.show = true;
    } finally {
        saving.value = false;
    }
};

onMounted(loadData);
</script>
