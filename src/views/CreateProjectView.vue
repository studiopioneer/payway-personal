<template>
  <div class="p-5">
    <div class="text-3xl text-900 font-semibold text-lg mt-3">
      Создание проекта
    </div>
    <Divider />
    <BreadCrumb :model="breadcrumbItems" />
    <ProjectForm />
  </div>
</template>

<script setup>
import Divider from 'primevue/divider'
import BreadCrumb from 'primevue/breadcrumb'
import ProjectForm from '@/components/forms/ProjectForm.vue'

const breadcrumbItems = [
  { id: 'withdrawal', label: 'Мои проекты', url: '/projects' },
  { id: 'create-withdrawal', label: 'Создание проекта', url: '/create-project' }
]
</script>
