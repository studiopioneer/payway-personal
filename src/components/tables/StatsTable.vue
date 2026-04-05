<template>
  <div class="p-card p-4">
    <DataTable
      :value="data"
      :paginator="true"
      :rows="10"
      :rowsPerPageOptions="[5, 10, 25]"
      emptyMessage="Еще нет статистики"
      :scrollable="true"
      class="text-sm"
    >
      <Column field="period" header="Период" :sortable="true"
        :style="{ minWidth: '15rem', textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      >
        <template #body="{ data: row }">
          {{ formatDate(row.date_start) }} - {{ formatDate(row.date_end) }}
        </template>
      </Column>
      <Column field="project_url" header="Проект" :sortable="false"
        :style="{ minWidth: '20rem', textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      />
      <Column field="estimated_earnings_usd" header="Заработано ($)" :sortable="true"
        :style="{ minWidth: '11rem', textAlign: 'left', verticalAlign: 'top', backgroundColor: highlightEarnings ? '#f0f8ff' : 'transparent' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      />
      <Column field="page_views" header="Просмотры страниц" :sortable="true"
        :style="{ minWidth: '13rem', textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      />
      <Column field="page_rpm_usd" header="RPM страницы ($)" :sortable="true"
        :style="{ minWidth: '13rem', textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      />
      <Column field="impressions" header="Показы" :sortable="true"
        :style="{ minWidth: '10rem', textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      />
      <Column field="impression_rpm_usd" header="RPM показов ($)" :sortable="true"
        :style="{ minWidth: '13rem', textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      />
      <Column field="active_view_viewable" header="Активные просмотры (%)" :sortable="true"
        :style="{ minWidth: '15rem', textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      />
      <Column field="clicks" header="Клики" :sortable="true"
        :style="{ minWidth: '6rem', textAlign: 'left', verticalAlign: 'top' }"
        headerStyle="text-align: left"
        bodyStyle="text-align: left; vertical-align: top"
      />
    </DataTable>
  </div>
</template>

<script setup>
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

defineProps({
  data: { type: Array, default: () => [] },
  highlightEarnings: { type: Boolean, default: false }
})

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric'
  }).format(new Date(dateStr))
}
</script>
