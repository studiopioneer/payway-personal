<template>
  <div class="audit-unlock-button">
    <div class="surface-card border-round-xl shadow-1 p-4" style="max-width:480px">
      <div class="flex align-items-center gap-2 mb-3">
        <i class="pi pi-lock text-orange-400" style="font-size:1.5rem"></i>
        <span class="font-bold text-xl text-900">Полный отчёт заблокирован</span>
      </div>
      <p class="text-600 mb-3 line-height-3">
        Разблокируйте полный AI-анализ канала: детальные рекомендации, список проблемных видео
        и план по устранению нарушений.
      </p>

      <Message v-if="errorMsg" severity="error" :closable="false" class="mb-3">
        {{ errorMsg }}
      </Message>

      <div class="flex align-items-center gap-3 flex-wrap">
        <Button
          label="Разблокировать за $1.00"
          icon="pi pi-unlock"
          :loading="loading"
          @click="handleUnlock"
          class="p-button-warning"
        />
        <span class="text-500 text-sm">Списание с баланса аккаунта</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuditStore } from '@/stores/auditStore.js'

const store = useAuditStore()
const loading = ref(false)
const errorMsg = ref(null)

async function handleUnlock() {
  loading.value = true
  errorMsg.value = null
  try {
    await store.unlockReport()
  } catch (e) {
    if (e?.response?.status === 402) {
      errorMsg.value = 'Недостаточно средств на балансе. Пополните счёт и попробуйте снова.'
    } else if (e?.response?.status === 429) {
      errorMsg.value = 'Слишком много запросов. Подождите минуту и попробуйте снова.'
    } else {
      errorMsg.value = store.error || 'Произошла ошибка. Попробуйте позже.'
    }
  } finally {
    loading.value = false
  }
}
</script>
