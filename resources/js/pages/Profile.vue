<template>
    <v-container class="py-8 px-4">
        <v-row justify="center">
            <v-col cols="12" md="7" lg="6">
                <v-card rounded="xl" elevation="2" class="pa-6">
                    <div class="d-flex align-center justify-space-between mb-4">
                        <div>
                            <div class="text-h5 font-weight-bold">Mi perfil</div>
                            <div class="text-body-2 text-medium-emphasis">
                                Gestiona tus datos de cuenta.
                            </div>
                        </div>

                        <div class="d-flex ga-2">
                            <v-btn v-if="isEditing" variant="text" rounded="lg" class="text-none"
                                :disabled="loading || saving" @click="toggleEdit">
                                Cancelar
                            </v-btn>

                            <v-btn v-if="isEditing" color="primary" variant="flat" rounded="lg" class="text-none"
                                :disabled="saving" :loading="saving" @click="save">
                                Guardar
                            </v-btn>

                            <v-btn v-else variant="outlined" rounded="lg" class="text-none"
                                :disabled="loading || saving" @click="toggleEdit">
                                Editar
                            </v-btn>
                        </div>
                    </div>

                    <!-- Tabs = segunda pantalla -->
                    <v-tabs v-model="tab" class="mb-4">
                        <v-tab value="account">Cuenta</v-tab>
                        <v-tab value="addresses">Direcciones</v-tab>
                    </v-tabs>

                    <v-divider class="mb-6" />

                    <v-skeleton-loader v-if="loading" type="article" />

                    <v-alert v-else-if="error" type="error" variant="tonal" class="mb-4">
                        {{ error }}
                    </v-alert>

                    <v-window v-else v-model="tab" :transition="false" :reverse-transition="false">
                        <!-- Pantalla 1: Cuenta -->
                        <v-window-item value="account">
                            <!-- Form cuenta -->
                            <v-form ref="formRef" @submit.prevent="save" class="d-flex flex-column ga-4 mt-2">
                                <v-text-field v-model="form.name" label="Nombre" variant="outlined"
                                    density="comfortable" rounded="lg" :readonly="!isEditing" :rules="nameRules" />

                                <v-text-field v-model="form.email" label="Email" variant="outlined"
                                    density="comfortable" rounded="lg" readonly />
                                <!-- Info extra del usuario -->
                                <v-card rounded="lg" variant="tonal" class="pa-4 mb-4">
                                    <div class="text-body-2 text-medium-emphasis mt-3">
                                        Miembro desde: <b>{{ formatDate(auth.user?.created_at) }}</b>
                                    </div>
                                    <div class="text-body-2 text-medium-emphasis">
                                        Última actualización: <b>{{ formatDate(auth.user?.updated_at) }}</b>
                                    </div>
                                </v-card>

                                <v-divider />

                                <div class="d-flex justify-end">
                                    <v-btn color="error" variant="outlined" rounded="lg" class="text-none"
                                        :disabled="loading || saving || deleteLoading" @click="deleteDialog = true">
                                        Eliminar cuenta
                                    </v-btn>
                                </div>

                                <!-- Dialog eliminar cuenta -->
                                <v-dialog v-model="deleteDialog" max-width="520">
                                    <v-card rounded="xl">
                                        <v-card-title class="text-h6 font-weight-bold">
                                            Confirmar eliminación de cuenta
                                        </v-card-title>

                                        <v-card-text class="text-body-2 text-medium-emphasis">
                                            Esta acción no se puede deshacer. Para confirmar, escribe
                                            <b>ELIMINAR</b> en el campo.
                                            <div class="mt-4">
                                                <v-text-field v-model="deleteConfirmText"
                                                    label="Escribe ELIMINAR para confirmar" variant="outlined"
                                                    rounded="lg" density="comfortable" :error="!!deleteError"
                                                    :error-messages="deleteError" autocomplete="off" />
                                            </div>
                                        </v-card-text>

                                        <v-card-actions class="px-6 pb-6">
                                            <v-spacer />
                                            <v-btn variant="text" rounded="lg" class="text-none"
                                                :disabled="deleteLoading" @click="closeDeleteDialog">
                                                Cancelar
                                            </v-btn>
                                            <v-btn color="error" variant="flat" rounded="lg" class="text-none"
                                                :loading="deleteLoading"
                                                :disabled="deleteLoading || deleteConfirmText.trim() !== 'ELIMINAR'"
                                                @click="deleteAccount">
                                                Eliminar definitivamente
                                            </v-btn>
                                        </v-card-actions>
                                    </v-card>
                                </v-dialog>
                            </v-form>
                        </v-window-item>


                        <v-window-item value="addresses">
                            <div class="mb-4">
                                <div class="text-h6 font-weight-bold">Mis direcciones</div>
                                <div class="text-body-2 text-medium-emphasis">
                                    Guarda y edita tus direcciones de envío y facturación.
                                </div>
                            </div>

                            <v-skeleton-loader v-if="addressesLoading" type="article" />

                            <v-alert v-if="addressTriedSave && addressesError" type="error" variant="tonal"
                                class="mb-4">
                                {{ addressesError }}
                            </v-alert>

                            <div v-else class="d-flex flex-column ga-4">
                                <!-- ENVÍO -->
                                <v-card rounded="lg" variant="outlined" class="pa-4">
                                    <div class="text-subtitle-1 font-weight-bold mb-3">Dirección de envío</div>

                                    <!-- Si no hay shipping y no editas -->
                                    <v-alert v-if="!addresses.shipping && !isEditing" type="info" variant="tonal"
                                        density="comfortable">
                                        Aún no has añadido dirección de envío. Pulsa <b>Editar</b> para añadirla.
                                    </v-alert>

                                    <!-- Si hay dirección o estás editando -->
                                    <v-form ref="shippingFormRef" v-else class="d-flex flex-column ga-3"
                                        @submit.prevent>
                                        <v-text-field v-model="addressForm.shipping.line1" label="Dirección"
                                            variant="outlined" density="comfortable" rounded="lg" :readonly="!isEditing"
                                            :rules="isEditing ? addressRules.line1 : []" />

                                        <v-text-field v-model="addressForm.shipping.city" label="Ciudad"
                                            variant="outlined" density="comfortable" rounded="lg" :readonly="!isEditing"
                                            :rules="isEditing ? addressRules.city : []" />

                                        <v-text-field v-model="addressForm.shipping.zip" label="Código postal"
                                            variant="outlined" density="comfortable" rounded="lg" :readonly="!isEditing"
                                            :rules="isEditing ? addressRules.zip : []" />

                                        <v-text-field v-model="addressForm.shipping.country" label="País"
                                            variant="outlined" density="comfortable" rounded="lg" :readonly="!isEditing"
                                            :rules="isEditing ? addressRules.country : []" />
                                    </v-form>
                                </v-card>

                                <!-- FACTURACIÓN -->
                                <v-card rounded="lg" variant="outlined" class="pa-4">
                                    <div class="text-subtitle-1 font-weight-bold mb-3">Dirección de facturación</div>

                                    <!-- Si no hay billing y no editas -->
                                    <v-alert v-if="!addresses.billing && !isEditing" type="info" variant="tonal"
                                        density="comfortable">
                                        Aún no has añadido dirección de facturación. Pulsa <b>Editar</b> para añadirla.
                                    </v-alert>

                                    <!-- Si hay dirección o estás editando -->
                                    <v-form ref="billingFormRef" v-else class="d-flex flex-column ga-3" @submit.prevent>
                                        <v-text-field v-model="addressForm.billing.line1" label="Dirección"
                                            variant="outlined" density="comfortable" rounded="lg" :readonly="!isEditing"
                                            :rules="isEditing ? addressRules.line1 : []" />

                                        <v-text-field v-model="addressForm.billing.city" label="Ciudad"
                                            variant="outlined" density="comfortable" rounded="lg" :readonly="!isEditing"
                                            :rules="isEditing ? addressRules.city : []" />

                                        <v-text-field v-model="addressForm.billing.zip" label="Código postal"
                                            variant="outlined" density="comfortable" rounded="lg" :readonly="!isEditing"
                                            :rules="isEditing ? addressRules.zip : []" />

                                        <v-text-field v-model="addressForm.billing.country" label="País"
                                            variant="outlined" density="comfortable" rounded="lg" :readonly="!isEditing"
                                            :rules="isEditing ? addressRules.country : []" />
                                    </v-form>
                                </v-card>

                                <v-alert v-if="addressSuccess" type="success" variant="tonal">
                                    Direcciones guardadas correctamente.
                                </v-alert>
                            </div>
                        </v-window-item>
                    </v-window>

                    <v-snackbar v-model="snackbar.show" :timeout="snackbar.timeout" :color="snackbar.color"
                        variant="flat" rounded="lg">
                        {{ snackbar.text }}

                        <template #actions>
                            <v-btn variant="text" class="text-none" @click="snackbar.show = false">
                                Cerrar
                            </v-btn>
                        </template>
                    </v-snackbar>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { onMounted, ref, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()

const auth = useAuthStore()

const loading = ref(true)
const saving = ref(false)
const error = ref(null)
const success = ref(false)

const isEditing = ref(false)

const formRef = ref(null)
const form = ref({
    name: '',
    email: '',
})

const tab = ref('account')

const deleteDialog = ref(false)
const deleteConfirmText = ref('')
const deleteLoading = ref(false)
const deleteError = ref('')

function formatDate(dateStr) {
    if (!dateStr) return '—'
    try { return new Date(dateStr).toLocaleString() } catch { return dateStr }
}

const addresses = ref({ shipping: null, billing: null })
const addressesLoading = ref(false)
const addressesError = ref(null)
const addressTriedSave = ref(false)

const shippingFormRef = ref(null)
const billingFormRef = ref(null)

const snackbar = ref({
    show: false,
    text: '',
    color: 'success',
    timeout: 2500,
})

function showSnackbar(text, color = 'success', timeout = 2500) {
    snackbar.value = { show: true, text, color, timeout }
}

async function fetchAddresses() {
    addressesLoading.value = true
    addressesError.value = null
    try {
        const { data } = await axios.get('/api/addresses/me')
        addresses.value = {
            shipping: data?.shipping ?? null,
            billing: data?.billing ?? null,
        }
    } catch (e) {
        addresses.value = { shipping: null, billing: null }
        addressesError.value = 'No se pudieron cargar tus direcciones'
    } finally {
        addressesLoading.value = false
    }
}

const addressEditing = ref({ shipping: false, billing: false })
const addressSaving = ref(false)
const addressSuccess = ref(false)

const addressForm = ref({
    shipping: { line1: '', city: '', zip: '', country: '' },
    billing: { line1: '', city: '', zip: '', country: '' },
})

const addressRules = {
    line1: [v => !!v || 'La dirección es obligatoria'],
    city: [v => !!v || 'La ciudad es obligatoria'],
    zip: [v => !!v || 'El código postal es obligatorio'],
    country: [v => !!v || 'El país es obligatorio'],
}

function fillAddressFormFrom(kind) {
    const a = addresses.value[kind]
    addressForm.value[kind] = {
        line1: a?.line1 ?? '',
        city: a?.city ?? '',
        zip: a?.zip ?? '',
        country: a?.country ?? '',
    }
}

function isAddressFilled(kind) {
    const f = addressForm.value[kind]
    return !!(f.line1?.trim() && f.city?.trim() && f.zip?.trim() && f.country?.trim())
}

function isAddressTouched(kind) {
    const f = addressForm.value[kind]
    return Object.values(f).some(v => (v ?? '').toString().trim() !== '')
}

function startEdit(kind) {
    addressSuccess.value = false
    addressesError.value = null
    addressEditing.value[kind] = true
    addressTriedSave.value = false
    fillAddressFormFrom(kind)
}

function cancelEdit(kind) {
    addressEditing.value[kind] = false
    addressSuccess.value = false
    fillAddressFormFrom(kind)
}

async function saveAddress(kind) {
    addressSuccess.value = false
    addressesError.value = null
    addressSaving.value = true
    addressTriedSave.value = true

    try {
        await axios.get('/sanctum/csrf-cookie')
        await axios.put(`/api/addresses/${kind}`, addressForm.value[kind])
        await fetchAddresses()
        addressEditing.value[kind] = false
        addressSuccess.value = true
    } catch (e) {
        const msg = e?.response?.data?.message
        addressesError.value = msg || 'No se pudo guardar la dirección'
    } finally {
        addressSaving.value = false
    }
}

const nameRules = [
    v => !!v || 'El nombre es obligatorio',
    v => (v?.length ?? 0) >= 2 || 'El nombre debe tener al menos 2 caracteres',
    v => (v?.length ?? 0) <= 255 || 'Máximo 255 caracteres',
]

function fillFromUser() {
    form.value.name = auth.user?.name ?? ''
    form.value.email = auth.user?.email ?? ''
}

async function toggleEdit() {
    success.value = false
    error.value = null

    if (!isEditing.value) {
        isEditing.value = true

        // Cargar direcciones en background por si el usuario va a editarlas
        await fetchAddresses()
        fillAddressFormFrom('shipping')
        fillAddressFormFrom('billing')
        return
    }

    isEditing.value = false
    fillFromUser()
    fillAddressFormFrom('shipping')
    fillAddressFormFrom('billing')
}

async function save() {
    error.value = null

    // Validar el form de cuenta si está montado (si estás en Direcciones puede no existir)
    const result = await formRef.value?.validate?.()
    if (result && result.valid === false) return

    // Direcciones: detectar si “tocó” algo y si está completa
    const shipTouched = isAddressTouched('shipping')
    const billTouched = isAddressTouched('billing')

    const shipFilled = isAddressFilled('shipping')
    const billFilled = isAddressFilled('billing')

    // Regla:
    // - Si NO escribe nada en direcciones -> se puede guardar (nombre, etc.)
    // - Si toca algo de shipping/billing pero no completa -> NO deja guardar
    if ((shipTouched && !shipFilled) || (billTouched && !billFilled)) {
        // Llevarlo a Direcciones para que vea el porqué
        tab.value = 'addresses'
        await nextTick()

        // Forzar validación para que salgan los mensajes sin blur
        if (shipTouched) await shippingFormRef.value?.validate?.()
        if (billTouched) await billingFormRef.value?.validate?.()

        showSnackbar('Completa todos los campos de la dirección antes de guardar.', 'error', 3500)
        return
    }

    saving.value = true
    try {
        await axios.get('/sanctum/csrf-cookie')

        // Guardar nombre
        await auth.updateProfile({ name: form.value.name })

        // Guardar direcciones solo si están completas
        if (shipFilled) {
            await axios.put('/api/addresses/shipping', addressForm.value.shipping)
        }
        if (billFilled) {
            await axios.put('/api/addresses/billing', addressForm.value.billing)
        }

        // Recargar y sincronizar formularios
        await fetchAddresses()
        fillAddressFormFrom('shipping')
        fillAddressFormFrom('billing')
        fillFromUser()

        isEditing.value = false
        showSnackbar('Cambios guardados correctamente.', 'success', 2500)

    } catch (e) {
        const status = e?.response?.status
        const data = e?.response?.data

        console.error('ERROR guardando perfil:', { status, data, e })

        if (status === 422 && data?.errors) {
            const first = Object.values(data.errors).flat()?.[0]
            error.value = first || data.message || 'Datos inválidos.'
        } else if (status === 419) {
            error.value = 'Tu sesión ha caducado. Recarga la página e inténtalo de nuevo.'
        } else {
            error.value = data?.message || 'No se pudo guardar el perfil'
        }

        showSnackbar(error.value, 'error', 3500)
    } finally {
        saving.value = false
    }
}

function closeDeleteDialog() {
    deleteDialog.value = false
    deleteConfirmText.value = ''
    deleteError.value = ''
}

async function deleteAccount() {
    deleteError.value = ''

    if (deleteConfirmText.value.trim() !== 'ELIMINAR') {
        deleteError.value = 'Debes escribir ELIMINAR exactamente.'
        return
    }

    deleteLoading.value = true
    try {
        await axios.get('/sanctum/csrf-cookie')
        await axios.delete('/api/me')

        // Limpieza en frontend
        closeDeleteDialog()

        // Si tu store tiene logout, úsalo:
        await auth.logout?.()

        // Redirige a home o login
        router.push('/')

    } catch (e) {
        const msg = e?.response?.data?.message
        deleteError.value = msg || 'No se pudo eliminar la cuenta.'
    } finally {
        deleteLoading.value = false
    }
}

onMounted(async () => {
    try {
        if (!auth.user) await auth.fetchUser()
        fillFromUser()
    } catch (_) {
        error.value = 'No se pudo cargar tu perfil'
    } finally {
        loading.value = false
    }
})

watch(
    () => auth.user,
    () => {
        if (!isEditing.value) fillFromUser()
    }
)

watch(tab, async (v) => {
    if (v !== 'addresses') return

    if (isEditing.value) return

    await fetchAddresses()
    fillAddressFormFrom('shipping')
    fillAddressFormFrom('billing')
})
</script>
