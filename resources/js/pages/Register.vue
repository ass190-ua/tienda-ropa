<template>
    <v-container class="register-bg" fluid>
        <v-row class="register-row" align="center" justify="center" no-gutters>
            <v-col cols="12" md="10" lg="8" xl="7">
                <v-card class="register-card" rounded="xl" elevation="6">
                    <v-row no-gutters>
                        <!-- Panel izquierdo -->
                        <v-col cols="12" md="6" class="d-none d-md-flex">
                            <div class="left-panel">
                                <div class="brand">
                                    <v-icon icon="mdi-feather" size="22" class="mr-2" />
                                    <span class="brand-text">Be Urban</span>
                                </div>

                                <h2 class="headline">Crea tu cuenta</h2>
                                <p class="subhead">
                                    Regístrate para guardar favoritos, gestionar tu carrito y seguir tus pedidos.
                                </p>

                                <div class="perks">
                                    <div class="perk">
                                        <v-icon icon="mdi-account-heart-outline" />
                                        <span>Wishlist y recomendaciones</span>
                                    </div>
                                    <div class="perk">
                                        <v-icon icon="mdi-cart-outline" />
                                        <span>Carrito persistente</span>
                                    </div>
                                    <div class="perk">
                                        <v-icon icon="mdi-truck-fast-outline" />
                                        <span>Historial y seguimiento</span>
                                    </div>
                                </div>

                                <div class="promo">
                                    <div class="promo-badge">Tip</div>
                                    <div class="promo-text">
                                        Usa un email real para recuperar tu contraseña si la olvidas.
                                    </div>
                                </div>
                            </div>
                        </v-col>

                        <!-- Panel derecho: Form -->
                        <v-col cols="12" md="6">
                            <div class="right-panel">
                                <div class="d-flex d-md-none align-center mb-6">
                                    <v-icon icon="mdi-feather" size="22" class="mr-2" />
                                    <span class="text-h6 font-weight-bold">Be Urban</span>
                                </div>

                                <div class="mb-2">
                                    <div class="text-h5 font-weight-bold">Crear cuenta</div>
                                    <div class="text-medium-emphasis mt-1">
                                        Completa tus datos para registrarte.
                                    </div>
                                </div>

                                <v-alert v-if="errorMsg" type="error" variant="tonal" class="mt-4"
                                    density="comfortable">
                                    {{ errorMsg }}
                                </v-alert>

                                <v-form ref="form" class="mt-6" @submit.prevent="onSubmit">
                                    <v-text-field v-model="name" label="Nombre" placeholder="Tu nombre"
                                        autocomplete="name" :rules="nameRules"
                                        prepend-inner-icon="mdi-account-outline" />

                                    <v-text-field v-model="email" class="mt-2" label="Email"
                                        placeholder="tuemail@ejemplo.com" type="email" autocomplete="email"
                                        :rules="emailRules" prepend-inner-icon="mdi-email-outline" />

                                    <v-text-field v-model="password" class="mt-2" label="Contraseña"
                                        placeholder="••••••••" :type="showPassword ? 'text' : 'password'"
                                        autocomplete="new-password" :rules="passwordRules"
                                        prepend-inner-icon="mdi-lock-outline"
                                        :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                        @click:append-inner="showPassword = !showPassword" />

                                    <v-text-field v-model="confirmPassword" class="mt-2" label="Confirmar contraseña"
                                        placeholder="••••••••" :type="showConfirm ? 'text' : 'password'"
                                        autocomplete="new-password" :rules="confirmRules"
                                        prepend-inner-icon="mdi-lock-check-outline"
                                        :append-inner-icon="showConfirm ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                        @click:append-inner="showConfirm = !showConfirm" />

                                    <div class="mt-2">
                                        <v-checkbox v-model="acceptTerms" density="comfortable" hide-details
                                            color="primary">
                                            <template #label>
                                                <span class="text-body-2">
                                                    Acepto los
                                                    <RouterLink class="link" to="/terminos">términos</RouterLink>
                                                    y la
                                                    <RouterLink class="link" to="/privacidad">política de privacidad
                                                    </RouterLink>.
                                                </span>
                                            </template>
                                        </v-checkbox>
                                    </div>

                                    <v-btn type="submit" color="primary" size="large" class="mt-4" block rounded="lg"
                                        :loading="loading">
                                        Crear cuenta
                                    </v-btn>

                                    <div class="d-flex align-center my-6">
                                        <v-divider />
                                        <span class="mx-3 text-medium-emphasis text-body-2">o</span>
                                        <v-divider />
                                    </div>

                                    <v-btn variant="outlined" size="large" block rounded="lg"
                                        class="text-none social-btn" @click="onSocial('google')" :loading="loadingGoogle">
                                        <img src="../assets/google.png" class="social-logo" alt="Google" />
                                        Continuar con Google
                                    </v-btn>

                                    <div class="text-center mt-7">
                                        <span class="text-medium-emphasis">¿Ya tienes cuenta?</span>
                                        <RouterLink to="/login" class="link ml-1">Iniciar sesión</RouterLink>
                                    </div>
                                </v-form>
                            </div>
                        </v-col>
                    </v-row>

                    <hr>

                    <div class="text-center text-medium-emphasis text-body-2 mt-4 mb-4">
                        Registrándote aceptas nuestras <RouterLink class="link" to="/terminos">condiciones</RouterLink>.
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const form = ref(null)

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const loadingGoogle = ref(false);

