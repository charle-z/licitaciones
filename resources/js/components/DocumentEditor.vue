<script setup>
import { ref, reactive } from "vue";

defineProps({
  existing: { type: Array, default: () => [] },
  pending: { type: Array, default: () => [] },
});
const emit = defineEmits(["add", "remove"]);
const dialog = ref(null);
const form = ref(null);
const fileInput = ref(null);
const draft = reactive({ titulo: "", descripcion: "" });
const error = ref("");
function open() {
  draft.titulo = "";
  draft.descripcion = "";
  error.value = "";
  form.value.reset();
  dialog.value.showModal();
}
function add() {
  const file = fileInput.value.files[0];
  error.value = "";
  if (!draft.titulo.trim() || !draft.descripcion.trim()) {
    error.value =
      "Completa el título y la descripción sin dejar solo espacios.";
    return;
  }
  if (
    !file ||
    !/\.(pdf|zip)$/i.test(file.name) ||
    file.size < 1 ||
    file.size > 10 * 1024 * 1024
  ) {
    error.value = "Selecciona un PDF o ZIP de hasta 10 MB.";
    return;
  }
  emit("add", {
    titulo: draft.titulo.trim(),
    descripcion: draft.descripcion.trim(),
    file,
  });
  dialog.value.close();
}
</script>

<template>
  <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
    <div>
      <h2 class="mb-1">Documentos</h2>
      <p class="text-secondary mb-0 small">
        La oferta debe tener al menos un documento para guardar la edición.
      </p>
    </div>
    <button
      type="button"
      class="btn btn-outline-primary text-nowrap"
      :disabled="pending.length >= 5"
      @click="open"
    >
      Agregar documento
    </button>
  </div>
  <ul v-if="existing.length || pending.length" class="document-list">
    <li v-for="doc in existing" :key="doc.id">
      <div>
        <strong>{{ doc.titulo }}</strong>
        <p class="mb-1 text-secondary">{{ doc.descripcion }}</p>
        <span class="small text-secondary">{{ doc.nombre_original }}</span>
      </div>
      <a
        :href="'/api/documentos/' + doc.id + '/descargar'"
        class="btn btn-outline-secondary btn-sm"
        >Descargar</a
      >
    </li>
    <li v-for="(doc, index) in pending" :key="index">
      <div>
        <strong>{{ doc.titulo }}</strong>
        <p class="mb-1 text-secondary">{{ doc.descripcion }}</p>
        <span class="small text-secondary"
          >{{ doc.file.name }} · Sin guardar</span
        >
      </div>
      <button
        type="button"
        class="btn btn-outline-danger btn-sm"
        :aria-label="'Quitar ' + doc.titulo"
        @click="emit('remove', index)"
      >
        Quitar
      </button>
    </li>
  </ul>
  <p v-else class="small text-secondary mb-0">
    Todavía no has agregado documentos.
  </p>
  <Teleport to="body">
    <dialog ref="dialog" class="document-dialog" aria-labelledby="dialog-title">
      <form ref="form" @submit.stop.prevent="add">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 id="dialog-title" class="mb-0">Agregar documento</h2>
          <button
            type="button"
            class="btn-close"
            aria-label="Cerrar"
            @click="dialog.close()"
          ></button>
        </div>
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>
        <label for="doc-titulo" class="form-label">Título *</label
        ><input
          id="doc-titulo"
          v-model="draft.titulo"
          required
          maxlength="100"
          class="form-control mb-3"
        />
        <label for="doc-descripcion" class="form-label">Descripción *</label
        ><textarea
          id="doc-descripcion"
          v-model="draft.descripcion"
          required
          maxlength="200"
          rows="3"
          class="form-control mb-3"
        ></textarea>
        <label for="doc-archivo" class="form-label">Archivo *</label
        ><input
          id="doc-archivo"
          ref="fileInput"
          type="file"
          accept=".pdf,.zip"
          required
          class="form-control"
          aria-describedby="archivo-ayuda"
        />
        <p id="archivo-ayuda" class="form-text">
          PDF o ZIP, máximo 10 MB. Hasta 5 archivos nuevos por edición.
        </p>
        <div class="d-flex justify-content-end gap-2 mt-4">
          <button
            type="button"
            class="btn btn-outline-secondary"
            @click="dialog.close()"
          >
            Cancelar</button
          ><button class="btn btn-primary">Agregar</button>
        </div>
      </form>
    </dialog>
  </Teleport>
</template>
