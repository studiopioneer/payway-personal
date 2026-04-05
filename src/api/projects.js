import api from './index.js'

export function getList(params = {}) {
  return api.get('/projects', { params })
}

export function create(data) {
  return api.post('/projects', data)
}

export function deleteProject(id) {
  return api.delete(`/projects/${id}`)
}