const acceptTerms = ref(true)
const showPassword = ref(false)
const showConfirm = ref(false)

const loading = ref(false)
const errorMsg = ref('')

// Reglas de validación
const nameRules = [
    v => !!v || 'El nombre es obligatorio',
    v => (v?.trim()?.length ?? 0) >= 2 || 'Mínimo 2 caracteres',
]

const emailRules = [
    v => !!v || 'El email es obligatorio',
    v => /.+@.+\..+/.test(v) || 'Email no válido',
]

const passwordRules = [
    v => !!v || 'La contraseña es obligatoria',
    v => (v?.length ?? 0) >= 8 || 'Mínimo 8 caracteres',
]

const confirmRules = [
    v => !!v || 'Confirma la contraseña',
    v => v === password.value || 'Las contraseñas no coinciden',
]

async function onSubmit() {
    errorMsg.value = ''

    const res = await form.value?.validate()
    if (!res?.valid) return

    if (!acceptTerms.value) {
        errorMsg.value = 'Debes aceptar los términos y la política de privacidad.'
        return
    }

    loading.value = true
    try {
        // --- LLAMADA REAL AL BACKEND ---
        await authStore.register({
            name: name.value,
            email: email.value,
            password: password.value,
            password_confirmation: confirmPassword.value // Laravel exige este campo exacto
        })

        const redirect = route.query.redirect
        if (typeof redirect === 'string' && redirect.startsWith('/')) {
            router.push(redirect)
        } else {
            router.push('/')
        }
    } catch (e) {
        // Manejo de errores de validación de Laravel (ej: email duplicado)
        if (e.response && e.response.status === 422) {
            const errors = e.response.data.errors
            // Mostramos el primer error que encontremos
            errorMsg.value = Object.values(errors).flat()[0] || 'Datos inválidos'
        } else {
            errorMsg.value = 'Error de conexión. Inténtalo de nuevo.'
        }
    } finally {
        loading.value = false
    }
}

const onSocial = (provider) => {
    loadingGoogle.value = true;
    // Esto iniciará el viaje de ida y vuelta.
    // Al volver, aterrizarás en '/' ya logueado.
    window.location.href = '/api/auth/google/redirect';
};
</script>

<style scoped>
.register-bg {
    min-height: 100%;
    display: flex;
    align-items: center;
    padding: 24px 0;
}

.register-row {
    width: 100%;
}

.register-card {
    overflow: hidden;
}

.left-panel {
    width: 100%;
    padding: 42px 36px;
    background: linear-gradient(135deg, rgba(19, 127, 236, 0.10), rgba(19, 127, 236, 0.02));
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.brand {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 800;
}

.brand-text {
    font-size: 18px;
}

.headline {
    margin-top: 6px;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.15;
}

.subhead {
    color: rgba(0, 0, 0, .65);
    line-height: 1.55;
    max-width: 420px;
}

.perks {
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.perk {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(0, 0, 0, .75);
    font-weight: 600;
}

.promo {
    margin-top: auto;
    padding: 14px 14px;
    border: 1px solid rgba(0, 0, 0, .08);
    border-radius: 14px;
    background: rgba(255, 255, 255, .65);
    display: flex;
    align-items: center;
    gap: 10px;
}

.promo-badge {
    font-size: 12px;
    font-weight: 800;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(19, 127, 236, 0.12);
}

.promo-text {
    color: rgba(0, 0, 0, .75);
}

.right-panel {
    padding: 42px 36px;
}

.link {
    text-decoration: none;
    color: rgb(19, 127, 236);
    font-weight: 700;
}

.link:hover {
    text-decoration: underline;
}

.social-btn {
    justify-content: center;
    position: relative;
}

.social-logo {
    position: absolute;
    left: 16px;
    width: 22px;
    height: 22px;
}
</style>
