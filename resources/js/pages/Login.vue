<template>
    <v-container class="login-bg" fluid>
        <v-row class="login-row" align="center" justify="center" no-gutters>
            <v-col cols="12" md="10" lg="8" xl="7">
                <v-card class="login-card" rounded="xl" elevation="6">
                    <v-row no-gutters>
                        <!-- Panel izquierdo -->
                        <v-col cols="12" md="6" class="d-none d-md-flex">
                            <div class="left-panel">
                                <div class="brand">
                                    <v-icon icon="mdi-feather" size="22" class="mr-2" />
                                    <span class="brand-text">TiendaModa</span>
                                </div>

                                <h2 class="headline">Bienvenido de vuelta</h2>
                                <p class="subhead">
                                    Accede a tu cuenta para ver tus pedidos, guardar favoritos y gestionar tu carrito.
                                </p>

                                <div class="perks">
                                    <div class="perk">
                                        <v-icon icon="mdi-shield-check-outline" />
                                        <span>Inicio de sesión seguro</span>
                                    </div>
                                    <div class="perk">
                                        <v-icon icon="mdi-truck-fast-outline" />
                                        <span>Seguimiento de pedidos</span>
                                    </div>
                                    <div class="perk">
                                        <v-icon icon="mdi-heart-outline" />
                                        <span>Wishlist y recomendaciones</span>
                                    </div>
                                </div>

                                <div class="promo">
                                    <div class="promo-badge">Nuevo</div>
                                    <div class="promo-text">
                                        Descubre nuestras <b>novedades</b> y ofertas semanales.
                                    </div>
                                </div>
                            </div>
                        </v-col>

                        <!-- Panel derecho: Form -->
                        <v-col cols="12" md="6">
                            <div class="right-panel">
                                <div class="d-flex d-md-none align-center mb-6">
                                    <v-icon icon="mdi-feather" size="22" class="mr-2" />
                                    <span class="text-h6 font-weight-bold">TiendaModa</span>
                                </div>

                                <div class="mb-2">
                                    <div class="text-h5 font-weight-bold">Iniciar sesión</div>
                                    <div class="text-medium-emphasis mt-1">
                                        Entra con tu email y contraseña.
                                    </div>
                                </div>

                                <v-alert v-if="errorMsg" type="error" variant="tonal" class="mt-4"
                                    density="comfortable">
                                    {{ errorMsg }}
                                </v-alert>

                                <v-form ref="form" class="mt-6" @submit.prevent="onSubmit">
                                    <v-text-field v-model="email" label="Email" placeholder="tuemail@ejemplo.com"
                                        type="email" autocomplete="email" variant="outlined" density="comfortable"
                                        :rules="emailRules" prepend-inner-icon="mdi-email-outline" />

                                    <v-text-field v-model="password" class="mt-2" label="Contraseña"
                                        placeholder="••••••••" :type="showPassword ? 'text' : 'password'"
                                        autocomplete="current-password" variant="outlined" density="comfortable"
                                        :rules="passwordRules" prepend-inner-icon="mdi-lock-outline"
                                        :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                        @click:append-inner="showPassword = !showPassword" />

                                    <div class="d-flex align-center justify-space-between mt-1">
                                        <v-checkbox v-model="remember" density="comfortable" hide-details
                                            label="Recuérdame" />
                                        <RouterLink to="/forgot-password" class="link">
                                            ¿Olvidaste tu contraseña?
                                        </RouterLink>
                                    </div>

                                    <v-btn type="submit" color="primary" size="large" class="mt-4" block rounded="lg"
                                        :loading="loading">
                                        Entrar
                                    </v-btn>

                                    <div class="d-flex align-center my-6">
                                        <v-divider />
                                        <span class="mx-3 text-medium-emphasis text-body-2">o</span>
                                        <v-divider />
                                    </div>

                                    <v-btn variant="outlined" size="large" block rounded="lg"
                                        class="text-none social-btn" @click="onSocial('google')">
                                        <img src="../assets/google.png" class="social-logo" alt="Google" />
                                        Continuar con Google
                                    </v-btn>

                                    <v-btn variant="outlined" size="large" block rounded="lg"
                                        class="text-none mt-3 social-btn" @click="onSocial('github')">
                                        <img src="../assets/github.png" class="social-logo" alt="Github" />
                                        Continuar con GitHub
                                    </v-btn>

                                    <div class="text-center mt-7">
                                        <span class="text-medium-emphasis">¿No tienes cuenta?</span>
                                        <RouterLink to="/register" class="link ml-1">Crear cuenta</RouterLink>
                                    </div>
                                </v-form>
                            </div>
                        </v-col>
                    </v-row>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth' // <--- Importamos el store

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const form = ref(null)
const email = ref('')
const password = ref('')
const remember = ref(true)
const showPassword = ref(false)

const loading = ref(false)
const errorMsg = ref('')

// Reglas simples (UI)
const emailRules = [
    v => !!v || 'El email es obligatorio',
    v => /.+@.+\..+/.test(v) || 'Email no válido',
]
const passwordRules = [
    v => !!v || 'La contraseña es obligatoria',
    v => (v?.length ?? 0) >= 6 || 'Mínimo 6 caracteres',
]

async function onSubmit() {
    errorMsg.value = ''
    const res = await form.value?.validate()
    if (!res?.valid) return

    loading.value = true
    try {
        // LLAMADA REAL AL BACKEND
        await authStore.login({
            email: email.value,
            password: password.value,
            remember: remember.value
        })

        const redirect = route.query.redirect
        if (typeof redirect === 'string' && redirect.startsWith('/')) {
            router.push(redirect)
        } else {
            router.push('/')
        }
    } catch (e) {
        // Capturamos el error que devuelve Laravel
        if (e.response && e.response.status === 422) {
            errorMsg.value = e.response.data.errors.email?.[0] || 'Datos inválidos'
        } else {
            errorMsg.value = 'Ocurrió un error al iniciar sesión'
        }
    } finally {
        loading.value = false
    }
}

function onSocial(provider) {
    errorMsg.value = `Login con ${provider} aún no está conectado.`
}
</script>

<style scoped>
.login-bg {
    min-height: 100%;
    display: flex;
    align-items: center;
    padding: 24px 0;
}

.login-row {
    width: 100%;
}

.login-card {
    overflow: hidden;
}

.left-panel {
    width: 100%;
    padding: 42px 36px;
    background: linear-gradient(135deg, rgba(19, 127, 236, 0.12), rgba(19, 127, 236, 0.02));
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

.link {
    text-decoration: none;
    color: rgb(19, 127, 236);
    font-weight: 700;
}

.link:hover {
    text-decoration: underline;
}
</style>
