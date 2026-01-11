<template>
    <v-container class="py-8">
        <h1 class="text-h4 font-weight-bold mb-2">Ayuda</h1>
        <p class="text-body-1 mb-6">
            Aquí encontrarás respuestas rápidas y formas de contactar con nosotros.
        </p>

        <v-row>
            <v-col cols="12" md="7">
                <v-card class="pa-4" rounded="xl">
                    <h2 class="text-h6 font-weight-bold mb-3">Preguntas frecuentes</h2>

                    <v-expansion-panels variant="accordion">
                        <v-expansion-panel title="¿Cómo puedo hacer un pedido?">
                            <v-expansion-panel-text>
                                Busca un producto, añádelo al carrito y completa el proceso de compra.
                            </v-expansion-panel-text>
                        </v-expansion-panel>

                        <v-expansion-panel title="¿Puedo modificar mi carrito?">
                            <v-expansion-panel-text>
                                Sí, desde la página de carrito puedes cambiar cantidades o eliminar productos.
                            </v-expansion-panel-text>
                        </v-expansion-panel>

                        <v-expansion-panel title="He olvidado mi contraseña, ¿qué hago?">
                            <v-expansion-panel-text>
                                Usa “¿Olvidaste tu contraseña?” en la pantalla de login para recuperar el acceso.
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card>

                <div class="mt-6">
                    <v-btn color="primary" prepend-icon="mdi-arrow-left" @click="goBack">
                        Volver
                    </v-btn>
                </div>

            </v-col>



            <v-col cols="12" md="5">
                <v-card class="pa-4" rounded="xl">
                    <h2 class="text-h6 font-weight-bold mb-3">Contacto</h2>

                    <v-list lines="two">
                        <v-list-item prepend-icon="mdi-email" title="Email" subtitle="soporte@ejemplo.com" />
                        <v-list-item prepend-icon="mdi-phone" title="Teléfono" subtitle="+34 600 000 000" />
                        <v-list-item prepend-icon="mdi-clock-outline" title="Horario" subtitle="L–V 09:00–18:00" />
                    </v-list>

                    <v-divider class="my-4" />

                    <p class="text-body-2">
                        (Más adelante conectaremos un formulario real con backend.)
                    </p>
                </v-card>


            </v-col>
        </v-row>
    </v-container>
</template>

import { useRouter } from "vue-router";

const router = useRouter();

function goBack() {
router.back();
}
