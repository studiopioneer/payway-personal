import api from './index.js'

export function getBalance() {
  return api.get('/user/balance')
}

export function getAvailableMonths() {
  return api.get('/stats/months')
}

export function getMonthlyBalance(month) {
  return api.get('/stats/monthly', { params: { month } })
}

export function getStatsByMonth(params = {}) {
  return api.get('/stats', { params })
}

export function getList(params = {}) {
  return api.get('/stats', { params })
}
