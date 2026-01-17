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
const categories = ref([]);

const snackbar = reactive({ show: false, message: '', color: 'success' });

// --- FUNCIÓN INTELIGENTE PARA IMÁGENES ---
const getImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http')) return path;

    // Si la ruta contiene "images/", asumimos que es de las antiguas (carpeta public)
    if (path.includes('images/')) {
        return path.startsWith('/') ? path : `/${path}`;
    }

    // Si no, asumimos que es una subida nueva de Laravel (carpeta storage)
    // Aseguramos que tenga /storage/ delante
    if (path.startsWith('/storage')) return path;
    return `/storage/${path}`;
};

// --- MÉTODOS ---

const loadData = async () => {
    try {
        // Cargar categorías
        const attrResponse = await axios.get('/api/admin/products/form-data');
        // Ajuste defensivo por si la API cambia de estructura
        if (Array.isArray(attrResponse.data)) {
             const catObj = attrResponse.data.find(a => a.name === 'category' || a.name === 'Categoría');
             categories.value = catObj ? catObj.values : attrResponse.data;
        } else {
             categories.value = attrResponse.data;
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

            // [CORRECCIÓN] Usamos la función inteligente para mostrar la imagen existente
            if (p.image_url || p.image) {
                previewImage.value = getImageUrl(p.image_url || p.image);
            }
        }
    } catch (e) {
        console.error(e);
        snackbar.message = "Error cargando datos";
        snackbar.color = "error";
        snackbar.show = true;
    }
};

const handleImagePreview = (fileInputInfo) => {
    // fileInputInfo puede ser el evento o el array de archivos directamente
    // Aseguramos obtener el primer archivo real
    const file = Array.isArray(fileInputInfo) ? fileInputInfo[0] : fileInputInfo;

    if (file && file instanceof File) {
        previewImage.value = URL.createObjectURL(file);
        imageFile.value = [file]; // Forzamos que el modelo sea un array para Vuetify
    } else {
        // Si no hay fichero válido (ej. usuario cancela), no hacemos nada o limpiamos
        // previewImage.value = null;
    }
};

const saveProduct = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    saving.value = true;
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('price', form.price);
    formData.append('stock', form.stock);
    if(form.category_id) formData.append('category_id', form.category_id);
    if(form.description_short) formData.append('description_short', form.description_short);
    if(form.description_long) formData.append('description_long', form.description_long);

    // Detectamos si imageFile es un array o un archivo suelto
    const rawFile = imageFile.value;
    const fileToUpload = Array.isArray(rawFile) ? rawFile[0] : rawFile;

    // Solo lo añadimos si realmente es un archivo válido
    if (fileToUpload instanceof File) {
        formData.append('image_file', fileToUpload);
    }

    try {
        const url = isEditing.value
            ? `/api/admin/products/${route.params.id}`
            : '/api/admin/products';

        // Axios pondrá el Content-Type correcto automáticamente
        await axios.post(url, formData);

        snackbar.message = isEditing.value ? "Producto actualizado" : "Producto creado";
        snackbar.show = true;
        setTimeout(() => router.push('/admin/products'), 1000);

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
