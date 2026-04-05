<template>
  <div class="p-5">
    <Toast ref="toastRef" />
    <div class="text-3xl text-900 font-semibold text-lg mt-3">
      Заявки поданные на вывод средств
    </div>
    <Divider />
    <div class="grid grid-cols-2 gap-4 mb-3 items-end">
      <div class="col">
        <NavigateButton label="Создать заявку" to="/create-withdrawal" />
      </div>
      <div class="col text-right pt-4 text-lg">
        Баланс для вывода: <span class="text-green-500">{{ formatBalance(balance) }}</span>
      </div>
    </div>
    <WithdrawalTable />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Toast from 'primevue/toast'
import Divider from 'primevue/divider'
import NavigateButton from '@/components/NavigateButton.vue'
import WithdrawalTable from '@/components/tables/WithdrawalTable.vue'
import { useUserStore } from '@/stores/user.js'
import { useToastStore } from '@/stores/toast.js'
import { storeToRefs } from 'pinia'

const toastRef = ref(null)
const userStore = useUserStore()
const toastStore = useToastStore()
const { balance } = storeToRefs(userStore)

onMounted(async () => {
  await userStore.fetchBalance()
  checkToast()
})

function checkToast() {
  if (toastStore.message) {
    toastRef.value.show({
      severity: toastStore.severity,
      summary: 'Успешно',
      detail: toastStore.message,
      life: 3000
    })
    setTimeout(() => toastStore.clearToast(), 3000)
  }
}

function formatBalance(val) {
  if (val === null || val === undefined) return '...'
  return `$${parseFloat(val).toFixed(2)}`
}
</script>
