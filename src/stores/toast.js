import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const message = ref('')
  const severity = ref('success')

  function showToast(msg, sev = 'success') {
    message.value = msg
    severity.value = sev
  }

  function clearToast() {
    message.value = ''
    severity.value = 'success'
  }

  return { message, severity, showToast, clearToast }
})
