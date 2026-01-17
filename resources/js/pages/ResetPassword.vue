<template>
    <v-container class="fp-bg" fluid>
        <v-row class="fp-row" align="center" justify="center" no-gutters>
            <v-col cols="12" sm="10" md="7" lg="5" xl="4">
                <v-card class="fp-card" rounded="xl" elevation="10">
                    <v-card-text class="pa-8">
                        <div class="d-flex align-center ga-2 mb-2">
                            <v-icon icon="mdi-lock-check" size="22" />
                            <span class="text-h6 font-weight-bold">Nueva Contraseña</span>
                        </div>

                        <div class="text-medium-emphasis mb-6">
                            Introduce tu nueva contraseña para confirmar el cambio.
                        </div>

                        <v-alert v-if="errorMsg" type="error" variant="tonal" density="comfortable" class="mb-4">
                            {{ errorMsg }}
                        </v-alert>

                        <v-form ref="form" @submit.prevent="handleReset">
                            <v-text-field
                                v-model="email"
                                label="Email"
                                variant="outlined"
                                density="comfortable"
                                readonly
                                prepend-inner-icon="mdi-email-outline"
                                class="mb-2"
                                color="grey"
                            ></v-text-field>

                            <v-text-field
                                v-model="password"
                                label="Nueva contraseña"
                                type="password"
                                variant="outlined"
                                density="comfortable"
                                :rules="[v => !!v || 'Requerido', v => v.length >= 8 || 'Mínimo 8 caracteres']"
                                prepend-inner-icon="mdi-lock-outline"
                            ></v-text-field>

                            <v-text-field
                                v-model="password_confirmation"
                                label="Confirmar contraseña"
                                type="password"
                                variant="outlined"
                                density="comfortable"
                                :rules="[v => !!v || 'Requerido', v => v === password || 'Las contraseñas no coinciden']"
                                prepend-inner-icon="mdi-lock-check-outline"
                                class="mt-2"
                            ></v-text-field>

                            <v-btn type="submit" color="primary" size="large" rounded="lg" block class="mt-4"
                                :loading="loading">
                                Cambiar Contraseña
                            </v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const form = ref(null);

const token = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');

const loading = ref(false);
const errorMsg = ref('');

onMounted(() => {
    // IMPORTANTE: Imprimimos qué recibimos de la URL
    console.log("URL Query Params:", route.query);

    token.value = route.query.token || '';
    email.value = route.query.email || '';
});

const handleReset = async () => {
    errorMsg.value = '';

    const valid = await form.value?.validate();
    if (!valid.valid) return;

    loading.value = true;

    // DEBUG: Mira la consola antes de enviar
    const payload = {
        token: token.value,
        email: email.value,
        password: password.value,
        password_confirmation: password_confirmation.value
    };
    console.log("Enviando al backend:", payload);

    try {
        await axios.post('/api/reset-password', payload);

        // Si funciona
        alert('Contraseña restablecida correctamente. Inicia sesión.');
        router.push('/login');

    } catch (error) {
        console.error("Error del backend:", error.response);

        if (error.response && error.response.data && error.response.data.errors) {
            // Error de validación (ej. contraseña corta)
            errorMsg.value = Object.values(error.response.data.errors).flat().join('\n');
        } else if (error.response && error.response.data.message) {
             // Error específico (Token inválido)
             errorMsg.value = error.response.data.message;
        } else {
            errorMsg.value = 'El token es inválido o ha expirado. Solicita uno nuevo.';
        }
    } finally {
        loading.value = false;
    }
};
</script>
