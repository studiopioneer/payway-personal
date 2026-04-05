<template>
  <div class="p-5">
    <Toast ref="toastRef" />
    <div class="text-3xl text-900 font-semibold text-lg mt-3">
      Мои проекты
    </div>
    <Divider />
    <div class="mb-3">
      <NavigateButton label="Создать заявку" to="/create-project" />
    </div>
    <ProjectsTable />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Toast from 'primevue/toast'
import Divider from 'primevue/divider'
import NavigateButton from '@/components/NavigateButton.vue'
import ProjectsTable from '@/components/tables/ProjectsTable.vue'
import { useToastStore } from '@/stores/toast.js'

const toastRef = ref(null)
const toastStore = useToastStore()

onMounted(() => {
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
