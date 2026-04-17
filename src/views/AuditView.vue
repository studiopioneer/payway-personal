<template>
  <div class="p-5">
    <div class="text-3xl text-900 font-semibold text-lg mt-3">
      Аудит канала
    </div>
    <Divider />
    <div class="surface-card border-round p-4">
      <!-- Форма ввода URL канала -->
      <div v-if="!store.auditId || store.status === 'idle'" class="flex flex-column gap-3">
        <label for="channel-url" class="font-medium">Ссылка на YouTube канал</label>
        <div class="flex gap-2">
          <InputText
            id="channel-url"
            v-model="channelUrl"
            placeholder="https://www.youtube.com/@channel"
            class="flex-1"
            :disabled="isSubmitting"
            @keyup.enter="submitAudit"
          />
          <Button
            label="Проверить"
            icon="pi pi-search"
            :loading="isSubmitting"
            @click="submitAudit"
          />
        </div>
        <small class="text-500">Вставьте ссылку на YouTube канал для проверки готовности к монетизации</small>
      </div>
 
      <!-- Статус обработки -->
      <div v-else-if="store.status === 'pending' || store.status === 'processing'" class="flex flex-column align-items-center gap-3 py-5">
        <ProgressSpinner style="width: 50px; height: 50px" />
        <span class="text-600">Анализируем канал... Это может занять 1-2 минуты.</span>
      </div>
 
      <!-- Ошибка -->
      <div v-else-if="store.status === 'failed'" class="flex flex-column align-items-center gap-3 py-5">
        <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
        <span class="text-600">{{ store.error || 'Произошла ошибка при анализе' }}</span>
        <Button label="Попробовать снова" icon="pi pi-refresh" class="p-button-outlined" @click="store.reset()" />
      </div>
 
      <!-- Результат — рендерится audit-ui-inject.js поверх этого контейнера -->
      <div v-else-if="store.status === 'done'">
        <!-- audit-ui-inject.js автоматически перестраивает DOM здесь -->
      </div>
    </div>
  </div>
</template>
 
<script setup>
import { ref } from 'vue'
import Divider from 'primevue/divider'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import { useAuditStore } from '@/stores/audit.js'
 
const store = useAuditStore()
const channelUrl = ref('')
const isSubmitting = ref(false)
 
async function submitAudit() {
  if (!channelUrl.value.trim()) return
  isSubmitting.value = true
  try {
    await store.startAudit(channelUrl.value.trim())
  } finally {
    isSubmitting.value = false
  }
}
</script>
