<template>
  <div
    v-if="isAuthenticated"
    class="lg:hidden fixed bottom-0 left-0 right-0 surface-section border-top-1 surface-border flex align-items-center justify-content-around z-5"
    style="height: 56px"
  >
    <router-link
      v-for="tab in tabs"
      :key="tab.path"
      :to="tab.path"
      class="no-underline flex flex-column align-items-center justify-content-center gap-1 py-1 px-2"
      :class="isActive(tab.path) ? 'text-blue-600' : 'text-600'"
    >
      <i :class="[tab.icon]" style="font-size: 1.2rem"></i>
      <span style="font-size: 0.65rem; font-weight: 500">{{ tab.label }}</span>
    </router-link>
  </div>
</template>
 
<script setup>
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth.js'
 
const route = useRoute()
const { isAuthenticated } = useAuth()
 
const tabs = [
  { path: '/account', icon: 'pi pi-download', label: 'Вывод' },
  { path: '/projects', icon: 'pi pi-folder', label: 'Проекты' },
  { path: '/audit', icon: 'pi pi-search', label: 'Аудит' },
  { path: '/stats', icon: 'pi pi-chart-bar', label: 'Статистика' },
  { path: '/profile', icon: 'pi pi-user', label: 'Профиль' }
]
 
function isActive(path) {
  return route.path === path
}
</script>
