<template>
 <div class="surface-card border-round-xl shadow-1 p-4" style="max-width:560px">
  <p class="text-600 mt-0 mb-4">Введите URL YouTube-канала для анализа. Результат будет готов через 1–2 минуты.</p>
  <div class="flex flex-column gap-2 mb-3">
   <label class="font-medium text-900">URL канала</label>
   <InputText
    v-model="url"
    placeholder="https://www.youtube.com/@channel"
    :class="{ 'p-invalid': urlError }"
    class="w-full"
    @keyup.enter="submit"
   />
   <small v-if="urlError" class="p-error">{{ urlError }}</small>
  </div>
  <Button
   label="Начать аудит"
   icon="pi pi-search"
   class="w-full"
   :loading="props.loading"
   @click="submit"
  />
 </div>
</template>

<script setup>
import { ref } from 'vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const props = defineProps({ loading: { type: Boolean, default: false } })
const emit = defineEmits(['submit'])

const url = ref('')
const urlError = ref('')

function submit() {
 urlError.value = ''
 const trimmed = url.value.trim()
 if (!trimmed) { urlError.value = 'Введите URL канала'; return }
 if (!trimmed.includes('youtube.com') && !trimmed.includes('youtu.be')) {
  urlError.value = 'Укажите корректный URL YouTube-канала'; return
 }
 emit('submit', trimmed)
}
</script>