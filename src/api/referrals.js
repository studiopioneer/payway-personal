import api from './index.js'

export async function getReferralLink() {
  const resp = await api.get('/referrals/link')
  return resp.data
}

export async function getMyReferrals() {
  const resp = await api.get('/referrals/list')
  return resp.data
}
