<template>
  <div class="p-card p-4 w-30">
    <Toast ref="toastRef" />
    <DataTable
      :value="loading ? skeletonData : localData"
      :paginator="true"
      :rows="rows"
      :totalRecords="totalRecords"
      :lazy="true"
      :first="(page - 1) * rows"
      :rowsPerPageOptions="rowsPerPageOptions"
      :editMode="editMode"
      :dataKey="dataKey"
      :emptyMessage="emptyMessage"
      :loading="loading"
      :scrollable="true"
      class="text-sm"
      @page="onPageChange"
      @sort="onSortChange"
      @row-edit-save="onRowEditComplete"
    >
      <Column
        v-for="col in columns"
        :key="col.field"
        :field="col.field"
        :header="col.header"
        :sortable="col.sortable || false"
        :style="{ ...col.style, textAlign: 'left', verticalAlign: 'top' }"
        :headerStyle="{ textAlign: 'left' }"
        :bodyStyle="{ textAlign: 'left', verticalAlign: 'top' }"
      >
        <template v-if="col.body" #body="slotProps">
          <Skeleton v-if="loading" width="100%" height="1.5rem" />
          <component v-else :is="col.body" v-bind="slotProps" />
        </template>
        <template v-if="col.editor" #editor="slotProps">
          <component :is="col.editor" v-bind="slotProps" />
        </template>
      </Column>
      <Column
        v-if="onDelete"
        :rowEditor="true"
        header="Действия"
        class="text-center valign-top align-top text-sm"
        style="width: 10rem; text-align: center; vertical-align: top"
      />
    </DataTable>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toast from 'primevue/toast'
import Skeleton from 'primevue/skeleton'

const props = defineProps({
  columns: { type: Array, required: true },
  data: { type: Array, default: () => [] },
  totalRecords: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  emptyMessage: { type: String, default: 'Нет данных' },
  rowsPerPageOptions: { type: Array, default: () => [5, 10, 25] },
  editMode: { type: String, default: 'row' },
  dataKey: { type: String, default: 'id' },
  onDelete: { type: Function, default: null }
})

const emit = defineEmits(['page', 'sort', 'row-edit-complete'])

const toastRef = ref(null)
const page = ref(1)
const rows = ref(props.rowsPerPageOptions[0])
const localData = ref([...props.data])

watch(() => props.data, (val) => {
  localData.value = [...val]
})

const skeletonData = computed(() =>
  Array.from({ length: rows.value }).map((_, i) => ({ id: `skeleton-${i}` }))
)

function onPageChange(event) {
  page.value = event.page + 1
  rows.value = event.rows
  emit('page', event)
}

function onSortChange(event) {
  emit('sort', event)
}

function onRowEditComplete(event) {
  emit('row-edit-complete', event)
}
</script>
