<template>
    <v-container class="py-10">
        <v-row justify="center">
            <v-col cols="12" md="10" lg="9">
                <v-chip color="primary" variant="tonal" label class="mb-4">
                    Sobre nosotros
                </v-chip>

                <h1 class="text-h3 text-md-h2 font-weight-black mb-3">
                    TiendaModa
                </h1>

                <p class="text-body-1 text-medium-emphasis mb-8">
                    Esta tienda online es un <strong>proyecto académico</strong> desarrollado por un equipo de
                    <strong>4 integrantes</strong> de la <strong>Universidad de Alicante (UA)</strong>, dentro del grado
                    de
                    <strong>Ingeniería Informática</strong>. Forma parte de la asignatura <strong>Ingeniería
                        Web</strong>,
                    donde aplicamos buenas prácticas de desarrollo frontend y backend.
                </p>

                <v-row class="mb-8" dense>
                    <v-col cols="12" md="6">
                        <v-card rounded="xl" variant="outlined" class="pa-5 h-100">
                            <div class="d-flex align-center ga-3 mb-2">
                                <v-avatar color="primary" variant="tonal">
                                    <v-icon icon="mdi-account-group-outline" />
                                </v-avatar>
                                <h2 class="text-h6 font-weight-bold mb-0">Quiénes somos</h2>
                            </div>

                            <p class="text-body-2 text-medium-emphasis mb-0">
                                Somos un equipo de estudiantes que está construyendo una tienda online moderna con un
                                enfoque en
                                usabilidad, diseño consistente y arquitectura limpia. Nuestro objetivo es aprender a
                                crear un
                                producto web completo de principio a fin.
                            </p>
                        </v-card>
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-card rounded="xl" variant="outlined" class="pa-5 h-100">
                            <div class="d-flex align-center ga-3 mb-2">
                                <v-avatar color="primary" variant="tonal">
                                    <v-icon icon="mdi-target" />
                                </v-avatar>
                                <h2 class="text-h6 font-weight-bold mb-0">Para qué sirve</h2>
                            </div>

                            <p class="text-body-2 text-medium-emphasis mb-0">
                                El proyecto simula una experiencia real de compra: navegación por catálogo, búsqueda,
                                carrito,
                                wishlist y gestión de pedidos. A nivel técnico, nos permite practicar integración
                                frontend–backend,
                                persistencia en base de datos y diseño por capas en el servidor.
                            </p>
                        </v-card>
                    </v-col>
                </v-row>

                <v-card rounded="xl" variant="outlined" class="pa-6 mb-8">
                    <h2 class="text-h6 font-weight-bold mb-4">Qué estamos construyendo</h2>

                    <v-list density="comfortable">
                        <v-list-item prepend-icon="mdi-storefront-outline" title="Catálogo de productos"
                            subtitle="Páginas de listado y detalle con información clara y navegación fluida." />
                        <v-list-item prepend-icon="mdi-magnify" title="Búsqueda y filtros"
                            subtitle="Herramientas para encontrar productos por texto, categoría o atributos." />
                        <v-list-item prepend-icon="mdi-cart-outline" title="Carrito y checkout"
                            subtitle="Gestión de cantidades, totales y flujo de compra." />
                        <v-list-item prepend-icon="mdi-heart-outline" title="Wishlist"
                            subtitle="Guardar productos favoritos para consultarlos más tarde." />
                        <v-list-item prepend-icon="mdi-receipt-text-outline" title="Pedidos"
                            subtitle="Historial, estado y detalles de pedidos del usuario." />
                    </v-list>
                </v-card>

                <v-row class="mb-8" dense>
                    <v-col cols="12" md="6">
                        <v-card rounded="xl" variant="tonal" color="primary" class="pa-5 h-100">
                            <div class="d-flex align-center ga-3 mb-2">
                                <v-avatar color="primary" variant="outlined">
                                    <v-icon icon="mdi-layers-outline" />
                                </v-avatar>
                                <h2 class="text-h6 font-weight-bold mb-0">Arquitectura</h2>
                            </div>

                            <p class="text-body-2 text-medium-emphasis mb-3">
                                En el backend seguimos un enfoque por capas para mantener el código claro y mantenible:
                            </p>

                            <v-chip class="me-2 mb-2" variant="outlined">Controladores</v-chip>
                            <v-chip class="me-2 mb-2" variant="outlined">Servicios</v-chip>
                            <v-chip class="me-2 mb-2" variant="outlined">Repositorios</v-chip>
                            <v-chip class="me-2 mb-2" variant="outlined">Modelos</v-chip>

                            <p class="text-body-2 text-medium-emphasis mt-3 mb-0">
                                Esto nos ayuda a separar responsabilidades y a facilitar pruebas y evolución del
                                proyecto.
                            </p>
                        </v-card>
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-card rounded="xl" variant="outlined" class="pa-5 h-100">
                            <div class="d-flex align-center ga-3 mb-2">
                                <v-avatar color="primary" variant="tonal">
                                    <v-icon icon="mdi-information-outline" />
                                </v-avatar>
                                <h2 class="text-h6 font-weight-bold mb-0">Nota</h2>
                            </div>

                            <p class="text-body-2 text-medium-emphasis mb-0">
                                Este sitio forma parte de un proyecto formativo. Algunas funciones pueden estar en
                                desarrollo,
                                y el contenido mostrado puede ser simulado mientras se completa la integración con
                                backend y base de datos.
                            </p>
                        </v-card>
                    </v-col>
                </v-row>

                <div class="d-flex flex-wrap ga-3">
                    <v-btn color="primary" prepend-icon="mdi-arrow-left" @click="goBack">
                        Volver
                    </v-btn>

                    <v-btn variant="text" to="/ayuda" prepend-icon="mdi-lifebuoy">
                        Ayuda / FAQ
                    </v-btn>
                </div>

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
