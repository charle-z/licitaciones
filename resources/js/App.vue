<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import OfferList from "./components/OfferList.vue";
import OfferForm from "./components/OfferForm.vue";
import OfferDetail from "./components/OfferDetail.vue";

const hash = ref(location.hash);
const update = () => {
  hash.value = location.hash;
  window.scrollTo(0, 0);
};
onMounted(() => window.addEventListener("hashchange", update));
onUnmounted(() => window.removeEventListener("hashchange", update));
const route = computed(() => {
  if (hash.value === "#/crear") return { name: "crear" };
  const match = hash.value.match(/^#\/(detalle|editar)\/([1-9]\d*)$/);
  return match ? { name: match[1], id: Number(match[2]) } : { name: "lista" };
});
</script>

<template>
  <header class="site-header">
    <div
      class="container py-3 d-flex align-items-center justify-content-between"
    >
      <a href="#/" class="brand">Licitaciones</a>
      <span class="header-caption">Gestión de ofertas</span>
    </div>
  </header>
  <main class="container py-4 py-lg-5">
    <OfferList v-if="route.name === 'lista'" />
    <OfferForm v-else-if="route.name === 'crear'" :key="'crear'" />
    <OfferForm
      v-else-if="route.name === 'editar'"
      :id="route.id"
      :key="'editar' + route.id"
    />
    <OfferDetail v-else :id="route.id" :key="'detalle' + route.id" />
  </main>
</template>
