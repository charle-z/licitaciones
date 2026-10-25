<script setup>
import { ref, reactive, onMounted } from "vue";
import { api, errorMessage, dateLabel } from "../api";

const filters = reactive({ consecutivo: "", objeto: "", descripcion: "" });
const applied = ref({});
const rows = ref([]);
const page = ref(1);
const total = ref(0);
const busy = ref(false);
const exporting = ref(false);
const error = ref("");
async function load(next = 1, apply = false) {
  if (busy.value) return;
  if (apply) applied.value = { ...filters };
  busy.value = true;
  error.value = "";
  try {
    const { data } = await api.get("/ofertas", {
      params: { ...applied.value, pagina: next },
    });
    rows.value = data.data;
    total.value = data.total;
    page.value = data.pagina;
  } catch (err) {
    error.value = errorMessage(err);
  } finally {
    busy.value = false;
  }
}
function clear() {
  Object.keys(filters).forEach((key) => {
    filters[key] = "";
  });
  load(1, true);
}
async function download() {
  exporting.value = true;
  error.value = "";
  try {
    const response = await api.get("/ofertas/exportar", {
      params: applied.value,
      responseType: "blob",
    });
    const url = URL.createObjectURL(response.data);
    const link = document.createElement("a");
    link.href = url;
    link.download = "ofertas.xlsx";
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  } catch {
    error.value = "No se pudo generar el Excel. Inténtalo de nuevo.";
  } finally {
    exporting.value = false;
  }
}
onMounted(() => load());
</script>

<template>
  <div class="page-heading">
    <div>
      <p class="eyebrow">Ofertas</p>
      <h1>Listado de ofertas</h1>
      <p class="page-description">Consulta, edita y exporta las ofertas registradas.</p>
    </div>
    <a href="#/crear" class="btn btn-primary">Crear oferta</a>
  </div>
  <form class="surface filter-form" @submit.prevent="load(1, true)">
    <div class="row g-3 align-items-end">
      <div class="col-md-3">
        <label for="f-consecutivo" class="form-label">Consecutivo</label
        ><input
          id="f-consecutivo"
          v-model="filters.consecutivo"
          maxlength="40"
          class="form-control"
          placeholder="PO-0001-26"
        />
      </div>
      <div class="col-md-4">
        <label for="f-objeto" class="form-label">Objeto</label
        ><input
          id="f-objeto"
          v-model="filters.objeto"
          maxlength="150"
          class="form-control"
        />
      </div>
      <div class="col-md-5">
        <label for="f-descripcion" class="form-label">Descripción</label
        ><input
          id="f-descripcion"
          v-model="filters.descripcion"
          maxlength="400"
          class="form-control"
        />
      </div>
    </div>
    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-primary" :disabled="busy">Buscar</button
      ><button
        type="button"
        class="btn btn-outline-secondary"
        :disabled="busy"
        @click="clear"
      >
        Limpiar
      </button>
    </div>
  </form>
  <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
  <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
    <p class="mb-0 text-secondary" aria-live="polite">
      {{
        busy
          ? "Cargando ofertas…"
          : total +
            (total === 1 ? " oferta encontrada" : " ofertas encontradas")
      }}
    </p>
    <button
      class="btn btn-outline-primary"
      :disabled="exporting || busy"
      @click="download"
    >
      {{ exporting ? "Generando…" : "Exportar a Excel" }}
    </button>
  </div>
  <div class="surface p-0 overflow-hidden">
    <div class="table-responsive">
      <table class="table offer-table align-middle mb-0">
        <caption class="visually-hidden">Ofertas registradas y acciones disponibles</caption>
        <thead>
          <tr>
            <th scope="col">Consecutivo</th>
            <th scope="col">Objeto</th>
            <th scope="col">Descripción</th>
            <th scope="col">Inicio</th>
            <th scope="col">Cierre</th>
            <th scope="col">Estado</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="offer in rows" :key="offer.id">
            <td class="text-nowrap fw-semibold">{{ offer.consecutivo }}</td>
            <td class="offer-object">{{ offer.objeto }}</td>
            <td class="offer-description text-secondary">{{ offer.descripcion }}</td>
            <td class="text-nowrap small">
              {{ dateLabel(offer.fecha_inicio) }}
            </td>
            <td class="text-nowrap small">
              {{ dateLabel(offer.fecha_cierre) }}
            </td>
            <td>
              <span class="status">{{ offer.estado }}</span>
            </td>
            <td class="text-nowrap">
              <a
                :href="'#/detalle/' + offer.id"
                class="me-3"
                :aria-label="'Ver ' + offer.consecutivo"
                >Ver</a
              ><a
                :href="'#/editar/' + offer.id"
                :aria-label="'Editar ' + offer.consecutivo"
                >Editar</a
              >
            </td>
          </tr>
          <tr v-if="!rows.length && !busy">
            <td colspan="7" class="text-center py-5 text-secondary">
              No hay ofertas que coincidan con la búsqueda.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <nav
    class="d-flex justify-content-between align-items-center mt-3"
    aria-label="Paginación"
  >
    <span class="small text-secondary"
      >Página {{ page }} de {{ Math.max(1, Math.ceil(total / 10)) }}</span
    >
    <div class="d-flex gap-2">
      <button
        class="btn btn-outline-secondary btn-sm"
        :disabled="busy || page <= 1"
        @click="load(page - 1)"
      >
        Anterior</button
      ><button
        class="btn btn-outline-secondary btn-sm"
        :disabled="busy || page * 10 >= total"
        @click="load(page + 1)"
      >
        Siguiente
      </button>
    </div>
  </nav>
  <p class="small text-secondary mt-4 mb-0">El Excel incluye todas las coincidencias de los filtros aplicados.</p>
</template>
