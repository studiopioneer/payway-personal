<template>
  <div class="p-5">
    <div class="text-3xl text-900 font-semibold text-lg mt-3">
      Заявка на вывод средств
    </div>
    <Divider />
    <BreadCrumb :model="breadcrumbItems" />
    <WithdrawalForm />
  </div>
</template>

<script setup>
import Divider from 'primevue/divider'
import BreadCrumb from 'primevue/breadcrumb'
import WithdrawalForm from '@/components/forms/WithdrawalForm.vue'

const breadcrumbItems = [
  { id: 'withdrawal', label: 'Вывод средств', url: '/account' },
  { id: 'create-withdrawal', label: 'Создание заявки на вывод средств', url: '/create-withdrawal' }
]
</script>
