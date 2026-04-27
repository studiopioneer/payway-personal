<template>
  <nav class="bottom-nav">
    <router-link
      v-for="item in navItems"
      :key="item.path"
      :to="item.path"
      class="bottom-nav__item"
      :class="{ 'bottom-nav__item--active': isActive(item.path) }"
    >
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
        :stroke="isActive(item.path) ? 'var(--primary-color,#1d4ed8)' : '#9CA3AF'"
        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path v-if="item.icon === 'withdrawal'" d="M12 2v14m0 0l-4-4m4 4l4-4M4 20h16" />
        <path v-else-if="item.icon === 'projects'" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
        <path v-else-if="item.icon === 'stats'" d="M4 20h16M4 20V10m0 10h4V14m-4 0h4m0 6V10m0 0h4m0 10V6m0 0h4v14" />
        <path v-else-if="item.icon === 'profile'" d="M12 12a4 4 0 100-8 4 4 0 000 8zm0 2c-4 0-8 3-8 7h16c0-4-4-7-8-7z" />
      </svg>
      <span class="bottom-nav__label">{{ item.label }}</span>
    </router-link>
  </nav>
</template>
 
<script setup>
import { useRoute } from 'vue-router'
 
const route = useRoute()
 
const navItems = [
  { path: '/account',  label: 'Вывод',      icon: 'withdrawal' },
  { path: '/projects', label: 'Проекты',    icon: 'projects' },
  { path: '/stats',    label: 'Статистика', icon: 'stats' },
  { path: '/profile',  label: 'Профиль',    icon: 'profile' },
]
 
function isActive(path) {
  if (path === '/account')  return route.path === '/account'  || route.path === '/create-withdrawal'
  if (path === '/projects') return route.path === '/projects' || route.path === '/create-project'
  return route.path === path
}
</script>
 
<style scoped>
.bottom-nav {
  position: fixed; bottom: 0; left: 0; right: 0; height: 60px; background: #fff;
  display: flex; align-items: center; justify-content: space-around;
  border-top: 1px solid #E5E7EB; z-index: 100;
  padding-bottom: env(safe-area-inset-bottom, 0px);
}
.bottom-nav__item {
  display: flex; flex-direction: column; align-items: center; gap: 2px;
  text-decoration: none; color: #9ca3af; font-size: 11px; padding: 6px 12px;
}
.bottom-nav__item--active { color: var(--primary-color, #1d4ed8); }
.bottom-nav__label { font-weight: 500; }
</style>
