<template>
  <v-container class="py-10">
    <v-row justify="center">
      <v-col cols="12" md="10" lg="9">
        <v-chip color="primary" variant="tonal" label class="mb-4">
          Envíos
        </v-chip>

        <h1 class="text-h3 text-md-h2 font-weight-black mb-3">Información de envíos</h1>

        <p class="text-body-1 text-medium-emphasis mb-6">
          Esta página forma parte de un <strong>proyecto universitario</strong> (UA, Ingeniería Informática – Ingeniería Web).
          La información es orientativa y puede cambiar durante el desarrollo.
        </p>

        <v-row dense>
          <v-col cols="12" md="6">
            <v-card variant="outlined" rounded="xl" class="pa-6 h-100">
              <div class="d-flex align-center ga-3 mb-3">
                <v-icon icon="mdi-truck-fast-outline" color="primary" />
                <h2 class="text-h6 font-weight-bold">Plazos estimados</h2>
              </div>
              <ul class="text-body-2 text-medium-emphasis pl-4">
                <li>Estándar: 3–5 días laborables.</li>
                <li>Express: 24–48h (si está disponible).</li>
                <li>Los plazos pueden variar por zona y disponibilidad.</li>
              </ul>
            </v-card>
          </v-col>

          <v-col cols="12" md="6">
            <v-card variant="outlined" rounded="xl" class="pa-6 h-100">
              <div class="d-flex align-center ga-3 mb-3">
                <v-icon icon="mdi-currency-eur" color="primary" />
                <h2 class="text-h6 font-weight-bold">Costes</h2>
              </div>
              <ul class="text-body-2 text-medium-emphasis pl-4">
                <li>Envío estándar: coste orientativo según pedido.</li>
                <li>Envío gratis: a partir de un mínimo (si aplica).</li>
                <li>En un proyecto real, se mostraría en checkout.</li>
              </ul>
            </v-card>
          </v-col>

          <v-col cols="12">
            <v-card variant="outlined" rounded="xl" class="pa-6">
              <div class="d-flex align-center ga-3 mb-3">
                <v-icon icon="mdi-map-marker-path" color="primary" />
                <h2 class="text-h6 font-weight-bold">Seguimiento</h2>
              </div>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Cuando el sistema esté integrado, podrás ver el estado del envío desde <strong>Mis pedidos</strong>.
                Por ahora, esta funcionalidad puede estar simulada.
              </p>
            </v-card>
          </v-col>
        </v-row>

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
