<template>
  <v-container class="py-10">
    <v-row justify="center">
      <v-col cols="12" md="10" lg="9">
        <v-chip color="primary" variant="tonal" label class="mb-4">
          Devoluciones
        </v-chip>

        <h1 class="text-h3 text-md-h2 font-weight-black mb-3">Cambios y devoluciones</h1>

        <p class="text-body-1 text-medium-emphasis mb-6">
          Contenido orientativo para el proyecto universitario. En un entorno real, estas condiciones
          se detallarían con precisión.
        </p>

        <v-expansion-panels variant="accordion">
          <v-expansion-panel>
            <v-expansion-panel-title>¿Cuál es el plazo de devolución?</v-expansion-panel-title>
            <v-expansion-panel-text class="text-medium-emphasis">
              Plazo orientativo: 14 días naturales desde la recepción del pedido.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>¿En qué estado debe estar el producto?</v-expansion-panel-title>
            <v-expansion-panel-text class="text-medium-emphasis">
              Sin usar, con etiquetas y en su embalaje original (si aplica).
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>¿Cómo solicito una devolución?</v-expansion-panel-title>
            <v-expansion-panel-text class="text-medium-emphasis">
              Cuando el sistema esté integrado, podrás iniciarla desde <strong>Mis pedidos</strong>.
              Por ahora, usa la página de <strong>Contacto</strong>.
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel>
            <v-expansion-panel-title>¿Cuándo recibiré el reembolso?</v-expansion-panel-title>
            <v-expansion-panel-text class="text-medium-emphasis">
              Orientativamente, entre 3 y 10 días laborables desde la recepción y revisión del producto.
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>

        <div class="d-flex flex-wrap ga-3 mt-8">
          <v-btn color="primary" prepend-icon="mdi-arrow-left" @click="router.back()">
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
</script>
