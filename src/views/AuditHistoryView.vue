<template>
  <div class="p-3 md:p-5">
    <div class="flex align-items-center gap-2 mb-4">
      <i class="pi pi-history text-primary" style="font-size:1.4rem"></i>
      <h1 class="text-900 font-bold m-0" style="font-size:1.5rem">История аудитов</h1>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-content-center py-6">
      <ProgressSpinner style="width:40px;height:40px" strokeWidth="4" />
    </div>

    <!-- Empty -->
    <div v-else-if="!audits.length" class="surface-card border-round-xl shadow-1 p-5 text-center">
      <i class="pi pi-inbox text-300" style="font-size:3rem"></i>
      <p class="text-500 mt-3">История аудитов пуста</p>
      <Button label="Запустить аудит" icon="pi pi-search" class="mt-2" @click="$router.push('/audit')" />
    </div>

    <!-- List -->
    <div v-else>
      <div
        v-for="audit in audits"
        :key="audit.id"
        class="surface-card border-round-xl shadow-1 p-4 mb-3 cursor-pointer hover:surface-hover transition-colors transition-duration-150"
        @click="openAudit(audit)"
      >
        <div class="flex align-items-center gap-3 flex-wrap">
          <div class="flex align-items-center gap-2 flex-1 min-w-0">
            <i class="pi pi-youtube text-red-500 flex-shrink-0"></i>
            <span class="text-900 font-medium text-sm overflow-hidden white-space-nowrap" style="text-overflow:ellipsis">
              {{ audit.channel_url }}
            </span>
          </div>
          <div class="flex align-items-center gap-2 flex-shrink-0">
            <Tag
              :value="statusLabel(audit.status)"
              :severity="statusSeverity(audit.status)"
              class="text-xs"
            />
            <span class="text-500 text-xs">{{ formatDate(audit.created_at) }}</span>
          </div>
        </div>

        <div v-if="audit.status === 'done'" class="flex gap-3 mt-3">
          <div v-if="audit.report?.admission" class="flex align-items-center gap-1">
            <i :class="riskIcon(audit.report.admission.risk)" style="font-size:0.9rem"></i>
            <span class="text-xs text-500">Допуск</span>
          </div>
          <div v-if="audit.report?.demonetization" class="flex align-items-center gap-1">
            <i :class="riskIcon(audit.report.demonetization.risk)" style="font-size:0.9rem"></i>
            <span class="text-xs text-500">Монетизация</span>
          </div>
          <div v-if="audit.report?.copyright" class="flex align-items-center gap-1">
            <i :class="riskIcon(audit.report.copyright.risk)" style="font-size:0.9rem"></i>
            <span class="text-xs text-500">Авт. права</span>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <Paginator
        v-if="total > perPage"
        :rows="perPage"
        :totalRecords="total"
        :first="(page - 1) * perPage"
        @page="onPage"
        class="mt-3"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/index.js'

const router = useRouter()
const audits = ref([])
const loading = ref(true)
const page = ref(1)
const perPage = 10
const total = ref(0)

onMounted(() => fetchHistory())

async function fetchHistory() {
  loading.value = true
  try {
    const res = await api.get('/audit/history', { params: { page: page.value, per_page: perPage } })
    audits.value = res.data.items || []
    total.value = res.data.total || 0
  } catch (e) {
    audits.value = []
  } finally {
    loading.value = false
  }
}

function onPage(event) {
  page.value = Math.floor(event.first / perPage) + 1
  fetchHistory()
}

function openAudit(audit) {
  router.push({ path: '/audit', query: { id: audit.id } })
}

function statusLabel(status) {
  const map = { pending: 'В обработке', done: 'Готов', error: 'Ошибка' }
  return map[status] || status
}

function statusSeverity(status) {
  const map = { pending: 'warning', done: 'success', error: 'danger' }
  return map[status] || 'secondary'
}

function riskIcon(risk) {
  if (risk === 'low') return 'pi pi-check-circle text-green-500'
  if (risk === 'medium') return 'pi pi-exclamation-triangle text-yellow-500'
  if (risk === 'high') return 'pi pi-times-circle text-red-500'
  return 'pi pi-question-circle text-500'
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>
