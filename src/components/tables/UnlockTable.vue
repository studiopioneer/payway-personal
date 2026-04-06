<template>
  <DataTable
    :value="loading ? skeletonData : data"
    :paginator="true"
    :rows="rows"
    :totalRecords="totalRecords"
    :lazy="true"
    :first="(page - 1) * rows"
    :rowsPerPageOptions="[5, 10, 25]"
    editMode="row"
    dataKey="id"
    :loading="loading"
    :scrollable="true"
    class="text-sm"
    @page="onPageChange"
    @sort="onSortChange"
    @row-edit-save="onRowEditComplete"
  >
    <Column field="time" header="Дата/время" :sortable="true"
      :style="{ width: '13rem', textAlign: 'left', verticalAlign: 'top' }"
      headerStyle="text-align: left"
      bodyStyle="text-align: left; vertical-align: top"
    >
      <template #body="{ data: row }">
        <Skeleton v-if="loading" width="100%" height="1.5rem" />
        <span v-else>{{ row.time }}</span>
      </template>
    </Column>
    <Column field="amount" header="Сумма" :sortable="true"
      :style="{ textAlign: 'left', verticalAlign: 'top' }"
      headerStyle="text-align: left"
      bodyStyle="text-align: left; vertical-align: top"
    >
      <template #body="{ data: row }">
        <Skeleton v-if="loading" width="100%" height="1.5rem" />
        <div v-else class="flex align-items-center gap-2">
          <span>${{ row.amount }}</span>
        </div>
      </template>
    </Column>
    <Column field="status" header="Статус" :sortable="true"
      :style="{ width: '8rem', textAlign: 'left', verticalAlign: 'top' }"
      headerStyle="text-align: left"
      bodyStyle="text-align: left; vertical-align: top"
    >
      <template #body="{ data: row }">
        <Skeleton v-if="loading" width="100%" height="1.5rem" />
        <StatusBadge v-else :rowData="row" :statuses="unlockStatuses" />
      </template>
    </Column>
  </DataTable>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import StatusBadge from '@/components/StatusBadge.vue'
import api from '@/api/index.js'

const data = ref([])
const totalRecords = ref(0)
const loading = ref(false)
const page = ref(1)
const rows = ref(10)
const sortField = ref('time')
const sortOrder = ref(-1)

const endpoint = '/unlock'

const unlockStatuses = [
  { label: 'Отклонен', value: 'rejected', severity: 'danger' },
  { label: 'На проверке', value: 'review', severity: 'warning' },
  { label: 'Подтвержден', value: 'approved', severity: 'info' },
  { label: 'Выплачено', value: 'paid', severity: 'success' }
]

const skeletonData = computed(() =>
  Array.from({ length: rows.value }).map((_, i) => ({ id: `skeleton-${i}` }))
)

async function fetchData() {
  loading.value = true
  try {
    const resp = await api.get(endpoint, {
      params: {
        page: page.value,
        per_page: rows.value,
        sort_field: sortField.value,
        sort_order: sortOrder.value
      }
    })
    data.value = resp.data.data || resp.data
    totalRecords.value = resp.data.meta?.total_records || 0
  } catch (err) {
    console.error('Error fetching unlock data:', err)
  } finally {
    loading.value = false
  }
}

function onPageChange(event) {
  page.value = event.page + 1
  rows.value = event.rows
  fetchData()
}

function onSortChange(event) {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  fetchData()
}

async function onRowEditComplete(event) {
  const { newData } = event
  try {
    await api.put(`${endpoint}/${newData.id}`, newData)
  } catch (err) {
    console.error('Error updating row:', err)
  }
}

onMounted(() => {
  fetchData()
})
</script>
