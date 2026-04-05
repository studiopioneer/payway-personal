<template>
  <div class="p-5">
    <div class="text-3xl text-900 font-semibold text-lg mt-3">
      Заявка на вывод средств
    </div>
    <Divider />
    <BreadCrumb :model="breadcrumbItems" />
    <UnlockForm />
  </div>
</template>

<script setup>
import Divider from 'primevue/divider'
import BreadCrumb from 'primevue/breadcrumb'
import UnlockForm from '@/components/forms/UnlockForm.vue'

const breadcrumbItems = [
  { id: 'withdrawal', label: 'Разблокировка средств', url: '/unlock' },
  { id: 'create-withdrawal', label: 'Создание заявки на вывод средств', url: '/create-unlock' }
]
</script>
