import api from './index.js'

export function getList(params = {}) {
  return api.get('/unlock', { params })
}

export function create(data) {
  return api.post('/unlock', data)
}

export function deleteUnlock(id) {
  return api.delete(`/unlock/${id}`)
}
