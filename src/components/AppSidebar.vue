<template>
  <div class="flex flex-column h-full">
    <div class="flex align-items-center px-5 flex-shrink-0" style="height: 60px">
      <PaywayLogo :width="150" :height="70" fill="#3D73FF" />
    </div>
    <div class="overflow-y-auto mt-3">
      <ul class="list-none p-3 m-0">
        <li v-for="item in menuItems" :key="item.path">
          <router-link
            :to="item.path"
            class="no-underline p-ripple flex align-items-center cursor-pointer p-3 border-round transition-duration-150 transition-colors w-full"
            :class="isActive(item.path) ? 'text-blue-700 bg-blue-50' : 'text-700 hover:surface-100'"
            v-ripple
          >
            <i :class="[item.icon, 'mr-2']"></i>
            <span class="font-medium">{{ item.label }}</span>
          </router-link>
        </li>
      </ul>
    </div>
    <div class="mt-auto mx-3">
      <hr class="mb-3 border-top-1 border-200" />
      <ul class="list-none p-0 m-0">
        <li>
          <router-link
            to="/profile"
            class="no-underline p-ripple flex cursor-pointer p-3 border-round text-700 hover:surface-100 transition-duration-150 transition-colors w-full"
            v-ripple
          >
            <i class="pi pi-id-card mr-2"></i>
            <span class="font-medium">Профиль</span>
          </router-link>
        </li>
        <li>
          <a
            href="#"
            class="no-underline p-ripple flex cursor-pointer p-3 border-round text-700 hover:surface-100 transition-duration-150 transition-colors w-full"
            @click.prevent="handleLogout"
            v-ripple
          >
            <i class="pi pi-sign-out mr-2"></i>
            <span class="font-medium">Выход</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import PaywayLogo from './PaywayLogo.vue'
import { useAuth } from '@/composables/useAuth.js'

const route = useRoute()
const router = useRouter()
const { logout } = useAuth()

const menuItems = [
  { path: '/account', icon: 'pi pi-wallet', label: 'Вывод средств' },
  { path: '/unlock', icon: 'pi pi-unlock', label: 'Разблокировка средств' },
  { path: '/projects', icon: 'pi pi-folder-open', label: 'Мои проекты' },
  { path: '/referrals', icon: 'pi pi-users', label: 'Рефералы' },
  { path: '/stats', icon: 'pi pi-chart-bar', label: 'Статистика' },
  { path: '/audit', icon: 'pi pi-search', label: 'Аудит канала' },
  { path: '/audit-history', icon: 'pi pi-history', label: 'История аудитов' },
]

function isActive(path) { return route.path === path }

function handleLogout() {
  logout()
  router.push('/login')
}
</script>
