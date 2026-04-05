import axios from 'axios'

const api = axios.create({
  baseURL: '/wp-json/payway/v1',
  headers: {
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwtToken')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 || error.response?.status === 403) {
      localStorage.removeItem('jwtToken')
      window.location.href = '/login'
    }
    const message =
      error.response?.data?.message ||
      error.response?.data?.data?.message ||
      'Ошибка запроса'
    return Promise.reject(new Error(message))
  }
)

export default api
