<template>
  <div>
    <Toast ref="toastRef" />
    <DataTable
      :value="loading ? skeletonData : data"
      :paginator="true"
      :rows="rows"
      :totalRecords="totalRecords"
      :lazy="true"
      :first="(page - 1) * rows"
      :rowsPerPageOptions="[2, 3, 5]"
      editMode="row"
      dataKey="id"
      emptyMessage="Еще нет ни одного проекта"
      :loading="loading"
      :scrollable="true"
      class="text-sm"
      @page="onPageChange"
      @sort="onSortChange"
      @row-edit-save="onRowEditComplete"
    >
      <Column field="url" header="Детали проекта" :sortable="false"
        :style="{ textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      >
        <template #body="{ data: row }">
          <Skeleton v-if="loading" width="100%" height="1.5rem" />
          <div v-else>
            <div class="grid">
              <div class="col-12 mb-3 text-sm flex align-items-center">
                <UrlPreview :url="row.url" />
              </div>
              <div class="col-12 text-xs border-1 border-200 border-round-xs mb-3 surface-50 p-2">
                <div class="flex align-items-center">
                  <i class="pi pi-comment mr-2"></i>
                  <strong class="font-semibold">Комментарий: </strong>
                </div>
                <p class="text-400 font-italic pl-4">{{ row.comments || 'Нет данных' }}</p>
              </div>
              <div class="col-12 md:col-6 p-0 text-xs">
                <div class="col-12 flex align-items-center">
                  <i class="pi pi-chart-line mr-2"></i>
                  <strong class="font-semibold">Оборот в месяц: </strong>
                  <span class="inline-block ml-1">${{ row.amount || 'Нет данных' }}</span>
                </div>
                <div class="col-12 flex align-items-center">
                  <i class="pi pi-users mr-2"></i>
                  <strong class="font-semibold">Количество пользователей:</strong>
                  <span class="inline-block ml-1">{{ row.count_users || 'Нет данных' }}</span>
                </div>
              </div>
              <div class="col-12 md:col-6 text-xs">
                <div class="col-12 flex align-items-center">
                  <i class="pi pi-telegram mr-2"></i>
                  <strong class="font-semibold">Контактные данные: </strong>
                  <span class="inline-block ml-1">{{ row.contacts || 'Нет данных' }}</span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Column>
      <Column field="time" header="Дата" :sortable="true"
        class="align-top text-sm"
        :style="{ width: '13rem', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      >
        <template #body="{ data: row }">
          <Skeleton v-if="loading" width="100%" height="1.5rem" />
          <span v-else>{{ row.time }}</span>
        </template>
      </Column>
      <Column field="status" header="Статус" :sortable="true"
        class="align-top text-sm"
        :style="{ width: '8rem', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      >
        <template #body="{ data: row }">
          <Skeleton v-if="loading" width="100%" height="1.5rem" />
          <StatusBadge v-else :rowData="row" :statuses="projectStatuses" />
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
import UrlPreview from '@/components/UrlPreview.vue'
import api from '@/api/index.js'

const toastRef = ref(null)
const data = ref([])
const totalRecords = ref(0)
const loading = ref(false)
const page = ref(1)
const rows = ref(2)
const sortField = ref('time')
const sortOrder = ref(-1)

const endpoint = '/projects'

const projectStatuses = [
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
    totalRecords.value = resp.data.total || resp.data.length
  } catch (err) {
    console.error('Error fetching projects:', err)
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

async function deleteProject(id) {
  try {
    await api.delete(`${endpoint}/${id}`)
    toastRef.value.show({ severity: 'success', summary: 'Успешно', detail: 'Проект успешно удален', life: 3000 })
    fetchData()
  } catch (err) {
    toastRef.value.show({ severity: 'error', summary: 'Ошибка', detail: 'Не удалось удалить проект', life: 3000 })
  }
}

onMounted(() => {
  fetchData()
})
</script>
