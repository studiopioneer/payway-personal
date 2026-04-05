<template>
  <div class="surface-ground p-4">
    <div class="text-2xl font-bold mb-4"> </div>

    <div class="surface-card p-4 border-round shadow-2 mb-4">
      <div class="text-lg font-semibold mb-3">  </div>
      <div class="flex align-items-center gap-2">
        <InputText :value="referralStore.referralUrl" readonly class="w-full" />
        <Button icon="pi pi-copy" severity="secondary" @click="copyLink" v-tooltip="''" />
      </div>
      <div class="text-sm text-color-secondary mt-2">
                 ,      .
      </div>
    </div>

    <div class="surface-card p-4 border-round shadow-2">
      <div class="text-lg font-semibold mb-3"> </div>
      <DataTable :value="referralStore.referrals" :loading="referralStore.loading"
        stripedRows responsiveLayout="scroll" emptyMessage="    ">
        <Column field="referral_email" header="Email" />
        <Column field="created_at" header=" ">
          <template #body="{ data }">
            {{ formatDate(data.created_at) }}
          </template>
        </Column>
      </DataTable>
    </div>

    <Toast ref="toastRef" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useReferralStore } from '@/stores/referrals.js'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

const referralStore = useReferralStore()
const toast = useToast()
const toastRef = ref(null)

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' })
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(referralStore.referralUrl)
    toast.add({ severity: 'success', summary: '', detail: '    ', life: 2000 })
  } catch {
    toast.add({ severity: 'error', summary: '', detail: '  ', life: 2000 })
  }
}

onMounted(() => {
  referralStore.fetchReferralLink()
  referralStore.fetchMyReferrals()
})
</script>
