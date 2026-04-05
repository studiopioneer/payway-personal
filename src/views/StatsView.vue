<template>
  <div class="p-5">
    <Toast ref="toastRef" />
    <div class="text-900 font-semibold lg:text-3xl sm:text-2xl mt-3">
      Статистика
    </div>
    <Divider />
    <div class="grid grid-cols-2 gap-4 mb-3 items-end">
      <div class="col pt-4 lg:text-lg sm:text-sm">
        Баланс: <span :class="monthlyBalance < 0 ? 'text-red-500' : 'text-green-500'">${{ monthlyBalance.toFixed(2) }}</span>
      </div>
      <div class="flex-col items-end">
        <Dropdown
          v-model="selectedMonth"
          :options="months"
            optionLabel="label"
            optionValue="value"
          @change="onMonthChange"
          placeholder="Выберите месяц"
          :disabled="loading"
        />
      </div>
    </div>
    <div v-if="loading" class="text-center">Загрузка...</div>
    <StatsTable v-else :data="statsData" :highlightEarnings="highlightEarnings" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Toast from 'primevue/toast'
import Divider from 'primevue/divider'
import Dropdown from 'primevue/dropdown'
import StatsTable from '@/components/tables/StatsTable.vue'
import { useToastStore } from '@/stores/toast.js'

const toastRef = ref(null)
const toastStore = useToastStore()

const months = ref([])
const selectedMonth = ref(null)
const monthlyBalance = ref(0)
const statsData = ref([])
const loading = ref(false)
const highlightEarnings = ref(false)
const initialMonth = ref(null)

const API_BASE = '/wp-json/payway/v1'

async function fetchApi(path) {
  const token = localStorage.getItem('jwtToken')
  const resp = await fetch(API_BASE + path, {
    headers: { Authorization: `Bearer ${token}` }
  })
  if (!resp.ok) throw new Error(`HTTP ${resp.status}`)
  return resp.json()
}

async function loadInitial() {
  loading.value = true
  try {
    const availableMonths = await fetchApi('/stats/available-months')
    const monthOptions = availableMonths.map(m => {
      const [year, month] = m.split('-')
      const date = new Date(year, month - 1)
      const label = new Intl.DateTimeFormat('ru-RU', { month: 'long', year: 'numeric' }).format(date)
      return { label: label.charAt(0).toUpperCase() + label.slice(1), value: m }
    })
    months.value = monthOptions
    if (monthOptions.length > 0) {
      const firstMonth = monthOptions[0].value
      initialMonth.value = firstMonth
      selectedMonth.value = firstMonth
      const [data, balance] = await Promise.all([
        fetchApi(`/stats/get-by-month?month=${firstMonth}`),
        fetchApi(`/stats/monthly-balance?month=${firstMonth}`)
      ])
      statsData.value = data
      monthlyBalance.value = balance.balance ?? 0
    }
  } catch (err) {
    console.error('Ошибка при загрузке данных:', err)
  } finally {
    loading.value = false
  }
}

async function onMonthChange(event) {
  const month = event.value
  selectedMonth.value = month
  highlightEarnings.value = month !== initialMonth.value
  loading.value = true
  try {
    const [data, balance] = await Promise.all([
      fetchApi(`/stats/get-by-month?month=${month}`),
      fetchApi(`/stats/monthly-balance?month=${month}`)
    ])
    statsData.value = data
    monthlyBalance.value = balance.balance ?? 0
  } catch (err) {
    console.error('Ошибка при загрузке данных:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadInitial()
  if (toastStore.message) {
    toastRef.value.show({
      severity: toastStore.severity,
      summary: 'Успешно',
      detail: toastStore.message,
      life: 3000
    })
    setTimeout(() => toastStore.clearToast(), 3000)
  }
})
</script>
