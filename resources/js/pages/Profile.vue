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

                        <v-btn variant="outlined" rounded="lg" :disabled="loading || saving" @click="toggleEdit">
                            {{ isEditing ? 'Cancelar' : 'Editar' }}
                        </v-btn>
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

                    <v-window v-else v-model="tab">
                        <!-- Pantalla 1: Cuenta -->
                        <v-window-item value="account">


                            <!-- Form cuenta -->
                            <v-form ref="formRef" @submit.prevent="save" class="d-flex flex-column ga-4">
                                <v-text-field v-model="form.name" label="Nombre" variant="outlined"
                                    density="comfortable" rounded="lg" :readonly="!isEditing" :rules="nameRules" />

                                <v-text-field v-model="form.email" label="Email (Gmail)" variant="outlined"
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
                                <div class="d-flex justify-end ga-3 mt-2">
                                    <v-btn v-if="isEditing" variant="flat" color="primary" rounded="lg"
                                        class="text-none" type="submit" :disabled="saving" :loading="saving">
                                        Guardar cambios
                                    </v-btn>
                                </div>

                                <v-alert v-if="success" type="success" variant="tonal" class="mt-2">
                                    Cambios guardados correctamente.
                                </v-alert>
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
                                    <div class="d-flex align-center justify-space-between mb-3">
                                        <div class="text-subtitle-1 font-weight-bold">Dirección de envío</div>

                                        <v-btn variant="flat" color="primary" rounded="lg" class="text-none"
                                            :disabled="addressSaving" @click="startEdit('shipping')">
                                            {{ addresses.shipping ? 'Editar' : 'Añadir' }}
                                        </v-btn>
                                    </div>

                                    <v-form class="d-flex flex-column ga-3" @submit.prevent="saveAddress('shipping')">
                                        <v-text-field v-model="addressForm.shipping.line1" label="Dirección"
                                            variant="outlined" density="comfortable" rounded="lg"
                                            :readonly="!addressEditing.shipping" :rules="addressRules.line1" />

                                        <v-text-field v-model="addressForm.shipping.city" label="Ciudad"
                                            variant="outlined" density="comfortable" rounded="lg"
                                            :readonly="!addressEditing.shipping" :rules="addressRules.city" />

                                        <v-text-field v-model="addressForm.shipping.zip" label="Código postal"
                                            variant="outlined" density="comfortable" rounded="lg"
                                            :readonly="!addressEditing.shipping" :rules="addressRules.zip" />

                                        <v-text-field v-model="addressForm.shipping.country" label="País"
                                            variant="outlined" density="comfortable" rounded="lg"
                                            :readonly="!addressEditing.shipping" :rules="addressRules.country" />

                                        <div class="d-flex justify-end ga-2">
                                            <v-btn v-if="addressEditing.shipping" variant="text" rounded="lg"
                                                class="text-none" :disabled="addressSaving"
                                                @click="cancelEdit('shipping')">
                                                Cancelar
                                            </v-btn>

                                            <v-btn v-if="addressEditing.shipping && isAddressFilled('shipping')"
                                                type="submit" color="primary" variant="flat" rounded="lg"
                                                class="text-none" :loading="addressSaving" :disabled="addressSaving">
                                                Guardar
                                            </v-btn>

                                        </div>
                                    </v-form>
                                </v-card>

                                <!-- FACTURACIÓN -->
                                <v-card rounded="lg" variant="outlined" class="pa-4">
                                    <div class="d-flex align-center justify-space-between mb-3">
                                        <div class="text-subtitle-1 font-weight-bold">Dirección de facturación</div>

                                        <v-btn variant="flat" color="primary" rounded="lg" class="text-none"
                                            :disabled="addressSaving" @click="startEdit('billing')">
                                            {{ addresses.billing ? 'Editar' : 'Añadir' }}
                                        </v-btn>
                                    </div>

                                    <v-form class="d-flex flex-column ga-3" @submit.prevent="saveAddress('billing')">
                                        <v-text-field v-model="addressForm.billing.line1" label="Dirección"
                                            variant="outlined" density="comfortable" rounded="lg"
                                            :readonly="!addressEditing.billing" :rules="addressRules.line1" />

                                        <v-text-field v-model="addressForm.billing.city" label="Ciudad"
                                            variant="outlined" density="comfortable" rounded="lg"
                                            :readonly="!addressEditing.billing" :rules="addressRules.city" />

                                        <v-text-field v-model="addressForm.billing.zip" label="Código postal"
                                            variant="outlined" density="comfortable" rounded="lg"
                                            :readonly="!addressEditing.billing" :rules="addressRules.zip" />

                                        <v-text-field v-model="addressForm.billing.country" label="País"
                                            variant="outlined" density="comfortable" rounded="lg"
                                            :readonly="!addressEditing.billing" :rules="addressRules.country" />

                                        <div class="d-flex justify-end ga-2">
                                            <v-btn v-if="addressEditing.billing" variant="text" rounded="lg"
                                                class="text-none" :disabled="addressSaving"
                                                @click="cancelEdit('billing')">
                                                Cancelar
                                            </v-btn>

                                            <v-btn v-if="addressEditing.billing && isAddressFilled('billing')"
                                                type="submit" color="primary" variant="flat" rounded="lg"
                                                class="text-none" :loading="addressSaving" :disabled="addressSaving">
                                                Guardar
                                            </v-btn>


                                        </div>
                                    </v-form>
                                </v-card>

                                <v-alert v-if="addressSuccess" type="success" variant="tonal">
                                    Direcciones guardadas correctamente.
                                </v-alert>

                            </div>
                        </v-window-item>


                    </v-window>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

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

function formatDate(dateStr) {
    if (!dateStr) return '—'
    try { return new Date(dateStr).toLocaleString() } catch { return dateStr }
}

const addresses = ref({ shipping: null, billing: null })
const addressesLoading = ref(false)
const addressesError = ref(null)
const addressTriedSave = ref(false)


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

function toggleEdit() {
    success.value = false
    error.value = null

    if (!isEditing.value) {
        isEditing.value = true
        return
    }

    // Cancelar edición: restaurar datos originales
    isEditing.value = false
    fillFromUser()
}

async function save() {
    success.value = false
    error.value = null

    const result = await formRef.value?.validate?.()
    if (result && result.valid === false) return

    saving.value = true
    try {
        await auth.updateProfile({ name: form.value.name })
        // auth.user ya se actualiza en la store, pero por si acaso:
        fillFromUser()
        isEditing.value = false
        success.value = true
    } catch (e) {
        // Laravel suele mandar 422 con errors
        const msg = e?.response?.data?.message
        error.value = msg || 'No se pudo guardar el perfil'
    } finally {
        saving.value = false
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
    if (v === 'addresses') {
        await fetchAddresses()
        fillAddressFormFrom('shipping')
        fillAddressFormFrom('billing')
    }
})

</script>
