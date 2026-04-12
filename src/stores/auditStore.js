import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/index.js'

export const useAuditStore = defineStore('audit', () => {
  const auditId    = ref(null)
  const status     = ref('idle')
  const report     = ref(null)
  const error      = ref(null)
  const isPaid     = ref(false)
  const unlockInfo = ref(null)
  let _pollTimer   = null

  async function startAudit(channelUrl) {
    auditId.value = null; report.value = null; error.value = null
    isPaid.value = false; unlockInfo.value = null; status.value = 'pending'
    try {
      const res = await api.post('/audit/start', { channel_url: channelUrl })
      auditId.value = res.data.audit_id
      _schedulePoll()
    } catch (e) { status.value = 'error'; error.value = e.message }
  }

  function _schedulePoll() { _pollTimer = setTimeout(pollStatus, 3000) }

  async function pollStatus() {
    if (!auditId.value) return
    try {
      const res = await api.get('/audit/' + auditId.value + '/status')
      const s = res.data.status
      if (s === 'done') {
        status.value = 'done'; report.value = res.data.report || null
        isPaid.value = !!res.data.is_paid
      } else if (s === 'error') {
        status.value = 'error'; error.value = res.data.message || 'Ошибка анализа'
      } else { _schedulePoll() }
    } catch (e) { status.value = 'error'; error.value = e.message }
  }

  async function unlockReport() {
    if (!auditId.value) return
    try {
      const res = await api.post('/audit/' + auditId.value + '/unlock')
      if (res.data.unlocked) { isPaid.value = true; unlockInfo.value = res.data }
      return res.data
    } catch (e) { throw e }
  }

  function reset() {
    if (_pollTimer) clearTimeout(_pollTimer)
    auditId.value = null; status.value = 'idle'; report.value = null
    error.value = null; isPaid.value = false; unlockInfo.value = null
  }

  return { auditId, status, report, error, isPaid, unlockInfo, startAudit, pollStatus, unlockReport, reset }
})