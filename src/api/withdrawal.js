import api from './index.js'

export function getList(params = {}) {
  return api.get('/withdrawal', { params })
}

export function create(data) {
  return api.post('/withdrawal', data)
}

export function deleteWithdrawal(id) {
  return api.delete(`/withdrawal/${id}`)
}
