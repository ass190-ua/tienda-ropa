<template>
    <v-container class="py-8">
        <!-- Header mini (sin depender de layout global) -->

        <v-row class="mt-2" align="start">
            <!-- LEFT -->
            <v-col cols="12" lg="7">
                <v-chip color="primary" variant="tonal" label class="mb-4">404</v-chip>

                <h1 class="text-h3 text-md-h2 font-weight-black mb-3">
                    Ups… esta página no existe
                </h1>

                <p class="text-body-1 text-medium-emphasis mb-6">
                    Puede que el enlace esté mal, que el producto ya no esté disponible o que la página se haya movido.
                </p>

                <div class="d-flex flex-wrap ga-3 mb-6">
                    <v-btn color="primary" prepend-icon="mdi-home" @click="goHome">
                        Volver a inicio
                    </v-btn>

                    <v-btn variant="outlined" prepend-icon="mdi-arrow-left" @click="goBack">
                        Atrás
                    </v-btn>

                    <v-btn variant="text" prepend-icon="mdi-magnify" @click="goSearchPage">
                        Ir a buscar
                    </v-btn>
                </div>



                <v-alert v-if="showDebug" class="mt-6" type="info" variant="tonal" rounded="xl">
                    Ruta: <strong>{{ currentPath }}</strong>
                </v-alert>
            </v-col>

            <!-- RIGHT -->
            <v-col cols="12" lg="5">
                <v-card rounded="xl" variant="tonal" color="primary" class="pa-6 mb-4">
                    <div class="d-flex align-center ga-3">
                        <v-avatar color="primary" variant="outlined" size="56">
                            <v-icon icon="mdi-shopping" size="28" />
                        </v-avatar>

                        <div>
                            <div class="text-h6 font-weight-bold">¿Te has perdido?</div>
                            <div class="text-body-2 text-medium-emphasis">
                                Vuelve a explorar el catálogo o revisa tu carrito y favoritos.
                            </div>
                        </div>
                    </div>
                </v-card>

                <v-card rounded="xl" variant="outlined" class="pa-4 mb-4">
                    <div class="text-subtitle-1 font-weight-bold mb-2">Consejo rápido</div>
                    <div class="text-body-2 text-medium-emphasis">
                        Si estabas buscando un producto, prueba a entrar desde <strong>Buscar</strong> o filtra por
                        categoría.
                    </div>
                </v-card>

                <v-card rounded="xl" variant="outlined" class="pa-2">
                    <v-list density="comfortable">
                        <template v-if="isAuthenticated">
                            <v-list-item title="Ir al carrito" prepend-icon="mdi-shopping-outline" to="/cart" link />
                            <v-list-item title="Ver wishlist" prepend-icon="mdi-heart-outline" to="/wishlist" link />
                            <v-list-item title="Mis pedidos" prepend-icon="mdi-receipt-text-outline" to="/orders"
                                link />
                        </template>

                        <template v-else>
                            <v-list-item title="Ver novedades" prepend-icon="mdi-newspaper-variant-outline"
                                to="/novedades" link />
                            <v-list-item title="Volver a inicio" prepend-icon="mdi-home-outline" to="/" link />
                            <v-list-item title="Iniciar sesión" prepend-icon="mdi-login" to="/login" link />
                        </template>
                    </v-list>
                </v-card>

            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const q = ref("");

const isAuthenticated = computed(() => auth.isAuthenticated);

const showDebug = false;
const currentPath = computed(() => route.fullPath || "/");

const breadcrumbs = computed(() => [
    { title: "Inicio", to: "/" },
    { title: "404", disabled: true },
]);

function goHome() {
    router.push("/");
}

function goBack() {
    router.back();
}

function goSearch() {
    if (!q.value) return;
    router.push({ path: "/search", query: { q: q.value } });
}

function goSearchEmpty() {
    router.push({ path: "/search" });
}

function goCat(cat) {
    router.push({ path: "/search", query: { categoria: cat } });
}

</script>

<style scoped></style>
