<template>
    <v-footer class="bg-grey-lighten-5 text-high-emphasis pt-8 pb-4 border-t" style="z-index: 10;">
        <v-container class="py-0">
            <v-row>
                <v-col cols="12" md="4" lg="3">
                    <div class="d-flex align-center mb-3">
                        <v-icon icon="mdi-feather" color="primary" size="28" class="mr-2" />
                        <span class="text-h6 font-weight-bold">TiendaModa</span>
                    </div>
                    <p class="text-medium-emphasis text-body-2 mb-3" style="line-height: 1.6; max-width: 300px;">
                        Diseño contemporáneo para la vida moderna. Calidad y estilo en cada detalle.
                    </p>

                    <div class="d-flex align-center mb-2 text-medium-emphasis">
                        <v-icon icon="mdi-map-marker-outline" size="small" class="mr-3" />
                        <span class="text-body-2">Universidad de Alicante (UA), Alicante</span>
                    </div>
                    <div class="d-flex align-center text-medium-emphasis">
                        <v-icon icon="mdi-email-outline" size="small" class="mr-3" />
                        <a class="text-body-2 footer-link" href="mailto:tiendamoda.ua@gmail.com">
                            tiendamoda.ua@gmail.com
                        </a>
                    </div>
                </v-col>

                <v-col cols="6" md="2" lg="2" offset-lg="1">
                    <h4 class="text-subtitle-1 font-weight-bold mb-3">Explorar</h4>
                    <div class="d-flex flex-column gap-1">
                        <router-link to="/shop" class="footer-link">Novedades</router-link>
                        <router-link to="/shop" class="footer-link">Mujer</router-link>
                        <router-link to="/shop" class="footer-link">Hombre</router-link>
                        <router-link to="/shop" class="footer-link">Colecciones</router-link>
                    </div>
                </v-col>

                <v-col cols="6" md="2" lg="2">
                    <h4 class="text-subtitle-1 font-weight-bold mb-3">Soporte</h4>
                    <div class="d-flex flex-column gap-1">
                        <router-link to="/ayuda" class="footer-link">Ayuda / FAQ</router-link>
                        <router-link to="/envios" class="footer-link">Envíos</router-link>
                        <router-link to="/devoluciones" class="footer-link">Devoluciones</router-link>
                        <router-link to="/contacto" class="footer-link">Contacto</router-link>
                        <router-link to="/sobre-nosotros" class="footer-link">Sobre Nosotros</router-link>
                    </div>
                </v-col>

                <v-col cols="12" md="4" lg="4">
                    <h4 class="text-subtitle-1 font-weight-bold mb-3">Newsletter</h4>
                    <p class="text-medium-emphasis text-body-2 mb-3">
                        Suscríbete y obtén 10€ de descuento en tu primera compra.
                    </p>

                    <v-form @submit.prevent="onSubscribe">
                        <v-text-field v-model="email" placeholder="Introduce tu email" variant="outlined"
                            density="compact" bg-color="white" hide-details rounded="lg">
                            <template v-slot:append-inner>
                                <v-btn color="black" variant="flat" size="small" class="text-none px-4" rounded="md"
                                    @click="onSubscribe">
                                    Ok
                                </v-btn>
                            </template>
                        </v-text-field>
                    </v-form>

                    <div class="mt-4 opacity-60">
                        <div class="text-caption font-weight-bold mb-2 text-disabled">PAGO SEGURO</div>
                        <div class="d-flex gap-3">
                            <v-icon icon="mdi-credit-card-outline" size="default" />
                            <v-icon icon="mdi-bank" size="large" />
                            <v-icon icon="mdi-paypal" size="large" />
                        </div>
                    </div>
                </v-col>
            </v-row>

            <v-divider class="my-4"></v-divider>

            <div
                class="d-flex flex-column flex-md-row justify-space-between align-center text-caption text-medium-emphasis">
                <div class="mb-4 mb-md-0">
                    &copy; {{ year }} TiendaModa S.L. ·
                    <a href="#" class="text-decoration-none text-grey-darken-1">Privacidad</a> ·
                    <router-link to="/terminos" class="text-decoration-none text-grey-darken-1">Términos y
                        Condiciones</router-link>

                </div>

                <div class="d-flex gap-2">
                    <v-btn icon="mdi-instagram" variant="text" color="grey-darken-1" size="small"></v-btn>
                    <v-btn icon="mdi-facebook" variant="text" color="grey-darken-1" size="small"></v-btn>
                    <v-btn icon="mdi-twitter" variant="text" color="grey-darken-1" size="small"></v-btn>
                    <v-btn icon="mdi-linkedin" variant="text" color="grey-darken-1" size="small"></v-btn>
                </div>
            </div>
        </v-container>
    </v-footer>
</template>

<script setup>
import { ref } from 'vue'

const year = new Date().getFullYear()
const email = ref('')

function onSubscribe() {
    if (!email.value) return;
    // Lógica futura
    email.value = ''
}
</script>

<style scoped>
/* Estilo sutil para los enlaces */
.footer-link {
    color: rgba(0, 0, 0, 0.6);
    /* Text medium emphasis */
    text-decoration: none;
    font-size: 0.95rem;
    transition: color 0.2s ease, padding-left 0.2s ease;
    width: fit-content;
    line-height: 1.25;
}

.footer-link:hover {
    color: black;
    padding-left: 4px;
    /* Pequeño desplazamiento a la derecha al pasar el ratón */
}

.gap-3 {
    gap: 12px;
}

.gap-4 {
    gap: 16px;
}

.gap-2 {
    gap: 8px;
}

.gap-1 {
    gap: 4px;
}


/* Borde superior muy sutil */
.border-t {
    border-top: 1px solid rgba(0, 0, 0, 0.05) !important;
}
</style>
