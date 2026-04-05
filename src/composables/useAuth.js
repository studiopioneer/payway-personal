import { ref, readonly } from 'vue'

const isAuthenticated = ref(!!localStorage.getItem('jwtToken'))

export function useAuth() {
  function login(token) {
    localStorage.setItem('jwtToken', token)
    isAuthenticated.value = true
  }

  function logout() {
    localStorage.removeItem('jwtToken')
    isAuthenticated.value = false
  }

  return {
    isAuthenticated: readonly(isAuthenticated),
    login,
    logout
  }
}
