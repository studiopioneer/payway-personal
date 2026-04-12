<template>
 <div class="surface-card border-round-xl shadow-1 p-4" style="max-width:560px">
  <!-- pending -->
  <div v-if="status === 'pending'" class="flex flex-column align-items-center gap-3 py-3">
   <ProgressSpinner style="width:56px;height:56px" strokeWidth="4" />
   <p class="text-700 font-medium m-0">Анализируем канал…</p>
   <Tag :value="currentStep" severity="info" />
  </div>

  <!-- error -->
  <div v-else-if="status === 'error'" class="flex flex-column align-items-center gap-3 py-3">
   <i class="pi pi-times-circle text-red-500" style="font-size:3rem"></i>
   <p class="text-900 font-semibold m-0">{{ error || 'Произошла ошибка' }}</p>
   <Button label="Попробовать снова" icon="pi pi-refresh" severity="secondary" @click="emit('retry')" />
  </div>

  <!-- done, not paid -->
  <div v-else-if="status === 'done' && !isPaid" class="flex flex-column align-items-center gap-3 py-3">
   <i class="pi pi-lock text-orange-500" style="font-size:3rem"></i>
   <p class="text-900 font-semibold m-0">Аудит готов — разблокируйте полный отчёт</p>
   <Button label="Разблокировать за $1.00" icon="pi pi-unlock" @click="handleUnlock" :loading="unlocking" />
   <small v-if="unlockError" class="p-error">{{ unlockError }}</small>
  </div>
 </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuditStore } from '@/stores/auditStore.js'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

const emit = defineEmits(['retry'])
const store = useAuditStore()
const { status, error, isPaid } = storeToRefs(store)

const steps = ['Загружаем данные канала…', 'Анализируем видео…', 'Строим отчёт…', 'Финализируем…']
const currentStep = ref(steps[0])
let stepInterval = null

function startSteps() {
 let i = 0
 stepInterval = setInterval(() => { i = (i + 1) % steps.length; currentStep.value = steps[i] }, 2500)
}
function stopSteps() { if (stepInterval) { clearInterval(stepInterval); stepInterval = null } }

watch(status, (s) => {
 if (s === 'pending') startSteps()
 else stopSteps()
}, { immediate: true })

onUnmounted(stopSteps)

const unlocking = ref(false)
const unlockError = ref('')

async function handleUnlock() {
 unlocking.value = true; unlockError.value = ''
 try { await store.unlockReport() }
 catch (e) {
  unlockError.value = e?.response?.status === 402
   ? 'Недостаточно средств на балансе'
   : (e.message || 'Ошибка оплаты')
 } finally { unlocking.value = false }
}
</script>