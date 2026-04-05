import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getReferralLink, getMyReferrals } from '@/api/referrals.js'

export const useReferralStore = defineStore('referrals', () => {
  const referralUrl = ref('')
  const referralCode = ref('')
  const referrals = ref([])
  const loading = ref(false)

  async function fetchReferralLink() {
    try {
      const data = await getReferralLink()
      referralUrl.value = data.url || ''
      referralCode.value = data.code || ''
    } catch (e) {
      console.error('Failed to fetch referral link:', e)
    }
  }

  async function fetchMyReferrals() {
    loading.value = true
    try {
      const data = await getMyReferrals()
      referrals.value = Array.isArray(data) ? data : (data.data || [])
    } catch (e) {
      console.error('Failed to fetch referrals:', e)
    } finally {
      loading.value = false
    }
  }

  return { referralUrl, referralCode, referrals, loading, fetchReferralLink, fetchMyReferrals }
})
