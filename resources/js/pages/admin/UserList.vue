<template>
  <v-container fluid>
    <v-card elevation="2" class="rounded-lg">
      <v-card-title class="d-flex align-center py-4 px-6">
        <v-icon icon="mdi-account-group" class="mr-3" color="primary"></v-icon>
        <span class="text-h5 font-weight-bold">Gestión de Usuarios</span>
        <v-spacer></v-spacer>

        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          label="Buscar usuario..."
          single-line
          hide-details
          variant="outlined"
          density="compact"
          class="mr-4"
          style="max-width: 300px"
          bg-color="white"
        ></v-text-field>

        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          elevation="2"
          @click="openDialog()"
        >
          Nuevo Usuario
        </v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-data-table
        :headers="headers"
        :items="users"
        :search="search"
        :loading="loading"
        hover
        class="pa-2"
      >
        <template v-slot:item.name="{ item }">
          <div class="d-flex align-center py-2">
            <v-avatar color="primary" variant="tonal" size="40" class="mr-3">
              <span class="text-subtitle-1 font-weight-bold">
                {{ item.name ? item.name.charAt(0).toUpperCase() : '?' }}
              </span>
            </v-avatar>
            <div>
              <div class="font-weight-bold text-body-1">{{ item.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.email }}</div>
            </div>
          </div>
        </template>

        <template v-slot:item.role="{ item }">
            <v-chip
              :color="item.is_admin ? 'purple-darken-1' : 'blue-darken-1'"
              size="small"
              class="font-weight-medium"
              variant="flat"
            >
              <v-icon start size="small">{{ item.is_admin ? 'mdi-shield-crown' : 'mdi-account' }}</v-icon>
              {{ item.is_admin ? 'Admin' : 'Cliente' }}
            </v-chip>
        </template>

        <template v-slot:item.status="{ item }">
            <v-chip
              :color="item.is_active ? 'success' : 'error'"
              size="small"
              variant="tonal"
              class="font-weight-bold"
            >
              <v-icon start size="small" :icon="item.is_active ? 'mdi-check-circle' : 'mdi-cancel'"></v-icon>
              {{ item.is_active ? 'Activo' : 'Bloqueado' }}
            </v-chip>
        </template>

        <template v-slot:item.created_at="{ item }">
           {{ formatDate(item.created_at) }}
        </template>

        <template v-slot:item.actions="{ item }">
          <div class="d-flex justify-end">
             <v-btn icon size="small" variant="text" color="blue" class="mr-1" @click="openDialog(item)">
              <v-icon>mdi-pencil</v-icon>
              <v-tooltip activator="parent" location="top">Editar</v-tooltip>
            </v-btn>

            <v-btn
                icon
                size="small"
                variant="text"
                :color="item.is_active ? 'warning' : 'success'"
                @click="toggleBan(item)"
            >
              <v-icon>{{ item.is_active ? 'mdi-lock' : 'mdi-lock-open-variant' }}</v-icon>
              <v-tooltip activator="parent" location="top">
                  {{ item.is_active ? 'Bloquear acceso' : 'Reactivar usuario' }}
              </v-tooltip>
            </v-btn>

            <v-btn icon size="small" variant="text" color="error" @click="openDeleteDialog(item)">
              <v-icon>mdi-delete</v-icon>
              <v-tooltip activator="parent" location="top">Eliminar</v-tooltip>
            </v-btn>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="500px">
      <v-card rounded="lg">
        <v-card-title class="text-h5 pa-4 bg-primary text-white">
          <v-icon class="mr-2">{{ isEditing ? 'mdi-account-edit' : 'mdi-account-plus' }}</v-icon>
          {{ isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}
        </v-card-title>

        <v-card-text class="pt-4">
          <v-form ref="formRef" @submit.prevent="saveUser">
            <v-text-field
              v-model="editedItem.name"
              label="Nombre Completo"
              variant="outlined"
              prepend-inner-icon="mdi-account"
              :rules="[v => !!v || 'El nombre es obligatorio']"
            ></v-text-field>

            <v-text-field
              v-model="editedItem.email"
              label="Correo Electrónico"
              variant="outlined"
              prepend-inner-icon="mdi-email"
              type="email"
              :rules="[v => !!v || 'El email es obligatorio', v => /.+@.+\..+/.test(v) || 'Email inválido']"
            ></v-text-field>

            <v-text-field
              v-model="editedItem.password"
              :label="isEditing ? 'Nueva Contraseña (Opcional)' : 'Contraseña'"
              variant="outlined"
              prepend-inner-icon="mdi-key"
              type="password"
              :hint="isEditing ? 'Déjalo en blanco para mantener la actual' : 'Mínimo 8 caracteres'"
              :rules="passwordRules"
            ></v-text-field>

            <v-checkbox
                v-model="editedItem.is_admin"
                label="Otorgar permisos de Administrador"
                color="purple"
                density="compact"
                hide-details
            ></v-checkbox>
          </v-form>
        </v-card-text>

        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" variant="elevated" @click="saveUser" :loading="saving">
            {{ isEditing ? 'Actualizar' : 'Guardar' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogDelete" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">¿Eliminar usuario?</v-card-title>
        <v-card-text>
          Estás a punto de eliminar a <strong>{{ itemToDelete?.name }}</strong>. Esta acción no se puede deshacer.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="dialogDelete = false">Cancelar</v-btn>
          <v-btn color="error" variant="elevated" @click="deleteUserConfirm">Eliminar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000" location="bottom right">
      <div class="d-flex align-center">
        <v-icon :icon="snackbar.icon" class="mr-2"></v-icon> {{ snackbar.message }}
      </div>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

// --- ESTADO ---
const search = ref('');
const loading = ref(false);
const users = ref([]);
const saving = ref(false);

// Modal Crear/Editar
const dialog = ref(false);
const editedId = ref(null); // Si es null => Creando, Si tiene ID => Editando
const formRef = ref(null);
const editedItem = reactive({
    name: '',
    email: '',
    password: '',
    is_admin: false
});

// Modal Borrar
const dialogDelete = ref(false);
const itemToDelete = ref(null);

// Snackbar
const snackbar = reactive({ show: false, message: '', color: 'success', icon: 'mdi-check' });

const isEditing = computed(() => !!editedId.value);

// Reglas dinámicas para contraseña (obligatoria al crear, opcional al editar)
const passwordRules = computed(() => {
    const rules = [];
    if (!isEditing.value) {
        rules.push(v => !!v || 'La contraseña es obligatoria');
    }
    if (editedItem.password) {
        rules.push(v => v.length >= 8 || 'Mínimo 8 caracteres');
    }
    return rules;
});

const headers = [
  { title: 'Usuario', key: 'name', align: 'start' },
  { title: 'Rol', key: 'role', align: 'center' },
  { title: 'Estado', key: 'status', align: 'center' },
  { title: 'Registro', key: 'created_at', align: 'end' },
  { title: 'Acciones', key: 'actions', align: 'end', sortable: false },
];

const showMessage = (msg, type = 'success') => {
    snackbar.message = msg;
    snackbar.color = type;
    snackbar.icon = type === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle';
    snackbar.show = true;
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('es-ES') : '-';

const fetchUsers = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/admin/users');
        users.value = response.data;
    } catch (e) { showMessage('Error cargando usuarios', 'error'); }
    finally { loading.value = false; }
};

// --- ABRIR MODAL (Crear o Editar) ---
const openDialog = (item = null) => {
    if (item) {
        // Modo Edición: Copiamos datos
        editedId.value = item.id;
        editedItem.name = item.name;
        editedItem.email = item.email;
        editedItem.is_admin = !!item.is_admin; // asegurar booleano
        editedItem.password = ''; // Limpiamos password (no la mostramos)
    } else {
        // Modo Creación: Limpiamos todo
        editedId.value = null;
        editedItem.name = '';
        editedItem.email = '';
        editedItem.is_admin = false;
        editedItem.password = '';
    }
    dialog.value = true;
};

// --- GUARDAR (Crear o Actualizar) ---
const saveUser = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    saving.value = true;
    try {
        if (isEditing.value) {
            // ACTUALIZAR (PUT)
            const response = await axios.put(`/api/admin/users/${editedId.value}`, editedItem);
            // Actualizamos la lista localmente
            const index = users.value.findIndex(u => u.id === editedId.value);
            if (index !== -1) users.value[index] = response.data;
            showMessage('Usuario actualizado correctamente');
        } else {
            // CREAR (POST)
            const response = await axios.post('/api/admin/users', editedItem);
            users.value.unshift(response.data);
            showMessage('Usuario creado correctamente');
        }
        dialog.value = false;
    } catch (error) {
        const msg = error.response?.data?.message || 'Error al guardar';
        showMessage(msg, 'error');
    } finally {
        saving.value = false;
    }
};

// --- BORRAR (Con modal bonito) ---
const openDeleteDialog = (item) => {
    itemToDelete.value = item;
    dialogDelete.value = true;
};

const deleteUserConfirm = async () => {
    if (!itemToDelete.value) return;
    try {
        await axios.delete(`/api/admin/users/${itemToDelete.value.id}`);
        users.value = users.value.filter(u => u.id !== itemToDelete.value.id);
        showMessage('Usuario eliminado');
    } catch (e) {
        showMessage('Error al eliminar', 'error');
    } finally {
        dialogDelete.value = false;
        itemToDelete.value = null;
    }
};

// --- BLOQUEAR ---
const toggleBan = async (user) => {
    try {
        await axios.patch(`/api/admin/users/${user.id}/toggle-active`);
        user.is_active = !user.is_active;
        showMessage(user.is_active ? 'Usuario activado' : 'Usuario bloqueado');
    } catch (e) { showMessage('Error al cambiar estado', 'error'); }
};

onMounted(fetchUsers);
</script>
