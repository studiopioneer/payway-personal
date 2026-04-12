<template>
  <div class="p-3 md:p-5">
    <div class="flex align-items-center gap-2 mb-4">
      <i class="pi pi-search text-primary" style="font-size:1.4rem"></i>
      <h1 class="text-900 font-bold m-0" style="font-size:1.5rem">Аудит канала</h1>
    </div>

    <!-- Шаг 1: форма ввода -->
    <AuditInputForm v-if="status === 'idle'" @submit="handleStart" />

    <!-- Шаг 2: прогресс / ошибка -->
    <AuditProgress
      v-else-if="status === 'pending' || status === 'error'"
      @retry="handleReset"
    />

    <!-- Шаг 3: превью результата + кнопка разблокировки -->
    <template v-else-if="status === 'done' && !isPaid">
      <AuditResult :report="report" class="mb-4" />
      <AuditUnlockButton />
      <div class="mt-3">
        <Button label="Новый аудит" icon="pi pi-plus" class="p-button-text p-button-sm" @click="handleReset" />
      </div>
    </template>

    <!-- Шаг 4: полный отчёт -->
    <template v-else-if="status === 'done' && isPaid">
      <AuditResult :report="report" class="mb-4" />
      <AuditFullReport :report="report" @reset="handleReset" />
    </template>
  </div>
</template>

<script setup>
import { storeToRefs } from 'pinia'
import { useAuditStore } from '@/stores/auditStore.js'
import AuditInputForm from '@/components/audit/AuditInputForm.vue'
import AuditProgress from '@/components/audit/AuditProgress.vue'
import AuditResult from '@/components/audit/AuditResult.vue'
import AuditUnlockButton from '@/components/audit/AuditUnlockButton.vue'
import AuditFullReport from '@/components/audit/AuditFullReport.vue'

const store = useAuditStore()
const { status, isPaid, report } = storeToRefs(store)

function handleStart(url) { store.startAudit(url) }
function handleReset() { store.reset() }
</script>
