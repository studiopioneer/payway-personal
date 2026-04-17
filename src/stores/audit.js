import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/index.js'
 
export const useAuditStore = defineStore('audit', () => {
  const auditId = ref(null)
  const status = ref(null)       // null | 'idle' | 'pending' | 'processing' | 'done' | 'failed'
  const report = ref(null)       // { verdict, verdict_reason, summary, admission, demonetization, copyright }
  const preview = ref(null)      // { subscriber_count, view_count, video_count, ... }
  const full = ref(null)         // { block1_criteria, block2_signals, block3_signals, ... }
  const error = ref(null)
  const isPaid = ref(false)
  const unlockInfo = ref(null)   // { balance, credit_status, credit_available }
 
  let pollTimer = null
 
  async function startAudit(channelUrl) {
    error.value = null
    status.value = 'pending'
    try {
      const resp = await api.post('/audit', { url: channelUrl })
      auditId.value = resp.data.id
      status.value = resp.data.status || 'pending'
      pollStatus()
    } catch (e) {
      error.value = e.message || 'Ошибка при создании аудита'
      status.value = 'failed'
    }
  }
 
  async function pollStatus() {
    if (pollTimer) clearInterval(pollTimer)
    pollTimer = setInterval(async () => {
      if (!auditId.value) return
      try {
        const resp = await api.get('/audit/' + auditId.value + '/status')
        const data = resp.data
        status.value = data.status
        if (data.status === 'done' || data.status === 'failed') {
          clearInterval(pollTimer)
          pollTimer = null
          if (data.status === 'done') {
            await fetchReport(auditId.value)
          }
          if (data.status === 'failed') {
            error.value = data.error || 'Аудит завершился с ошибкой'
          }
        }
      } catch (e) {
        console.error('Poll error:', e)
      }
    }, 3000)
  }
 
  async function fetchReport(id) {
    try {
      const resp = await api.get('/audit/' + (id || auditId.value))
      const data = resp.data
      report.value = data.report || null
      preview.value = data.preview || null
      full.value = data.full || null
      isPaid.value = !!data.is_paid
      unlockInfo.value = data.unlock_info || null
      status.value = data.status || 'done'
    } catch (e) {
      console.error('Fetch report error:', e)
    }
  }
 
  async function unlockReport() {
    if (!auditId.value) return
    try {
      const resp = await api.post('/audit/' + auditId.value + '/unlock')
      const data = resp.data
      if (data.success) {
        isPaid.value = true
        full.value = data.full || full.value
        report.value = data.report || report.value
        unlockInfo.value = data.unlock_info || unlockInfo.value
      }
      return data
    } catch (e) {
      throw e
    }
  }
 
  function reset() {
    if (pollTimer) clearInterval(pollTimer)
    pollTimer = null
    auditId.value = null
    status.value = null
    report.value = null
    preview.value = null
    full.value = null
    error.value = null
    isPaid.value = false
    unlockInfo.value = null
  }
 
  return {
    auditId, status, report, preview, full, error, isPaid, unlockInfo,
    startAudit, pollStatus, fetchReport, unlockReport, reset
  }
})
