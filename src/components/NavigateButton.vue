<template>
  <Button
    type="button"
    :label="label"
    class="bg-blue-500 hover:bg-blue-600 border-blue-600 hover:border-blue-700"
    size="small"
    @click="router.push(to)"
  />
</template>

<script setup>
import Button from 'primevue/button'
import { useRouter } from 'vue-router'

defineProps({
  label: { type: String, required: true },
  to: { type: String, required: true }
})

const router = useRouter()
</script>
