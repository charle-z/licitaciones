<script setup>
import { reactive, ref, computed, onMounted } from "vue";
import { api, errorMessage } from "../api";
import ActivitySelect from "./ActivitySelect.vue";
import DocumentEditor from "./DocumentEditor.vue";

const props = defineProps({ id: { type: Number, default: null } });
const fields = reactive({
  objeto: "",
  descripcion: "",
  moneda: "COP",
  presupuesto: "",
  actividad_id: "",
  fecha_inicio: "",
  hora_inicio: "",
  fecha_cierre: "",
  hora_cierre: "",
});
const offer = ref(null);
const pending = ref([]);
const loading = ref(Boolean(props.id));
const saving = ref(false);
const error = ref("");
const errors = ref({});
function validDate(value) {
  if (!/^[1-9]\d{3}-\d{2}-\d{2}$/.test(value)) return false;
  const date = new Date(value + "T00:00:00Z");
  return (
    !Number.isNaN(date.getTime()) &&
    date.toISOString().substring(0, 10) === value
  );
}
const formValid = computed(() => {
  const validText =
    fields.objeto.trim().length > 0 &&
    fields.objeto.length <= 150 &&
    fields.descripcion.trim().length > 0 &&
    fields.descripcion.length <= 400;
  const validBudget =
    /^\d{1,13}(?:\.\d{1,2})?$/.test(fields.presupuesto) &&
    /[1-9]/.test(fields.presupuesto);
  const validSchedule =
    validDate(fields.fecha_inicio) &&
    validDate(fields.fecha_cierre) &&
    /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(fields.hora_inicio) &&
    /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(fields.hora_cierre) &&
    `${fields.fecha_inicio}T${fields.hora_inicio}` <
      `${fields.fecha_cierre}T${fields.hora_cierre}`;
  const validDocuments =
    !props.id ||
    Boolean(offer.value?.documentos.length || pending.value.length);
  return (
    validText &&
    validBudget &&
    validSchedule &&
    validDocuments &&
    ["COP", "USD", "EUR"].includes(fields.moneda) &&
    Number(fields.actividad_id) > 0
  );
});
function updateBudget(event) {
  const value = event.target.value;
  if (/^\d{0,13}(?:\.\d{0,2})?$/.test(value)) fields.presupuesto = value;
  else event.target.value = fields.presupuesto;
}
onMounted(async () => {
  if (!props.id) return;
  try {
    offer.value = (await api.get("/ofertas/" + props.id)).data.data;
    Object.keys(fields).forEach((key) => {
      fields[key] = offer.value[key];
    });
    fields.hora_inicio = fields.hora_inicio.substring(0, 5);
    fields.hora_cierre = fields.hora_cierre.substring(0, 5);
  } catch (err) {
    error.value = errorMessage(err);
  } finally {
    loading.value = false;
  }
});
async function save() {
  if (saving.value || !formValid.value) return;
  error.value = "";
  errors.value = {};
  if (!fields.objeto.trim() || !fields.descripcion.trim()) {
    error.value =
      "Completa el objeto y la descripción sin dejar solo espacios.";
    return;
  }
  if (
    `${fields.fecha_inicio}T${fields.hora_inicio}` >=
    `${fields.fecha_cierre}T${fields.hora_cierre}`
  ) {
    error.value = "La fecha y hora de cierre deben ser posteriores al inicio.";
    return;
  }
  if (props.id && !offer.value.documentos.length && !pending.value.length) {
    error.value = "Agrega al menos un documento para guardar la edición.";
    return;
  }
  saving.value = true;
  try {
    let payload = { ...fields };
    if (props.id) {
      payload = new FormData();
      Object.entries(fields).forEach(([key, value]) =>
        payload.append(key, value),
      );
      payload.append(
        "documentos",
        JSON.stringify(
          pending.value.map((doc) => ({
            titulo: doc.titulo,
            descripcion: doc.descripcion,
          })),
        ),
      );
      pending.value.forEach((doc) => payload.append("archivos[]", doc.file));
    }
    const { data } = await api.post(
      "/ofertas" + (props.id ? "/" + props.id : ""),
      payload,
    );
    location.hash = "#/detalle/" + data.data.id;
  } catch (err) {
    error.value = errorMessage(err);
    errors.value = err.response?.data?.errors || {};
    window.scrollTo(0, 0);
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <a :href="id ? '#/detalle/' + id : '#/'" class="back-link"
    >← {{ id ? "Volver al detalle" : "Volver al listado" }}</a
  >
  <div class="page-heading">
    <div>
      <p class="eyebrow">{{ offer?.consecutivo || "Nueva oferta" }}</p>
      <h1>{{ id ? "Editar oferta" : "Crear oferta" }}</h1>
    </div>
    <span v-if="offer" class="status">{{ offer.estado }}</span>
  </div>
  <div v-if="error" class="alert alert-danger" role="alert">
    {{ error }}
    <ul v-if="Object.keys(errors).length" class="mb-0 mt-2">
      <li v-for="(message, key) in errors" :key="key">{{ message }}</li>
    </ul>
  </div>
  <p v-if="loading" role="status">Cargando oferta…</p>
  <form v-else-if="!id || offer" class="offer-form" @submit.prevent="save">
    <fieldset :disabled="saving">
      <section class="surface mb-4" aria-labelledby="basic-heading">
        <h2 id="basic-heading">Información básica</h2>
        <p class="text-secondary small mb-4">
          Los campos con * son obligatorios.
        </p>
        <div class="mb-3">
          <label for="objeto" class="form-label">Objeto *</label
          ><input
            id="objeto"
            v-model="fields.objeto"
            required
            maxlength="150"
            class="form-control"
            :class="{ 'is-invalid': errors.objeto }"
          />
          <div class="form-text text-end">{{ fields.objeto.length }}/150</div>
        </div>
        <div class="mb-4">
          <label for="descripcion" class="form-label">Descripción *</label
          ><textarea
            id="descripcion"
            v-model="fields.descripcion"
            required
            maxlength="400"
            rows="3"
            class="form-control"
            :class="{ 'is-invalid': errors.descripcion }"
          ></textarea>
          <div class="form-text text-end">
            {{ fields.descripcion.length }}/400
          </div>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-sm-4">
            <label for="moneda" class="form-label">Moneda *</label
            ><select
              id="moneda"
              v-model="fields.moneda"
              class="form-select"
              required
            >
              <option>COP</option>
              <option>USD</option>
              <option>EUR</option>
            </select>
          </div>
          <div class="col-sm-8">
            <label for="presupuesto" class="form-label">Presupuesto *</label
            ><input
              id="presupuesto"
              :value="fields.presupuesto"
              @input="updateBudget"
              type="text"
              inputmode="decimal"
              pattern="[0-9]{1,13}(\.[0-9]{1,2})?"
              required
              class="form-control"
              placeholder="1500000.00"
              aria-describedby="presupuesto-ayuda"
            />
            <div id="presupuesto-ayuda" class="form-text">
              Mayor que cero. Sin separadores de miles y con punto para los
              decimales.
            </div>
          </div>
        </div>
        <ActivitySelect
          v-model="fields.actividad_id"
          :selected="offer?.actividad"
        />
      </section>
      <section class="surface mb-4" aria-labelledby="schedule-heading">
        <h2 id="schedule-heading">Cronograma</h2>
        <p class="text-secondary small mb-4">
          Horas de Colombia (America/Bogota), formato de 24 horas.
        </p>
        <div class="row g-3">
          <div class="col-sm-6 col-xl-3">
            <label for="fecha_inicio" class="form-label"
              >Fecha de inicio *</label
            ><input
              id="fecha_inicio"
              v-model="fields.fecha_inicio"
              type="date"
              min="1000-01-01"
              max="9999-12-31"
              required
              class="form-control"
            />
          </div>
          <div class="col-sm-6 col-xl-3">
            <label for="hora_inicio" class="form-label">Hora de inicio *</label
            ><input
              id="hora_inicio"
              v-model="fields.hora_inicio"
              type="time"
              required
              class="form-control"
            />
          </div>
          <div class="col-sm-6 col-xl-3">
            <label for="fecha_cierre" class="form-label"
              >Fecha de cierre *</label
            ><input
              id="fecha_cierre"
              v-model="fields.fecha_cierre"
              type="date"
              min="1000-01-01"
              max="9999-12-31"
              required
              class="form-control"
            />
          </div>
          <div class="col-sm-6 col-xl-3">
            <label for="hora_cierre" class="form-label">Hora de cierre *</label
            ><input
              id="hora_cierre"
              v-model="fields.hora_cierre"
              type="time"
              required
              class="form-control"
            />
          </div>
        </div>
      </section>
      <section v-if="id" class="surface mb-4" aria-label="Documentos adjuntos">
        <DocumentEditor
          :existing="offer.documentos"
          :pending="pending"
          @add="pending.push($event)"
          @remove="pending.splice($event, 1)"
        />
      </section>
      <p v-if="!formValid" class="small text-secondary text-end">
        Completa los campos obligatorios{{
          id ? " y agrega al menos un documento" : ""
        }}
        para guardar.
      </p>
      <div class="d-flex justify-content-end gap-2">
        <a
          :href="id ? '#/detalle/' + id : '#/'"
          class="btn btn-outline-secondary"
          :class="{ disabled: saving }"
          >Cancelar</a
        ><button class="btn btn-primary" :disabled="saving || !formValid">
          {{ saving ? "Guardando…" : id ? "Guardar cambios" : "Crear oferta" }}
        </button>
      </div>
    </fieldset>
  </form>
</template>
