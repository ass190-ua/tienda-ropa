<template>
    <v-container class="py-10">
        <v-row justify="center">
            <v-col cols="12" md="10" lg="9">
                <v-chip color="primary" variant="tonal" label class="mb-4">
                    Contacto
                </v-chip>

                <h1 class="text-h3 text-md-h2 font-weight-black mb-3">
                    Contáctanos
                </h1>

                <p class="text-body-1 text-medium-emphasis mb-8">
                    Esta web es un proyecto universitario. Si tienes dudas sobre el funcionamiento, errores o
                    sugerencias,
                    puedes escribirnos desde aquí.
                </p>

                <v-row dense>
                    <!-- Formulario -->
                    <v-col cols="12" md="7">
                        <v-card rounded="xl" variant="outlined" class="pa-6 h-100">
                            <h2 class="text-h6 font-weight-bold mb-4">Envíanos un mensaje</h2>

                            <v-form ref="formRef" @submit.prevent="submit">
                                <v-progress-linear v-if="loading" indeterminate class="mb-4" rounded />

                                <v-text-field v-model.trim="form.name" label="Nombre" prepend-inner-icon="mdi-account"
                                    variant="outlined" class="mb-3" :rules="[rules.required]" />

                                <v-text-field v-model.trim="form.email" label="Email" type="email"
                                    prepend-inner-icon="mdi-email-outline" variant="outlined" class="mb-3"
                                    :rules="[rules.required, rules.email]" />

                                <v-select v-model="form.reason" :items="reasons" label="Motivo"
                                    prepend-inner-icon="mdi-help-circle-outline" variant="outlined" class="mb-3"
                                    :rules="[rules.required]" />

                                <v-textarea v-model.trim="form.message" label="Mensaje"
                                    prepend-inner-icon="mdi-message-text-outline" variant="outlined" auto-grow rows="4"
                                    class="mb-4" :rules="[rules.required, rules.min10]" />

                                <div class="d-flex flex-wrap ga-3">
                                    <v-btn color="primary" type="submit" prepend-icon="mdi-send" :loading="loading" :disabled="loading">
                                        Enviar
                                    </v-btn>

                                    <v-btn variant="outlined" prepend-icon="mdi-broom" @click="resetAll" :disabled="loading">
                                        Limpiar
                                    </v-btn>

                                    <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="goBack" :disabled="loading">
                                        Volver
                                    </v-btn>
                                </div>

                                <v-alert v-if="sent" type="success" variant="tonal" rounded="xl" class="mt-4">
                                    Mensaje enviado correctamente. Gracias por contactarnos.
                                </v-alert>
                            </v-form>
                        </v-card>
                    </v-col>

                    <!-- Info -->
                    <v-col cols="12" md="5">
                        <v-card rounded="xl" variant="outlined" class="pa-6 mb-4">
                            <h2 class="text-h6 font-weight-bold mb-4">Información</h2>

                            <v-list density="comfortable">
                                <v-list-item prepend-icon="mdi-school-outline" title="Universidad de Alicante"
                                    subtitle="Grado en Ingeniería Informática" />
                                <v-list-item prepend-icon="mdi-book-open-page-variant-outline" title="Asignatura"
                                    subtitle="Ingeniería Web" />
                                <v-list-item prepend-icon="mdi-map-marker-outline" title="Ubicación"
                                    subtitle="Alicante, España" />
                            </v-list>
                        </v-card>

                        <v-card rounded="xl" variant="tonal" color="primary" class="pa-6">
                            <h2 class="text-h6 font-weight-bold mb-2">Sugerencia</h2>
                            <p class="text-body-2 text-medium-emphasis mb-0">
                                Si estás reportando un error, indica qué estabas haciendo y en qué página ocurrió.
                            </p>
                        </v-card>
                    </v-col>
                </v-row>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { reactive, ref, nextTick, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import axios from 'axios';

const router = useRouter();

const reasons = [
    "Duda general",
    "Problema con mi cuenta",
    "Error en la web",
    "Sugerencia de mejora",
    "Otro",
];

const form = reactive({
    name: "",
    email: "",
    reason: "",
    message: "",
});

const formRef = ref(null)
const sent = ref(false);
let sentTimer = null;
const loading = ref(false)


const rules = {
    required: (v) => !!v || "Este campo es obligatorio",
    email: (v) => /.+@.+\..+/.test(v) || "Email no válido",
    min10: (v) => (v && v.length >= 10) || "Escribe al menos 10 caracteres",
};


async function submit() {
    sent.value = false
    loading.value = true

    try {
        await axios.get('/sanctum/csrf-cookie')
        await axios.post('/api/contact', {
            name: form.name,
            email: form.email,
            reason: form.reason,
            message: form.message,
        })

        sent.value = true
        if (sentTimer) clearTimeout(sentTimer)

        sentTimer = setTimeout(() => {
            sent.value = false
            sentTimer = null
        }, 3000)
        await reset()
    } catch (e) {
        sent.value = false
        alert('Error al enviar el mensaje.')
    } finally {
        loading.value = false
    }
}



onBeforeUnmount(() => {
    if (sentTimer) clearTimeout(sentTimer)
})



async function reset() {
    form.name = ""
    form.email = ""
    form.reason = ""
    form.message = ""

    await nextTick()
    formRef.value?.resetValidation?.()
}

async function resetAll() {
    await reset()
    sent.value = false
}



function goBack() {
    router.back();
}
</script>
