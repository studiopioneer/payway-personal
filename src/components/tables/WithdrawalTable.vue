<template>
  <div class="p-card p-4 w-30">
    <Toast ref="toastRef" />
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
        :style="{ textAlign: 'left', verticalAlign: 'top' }"
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
            <i class="pi pi-arrow-right" style="font-size: 1rem"></i>
            <i :class="paymentIcon(row.payment_type)" style="font-size: 1.5rem"></i>
            <span>{{ row.payment_type }}</span>
          </div>
        </template>
      </Column>
      <Column field="comments" header="Примечание"
        :style="{ width: '25rem', textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      >
        <template #body="{ data: row }">
          <Skeleton v-if="loading" width="100%" height="1.5rem" />
          <div v-else>
            {{ truncateComment(row.comments) }}
          </div>
        </template>
      </Column>
      <Column field="status" header="Статус" :sortable="true"
        :style="{ textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      >
        <template #body="{ data: row }">
          <Skeleton v-if="loading" width="100%" height="1.5rem" />
          <StatusBadge v-else :rowData="row" :statuses="withdrawalStatuses" />
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toast from 'primevue/toast'
import Skeleton from 'primevue/skeleton'
import StatusBadge from '@/components/StatusBadge.vue'
import api from '@/api/index.js'

const toastRef = ref(null)
const data = ref([])
const totalRecords = ref(0)
const loading = ref(false)
const page = ref(1)
const rows = ref(10)
const sortField = ref('time')
const sortOrder = ref(-1)

const endpoint = '/withdrawal'

const withdrawalStatuses = [
  { label: 'Отклонен', value: 'rejected', severity: 'danger' },
  { label: 'На проверке', value: 'review', severity: 'warning' },
  { label: 'Подтвержден', value: 'approved', severity: 'info' },
  { label: 'Выплачено', value: 'paid', severity: 'success' }
]

const paymentIcons = {
  swift: 'pi pi-globe',
  cards: 'pi pi-credit-card',
  cryptocurrency: 'pi pi-bitcoin'
}

const skeletonData = computed(() =>
  Array.from({ length: rows.value }).map((_, i) => ({ id: `skeleton-${i}` }))
)

function paymentIcon(type) {
  return paymentIcons[type] || 'pi pi-question-circle'
}

function truncateComment(comments) {
  const text = comments || 'Нет примечания'
  return text.length > 55 ? `${text.substring(0, 55)}...` : text
}

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
    totalRecords.value = resp.data.total || resp.data.length
  } catch (err) {
    console.error('Error fetching withdrawals:', err)
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
