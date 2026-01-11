<template>
  <v-container class="py-10">
    <v-row justify="center">
      <v-col cols="12" md="10" lg="9">
        <v-chip color="primary" variant="tonal" label class="mb-4">
          Términos y condiciones
        </v-chip>

        <h1 class="text-h3 text-md-h2 font-weight-black mb-3">
          Términos y condiciones de uso
        </h1>

        <p class="text-body-1 text-medium-emphasis mb-6">
          Este sitio forma parte de un <strong>proyecto universitario</strong> (Universidad de Alicante, Ingeniería Informática)
          para la asignatura de <strong>Ingeniería Web</strong>. Algunas funcionalidades pueden estar simuladas o en desarrollo.
        </p>

        <v-alert type="info" variant="tonal" rounded="xl" class="mb-6">
          Contenido orientativo para el proyecto. No constituye asesoramiento legal.
        </v-alert>

        <v-expansion-panels variant="accordion" class="mb-8">
          <v-expansion-panel>
            <v-expansion-panel-title>1. Objeto del sitio</v-expansion-panel-title>
            <v-expansion-panel-text>
              La web muestra un catálogo de productos y simula funcionalidades de e-commerce
              (búsqueda, carrito, wishlist y pedidos) con fines educativos.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>2. Uso permitido</v-expansion-panel-title>
            <v-expansion-panel-text>
              El usuario se compromete a usar el sitio de forma lícita y a no realizar acciones que puedan
              dañar, saturar o comprometer la seguridad de la aplicación.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>3. Cuenta de usuario (si aplica)</v-expansion-panel-title>
            <v-expansion-panel-text>
              Si el sitio permite registro/inicio de sesión, el usuario es responsable de la confidencialidad
              de sus credenciales y del uso que se haga desde su cuenta.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>4. Productos, precios y disponibilidad</v-expansion-panel-title>
            <v-expansion-panel-text>
              La información mostrada puede ser simulada. Los precios, disponibilidad y descripciones
              pueden no corresponder con un comercio real.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>5. Pedidos y pagos</v-expansion-panel-title>
            <v-expansion-panel-text>
              Los flujos de pedido/pago pueden estar en desarrollo o ser demostrativos. Si existieran
              métodos de pago, se informarían claramente en el proceso de compra.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>6. Envíos y devoluciones</v-expansion-panel-title>
            <v-expansion-panel-text>
              En un entorno real se indicarían plazos, costes y condiciones. En este proyecto, esta información
              puede ser orientativa o simulada.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>7. Propiedad intelectual</v-expansion-panel-title>
            <v-expansion-panel-text>
              El contenido (diseño, textos, componentes y código) se usa con fines educativos. Si se incluyen
              recursos de terceros, se respetarán sus licencias y atribuciones cuando aplique.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>8. Limitación de responsabilidad</v-expansion-panel-title>
            <v-expansion-panel-text>
              Al ser un proyecto académico, no se garantiza disponibilidad continua ni ausencia de errores.
              No nos hacemos responsables de daños derivados del uso del sitio en un entorno no productivo.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>9. Cambios en los términos</v-expansion-panel-title>
            <v-expansion-panel-text>
              Podemos actualizar estos términos durante el desarrollo del proyecto. Se recomienda revisarlos
              periódicamente.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>10. Contacto</v-expansion-panel-title>
            <v-expansion-panel-text>
              Para dudas o incidencias, usa la página de <strong>Contacto</strong>.
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>

        <div class="d-flex flex-wrap ga-3">
          <v-btn color="primary" prepend-icon="mdi-arrow-left" @click="goBack">
            Volver
          </v-btn>

          <v-btn variant="text" to="/contacto" prepend-icon="mdi-email-outline">
            Contacto
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
