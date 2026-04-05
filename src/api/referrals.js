import api from './index.js'

export async function getReferralLink() {
  const resp = await api.get('/payway/v1/referrals/link')
  return resp.data
}

export async function getMyReferrals() {
  const resp = await api.get('/payway/v1/referrals/list')
  return resp.data
}
