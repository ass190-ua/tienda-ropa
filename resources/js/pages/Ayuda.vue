<template>
    <v-container class="py-8">
        <!-- Header / Hero -->
        <v-card rounded="xl" variant="tonal" class="pa-6 mb-6">
            <div class="d-flex flex-column flex-md-row align-start align-md-center justify-space-between ga-4">
                <div>
                    <div class="text-h4 font-weight-bold mb-1">Ayuda</div>
                    <div class="text-body-1 text-medium-emphasis">
                        Respuestas rápidas y formas de contactar con nosotros.
                    </div>
                </div>

                <div class="d-flex ga-2">
                    <v-btn variant="outlined" rounded="lg" prepend-icon="mdi-arrow-left" @click="goBack">
                        Volver
                    </v-btn>
                    <v-btn color="primary" rounded="lg" prepend-icon="mdi-email" href="mailto:tiendamoda.ua@gmail.com">
                        Escribir email
                    </v-btn>
                </div>
            </div>
        </v-card>

        <v-row>
            <!-- FAQs -->
            <v-col cols="12" md="7">
                <v-card class="pa-6" rounded="xl" elevation="2">
                    <div class="d-flex align-center justify-space-between mb-4">
                        <div>
                            <div class="text-h6 font-weight-bold">Preguntas frecuentes</div>
                            <div class="text-body-2 text-medium-emphasis">
                                Las dudas más comunes, en un momento.
                            </div>
                        </div>
                        <v-icon icon="mdi-help-circle-outline" class="text-medium-emphasis" />
                    </div>

                    <v-expansion-panels variant="accordion" rounded="lg">
                        <v-expansion-panel>
                            <v-expansion-panel-title>
                                <div class="d-flex align-center ga-3">
                                    <v-icon icon="mdi-cart-outline" />
                                    <span>¿Cómo puedo hacer un pedido?</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="text-body-2">
                                Busca un producto, añádelo al carrito y completa el proceso de compra.
                            </v-expansion-panel-text>
                        </v-expansion-panel>

                        <v-expansion-panel>
                            <v-expansion-panel-title>
                                <div class="d-flex align-center ga-3">
                                    <v-icon icon="mdi-cart-plus" />
                                    <span>¿Puedo modificar mi carrito?</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="text-body-2">
                                Sí. Desde el carrito puedes cambiar cantidades o eliminar productos antes de pagar.
                            </v-expansion-panel-text>
                        </v-expansion-panel>

                        <v-expansion-panel>
                            <v-expansion-panel-title>
                                <div class="d-flex align-center ga-3">
                                    <v-icon icon="mdi-lock-reset" />
                                    <span>He olvidado mi contraseña, ¿qué hago?</span>
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text class="text-body-2">
                                En la pantalla de login pulsa “¿Olvidaste tu contraseña?” y sigue los pasos para
                                recuperar el acceso.
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>

                    <v-divider class="my-6" />
                </v-card>
            </v-col>

            <!-- Contact -->
            <v-col cols="12" md="5">
                <v-card class="pa-6" rounded="xl" elevation="2">
                    <div class="d-flex align-center justify-space-between mb-4">
                        <div>
                            <div class="text-h6 font-weight-bold">Contacto</div>
                            <div class="text-body-2 text-medium-emphasis">
                                Te respondemos lo antes posible.
                            </div>
                        </div>
                        <v-icon icon="mdi-chat-processing-outline" class="text-medium-emphasis" />
                    </div>

                    <v-list lines="two" class="py-0">
                        <v-list-item prepend-icon="mdi-email" title="Email" subtitle="tiendamoda-ua@gmail.com"
                            href="mailto:tiendamoda.ua@gmail.com" />
                        <v-list-item prepend-icon="mdi-phone" title="Teléfono" subtitle="+34 600 000 000" />
                        <v-list-item prepend-icon="mdi-clock-outline" title="Horario" subtitle="L–V 09:00–18:00" />
                    </v-list>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>


<script setup>
import { useRouter } from "vue-router";

const router = useRouter();

function goBack() {
    router.back();
}
</script>
