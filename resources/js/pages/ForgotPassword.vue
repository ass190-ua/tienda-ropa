<template>
    <v-container class="fp-bg" fluid>
        <v-row class="fp-row" align="center" justify="center" no-gutters>
            <v-col cols="12" sm="10" md="7" lg="5" xl="4">
                <v-card class="fp-card" rounded="xl" elevation="10">
                    <v-card-text class="pa-8">
                        <div class="d-flex align-center ga-2 mb-2">
                            <v-icon icon="mdi-lock-reset" size="22" />
                            <span class="text-h6 font-weight-bold">Recuperar contraseña</span>
                        </div>

                        <div class="text-medium-emphasis mb-6">
                            Escribe tu email y te enviaremos un enlace para restablecerla.
                        </div>

                        <v-alert v-if="successMsg" type="success" variant="tonal" density="comfortable" class="mb-4">
                            {{ successMsg }}
                        </v-alert>

                        <v-alert v-if="errorMsg" type="error" variant="tonal" density="comfortable" class="mb-4">
                            {{ errorMsg }}
                        </v-alert>

                        <v-form ref="form" @submit.prevent="onSubmit">
                            <v-text-field v-model="email" label="Email" placeholder="tuemail@ejemplo.com" type="email"
                                autocomplete="email" variant="outlined" density="comfortable" :rules="emailRules"
                                prepend-inner-icon="mdi-email-outline" />

                            <v-btn type="submit" color="primary" size="large" rounded="lg" block class="mt-2"
                                :loading="loading">
                                Enviar enlace
                            </v-btn>

                            <div class="d-flex justify-space-between align-center mt-6">
                                <RouterLink to="/login" class="link">
                                    Volver a iniciar sesión
                                </RouterLink>

                                <RouterLink to="/register" class="link">
                                    Crear cuenta
                                </RouterLink>
                            </div>
                        </v-form>
                    </v-card-text>

                    <div class="text-center text-medium-emphasis text-body-2 mb-4">
                        Si no encuentras el email, revisa spam o vuelve a intentarlo.
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref, nextTick } from 'vue'

const form = ref(null)

const email = ref('')
const loading = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

const emailRules = [
    v => !!v || 'El email es obligatorio',
    v => /.+@.+\..+/.test(v) || 'Email no válido',
]

async function onSubmit() {
    successMsg.value = ''
    errorMsg.value = ''

    const res = await form.value?.validate()
    if (!res?.valid) return

    loading.value = true
    try {
        await new Promise(r => setTimeout(r, 700))

        successMsg.value = 'Si el email existe, te hemos enviado un enlace de recuperación.'

        email.value = ''
        await nextTick()
        form.value?.resetValidation()
    } catch (e) {
        errorMsg.value = 'No se pudo enviar el enlace. Inténtalo de nuevo.'
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.fp-bg {
    min-height: 100%;
    display: flex;
    align-items: center;
    padding: 24px 0;
}

.fp-row {
    width: 100%;
}

.fp-card {
    overflow: hidden;
}

.link {
    text-decoration: none;
    color: rgb(19, 127, 236);
    font-weight: 700;
}

.link:hover {
    text-decoration: underline;
}
</style>
