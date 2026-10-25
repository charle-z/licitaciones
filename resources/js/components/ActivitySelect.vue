<script setup>
import { ref, onMounted, onUnmounted, watch } from "vue";
import { api, errorMessage } from "../api";

const props = defineProps({
  modelValue: { type: [String, Number], default: "" },
  selected: { type: Object, default: null },
});
const emit = defineEmits(["update:modelValue"]);
const query = ref("");
const activities = ref([]);
const chosen = ref(props.selected);
const busy = ref(false);
const error = ref("");
let timer;
let controller;
async function search() {
  controller?.abort();
  const current = new AbortController();
  controller = current;
  busy.value = true;
  error.value = "";
  try {
    const { data } = await api.get("/actividades", {
      params: { q: query.value },
      signal: current.signal,
    });
    activities.value = data.data;
  } catch (err) {
    if (!current.signal.aborted) error.value = errorMessage(err);
  } finally {
    if (controller === current) busy.value = false;
  }
}
watch(query, () => {
  clearTimeout(timer);
  busy.value = true;
  timer = setTimeout(search, 250);
});
watch(
  () => props.selected,
  (value) => {
    chosen.value = value;
  },
);
function select(event) {
  const id = Number(event.target.value);
  chosen.value =
    activities.value.find((item) => item.id === id) ||
    (chosen.value?.id === id ? chosen.value : null);
  emit("update:modelValue", event.target.value);
}
onMounted(search);
onUnmounted(() => {
  clearTimeout(timer);
  controller?.abort();
});
</script>

<template>
  <label for="actividad-busqueda" class="form-label"
    >Actividad UNSPSC <span class="text-danger">*</span></label
  >
  <input
    id="actividad-busqueda"
    v-model="query"
    class="form-control mb-2"
    maxlength="100"
    placeholder="Buscar por código o nombre del producto"
    aria-describedby="actividad-ayuda"
  />
  <label for="actividad" class="visually-hidden">Seleccionar actividad</label>
  <select
    id="actividad"
    class="form-select"
    :value="modelValue"
    :disabled="busy || !!error || (!activities.length && !chosen)"
    required
    @change="select"
  >
    <option value="">
      {{ busy ? "Cargando actividades…" : "Selecciona una actividad" }}
    </option>
    <option
      v-if="chosen && !activities.some((item) => item.id === chosen.id)"
      :value="chosen.id"
    >
      {{ chosen.codigo_producto }} — {{ chosen.producto }}
    </option>
    <option
      v-for="activity in activities"
      :key="activity.id"
      :value="activity.id"
    >
      {{ activity.codigo_producto }} — {{ activity.producto }}
    </option>
  </select>
  <p id="actividad-ayuda" class="form-text mb-0" aria-live="polite">
    {{
      error ||
      (busy
        ? "Consultando el catálogo…"
        : "Se muestran hasta 30 coincidencias. Escribe para afinar la búsqueda.")
    }}
  </p>
</template>
