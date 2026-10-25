<script setup>
import { ref, onMounted } from "vue";
import { api, errorMessage, dateLabel } from "../api";

const props = defineProps({ id: { type: Number, required: true } });
const offer = ref(null);
const error = ref("");
const tab = ref("Información básica");
const tabs = ["Información básica", "Cronograma", "Documentos"];
onMounted(async () => {
  try {
    offer.value = (await api.get("/ofertas/" + props.id)).data.data;
  } catch (err) {
    error.value = errorMessage(err);
  }
});
</script>

<template>
  <a href="#/" class="back-link">← Volver al listado</a>
  <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
  <p v-else-if="!offer" role="status">Cargando oferta…</p>
  <template v-else>
    <div class="page-heading">
      <div>
        <p class="eyebrow">{{ offer.consecutivo }}</p>
        <h1>{{ offer.objeto }}</h1>
        <p class="page-description">Detalle de la oferta</p>
      </div>
      <a :href="'#/editar/' + id" class="btn btn-primary">Editar oferta</a>
    </div>
    <div class="surface">
      <nav class="nav nav-underline mb-4" aria-label="Secciones de la oferta">
        <button
          v-for="item in tabs"
          :key="item"
          type="button"
          class="nav-link"
          :class="{ active: tab === item }"
          :aria-current="tab === item ? 'page' : undefined"
          @click="tab = item"
        >
          {{ item }}
        </button>
      </nav>
      <dl v-if="tab === tabs[0]" class="detail-grid mb-0">
        <div class="full">
          <dt>Objeto</dt>
          <dd>{{ offer.objeto }}</dd>
        </div>
        <div class="full">
          <dt>Descripción</dt>
          <dd class="preserve-lines">{{ offer.descripcion }}</dd>
        </div>
        <div>
          <dt>Presupuesto</dt>
          <dd class="detail-amount">
            {{
              Number(offer.presupuesto).toLocaleString("es-CO", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })
            }}
            {{ offer.moneda }}
          </dd>
        </div>
        <div>
          <dt>Estado</dt>
          <dd>
            <span class="status">{{ offer.estado }}</span>
          </dd>
        </div>
        <div class="full">
          <dt>Actividad UNSPSC</dt>
          <dd>
            {{ offer.actividad.codigo_producto }} —
            {{ offer.actividad.producto }}
          </dd>
        </div>
      </dl>
      <dl v-else-if="tab === tabs[1]" class="detail-grid mb-0">
        <div>
          <dt>Inicio</dt>
          <dd>{{ dateLabel(offer.fecha_inicio, offer.hora_inicio) }}</dd>
        </div>
        <div>
          <dt>Cierre</dt>
          <dd>{{ dateLabel(offer.fecha_cierre, offer.hora_cierre) }}</dd>
        </div>
        <div class="full small text-secondary">
          Horas de Colombia (America/Bogota)
        </div>
      </dl>
      <div v-else>
        <p v-if="!offer.documentos.length" class="text-secondary mb-0">
          Esta oferta aún no tiene documentos. Puedes agregarlos al editarla.
        </p>
        <ul v-else class="document-list">
          <li v-for="doc in offer.documentos" :key="doc.id">
            <div>
              <strong>{{ doc.titulo }}</strong>
              <p class="mb-1 text-secondary">{{ doc.descripcion }}</p>
              <span class="small text-secondary">{{
                doc.nombre_original
              }}</span>
            </div>
            <a
              :href="'/api/documentos/' + doc.id + '/descargar'"
              class="btn btn-outline-primary btn-sm"
              >Descargar</a
            >
          </li>
        </ul>
      </div>
    </div>
  </template>
</template>
