import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getBalance } from '@/api/stats.js'

export const useUserStore = defineStore('user', () => {
  const balance = ref(null)
  const isLoadingBalance = ref(false)

  async function fetchBalance() {
    isLoadingBalance.value = true
    try {
      const resp = await getBalance()
      const data = resp.data
      const val = data && typeof data === 'object' && data.balance !== undefined ? data.balance : data
      if (val != null && val !== '') {
        balance.value = parseFloat(val)
      }
    } catch (e) {
      console.error('Failed to fetch balance:', e)
    } finally {
      isLoadingBalance.value = false
    }
  }

  function formatBalance(value) {
    if (value === null || value === undefined) return '...'
    return `$${parseFloat(value).toFixed(2)}`
  }

  return { balance, isLoadingBalance, fetchBalance, formatBalance }
})
