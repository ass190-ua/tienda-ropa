<template>
    <transition name="scrollfab">
        <v-btn v-if="show && !ui.cartOpen" icon color="primary" variant="flat" rounded="circle" class="scroll-top"
            aria-label="Subir arriba" @click="scrollTop">
            <v-icon icon="mdi-arrow-up" />
        </v-btn>
    </transition>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useUiStore } from '../stores/ui'

const ui = useUiStore()
const show = ref(false)

function onScroll() {
    show.value = window.scrollY > 420
}

function scrollTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll)
})
</script>

<style scoped>
.scroll-top {
    position: fixed;
    right: 22px;
    bottom: 22px;
    width: 54px;
    height: 54px;
    min-width: 54px;
    z-index: 1200;
    box-shadow: 0 14px 34px rgba(0, 0, 0, 0.18);
}

.scrollfab-enter-active,
.scrollfab-leave-active {
    transition: opacity 420ms ease, transform 420ms ease;
}

.scrollfab-enter-from,
.scrollfab-leave-to {
    opacity: 0;
    transform: translateY(10px) scale(0.96);
}

.scrollfab-enter-to,
.scrollfab-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}
</style>
