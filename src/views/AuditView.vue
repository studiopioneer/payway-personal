<template>
 <div class="p-3 md:p-5">
  <div class="flex align-items-center gap-2 mb-4">
   <i class="pi pi-search text-primary" style="font-size:1.4rem"></i>
   <h1 class="text-900 font-bold m-0" style="font-size:1.5rem">Аудит канала</h1>
  </div>
  <AuditInputForm v-if="status === 'idle'" @submit="handleStart" />
  <AuditProgress
   v-else-if="status === 'pending' || status === 'error' || (status === 'done' && !isPaid)"
   @retry="handleReset"
  />
  <div v-else-if="status === 'done' && isPaid" class="surface-card border-round-xl shadow-1 p-4" style="max-width:600px">
   <div class="flex align-items-center gap-2 mb-3">
    <i class="pi pi-check-circle text-green-500" style="font-size:1.8rem"></i>
    <span class="font-bold text-xl">Отчёт разблокирован</span>
   </div>
   <p class="text-600">Полный аудит будет доступен в следующем обновлении.</p>
   <Button label="Новый аудит" icon="pi pi-plus" class="mt-3" @click="handleReset" />
  </div>
 </div>
</template>

<script setup>
import { storeToRefs } from 'pinia'
import { useAuditStore } from '@/stores/auditStore.js'
import AuditInputForm from '@/components/audit/AuditInputForm.vue'
import AuditProgress from '@/components/audit/AuditProgress.vue'

const store = useAuditStore()
const { status, isPaid } = storeToRefs(store)

function handleStart(url) { store.startAudit(url) }
function handleReset() { store.reset() }
</script>