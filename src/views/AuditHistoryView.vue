<template>
  <div class="p-5">
    <div class="text-3xl text-900 font-semibold text-lg mt-3">
      История аудитов
    </div>
    <Divider />
    <div class="mb-3">
      <NavigateButton label="Новый аудит" to="/audit" />
    </div>
    <div v-if="isLoading" class="flex justify-content-center py-5">
      <ProgressSpinner style="width: 40px; height: 40px" />
    </div>
    <div v-else-if="audits.length === 0" class="surface-card border-round p-4 text-center text-500">
      У вас пока нет аудитов
    </div>
    <div v-else class="flex flex-column gap-2">
      <div
        v-for="item in audits"
        :key="item.id"
        class="surface-card border-round p-3 flex align-items-center justify-content-between cursor-pointer hover:surface-hover transition-duration-150"
        @click="openAudit(item.id)"
      >
        <div class="flex flex-column gap-1">
          <span class="font-medium text-900">{{ item.channel_title || item.url || 'Аудит #' + item.id }}</span>
          <small class="text-500">{{ formatDate(item.created_at) }}</small>
        </div>
        <div class="flex align-items-center gap-2">
          <span
            class="px-2 py-1 border-round text-xs font-semibold"
            :class="statusClass(item.status)"
          >
            {{ statusLabel(item.status) }}
          </span>
          <i class="pi pi-chevron-right text-400"></i>
        </div>
      </div>
    </div>
  </div>
</template>
 
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Divider from 'primevue/divider'
import ProgressSpinner from 'primevue/progressspinner'
import NavigateButton from '@/components/NavigateButton.vue'
import api from '@/api/index.js'
 
const router = useRouter()
const audits = ref([])
const isLoading = ref(true)
 
onMounted(async () => {
  try {
    const resp = await api.get('/audits')
    audits.value = resp.data || []
  } catch (e) {
    console.error('Failed to load audits:', e)
  } finally {
    isLoading.value = false
  }
})
 
function openAudit(id) {
  router.push('/audit?id=' + id)
}
 
function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
 
function statusClass(status) {
  if (status === 'done') return 'bg-green-100 text-green-700'
  if (status === 'failed') return 'bg-red-100 text-red-700'
  if (status === 'processing' || status === 'pending') return 'bg-yellow-100 text-yellow-700'
  return 'bg-gray-100 text-gray-600'
}
 
function statusLabel(status) {
  const map = { done: 'Готов', failed: 'Ошибка', processing: 'В работе', pending: 'В очереди' }
  return map[status] || status || '—'
}
</script>
