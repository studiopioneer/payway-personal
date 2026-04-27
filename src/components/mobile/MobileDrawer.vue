<template>
  <Teleport to="body">
    <Transition name="drawer">
      <div v-if="modelValue" class="mobile-drawer-overlay" @click.self="close">
        <aside class="mobile-drawer">
          <div class="mobile-drawer__header">
            <div class="mobile-drawer__avatar">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                stroke="#6B7280" stroke-width="1.5">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 20c0-4 4-7 8-7s8 3 8 7"/>
              </svg>
            </div>
            <div class="mobile-drawer__user">
              <div class="mobile-drawer__name">{{ userName }}</div>
              <div class="mobile-drawer__email">{{ userEmail }}</div>
            </div>
          </div>
 
          <nav class="mobile-drawer__nav">
            <!-- Аудит и История аудита намеренно отсутствуют -->
            <router-link
              v-for="item in navItems"
              :key="item.path"
              :to="item.path"
              class="mobile-drawer__link"
              @click="close"
            >
              {{ item.label }}
            </router-link>
          </nav>
 
          <div class="mobile-drawer__footer">
            <button class="mobile-drawer__logout" @click="handleLogout">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
              </svg>
              Выйти
            </button>
          </div>
        </aside>
      </div>
    </Transition>
  </Teleport>
</template>
 
<script setup>
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth.js'
 
const props = defineProps({
  modelValue: Boolean,
  userName:   { type: String, default: 'Пользователь' },
  userEmail:  { type: String, default: '' },
})
 
const emit = defineEmits(['update:modelValue'])
const router = useRouter()
const { logout } = useAuth()
 
const navItems = [
  { path: '/account',   label: 'Вывод средств' },
  { path: '/projects',  label: 'Проекты' },
  { path: '/stats',     label: 'Статистика' },
  { path: '/referrals', label: 'Рефералы' },
  { path: '/profile',   label: 'Профиль' },
]
 
function close() { emit('update:modelValue', false) }
function handleLogout() { close(); logout(); router.push('/login') }
</script>
 
<style scoped>
.mobile-drawer-overlay {
  position: fixed; top: 0; right: 0; bottom: 0; left: 0;
  background: rgba(0,0,0,.3); z-index: 200;
}
.mobile-drawer {
  position: absolute; top: 0; left: 0; bottom: 0; width: 280px; background: #fff;
  display: flex; flex-direction: column; box-shadow: 4px 0 12px rgba(0,0,0,.1);
}
.mobile-drawer__header {
  padding: 24px 20px; border-bottom: 1px solid #E5E7EB;
  display: flex; align-items: center; gap: 12px;
}
.mobile-drawer__avatar {
  width: 48px; height: 48px; border-radius: 50%; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.mobile-drawer__name  { font-size: 15px; font-weight: 600; color: #111827; }
.mobile-drawer__email { font-size: 13px; color: #6b7280; margin-top: 2px; }
.mobile-drawer__nav   { flex: 1; padding: 12px 0; overflow-y: auto; }
.mobile-drawer__link  {
  display: block; padding: 12px 20px; color: #374151;
  text-decoration: none; font-size: 15px;
}
.mobile-drawer__link.router-link-active {
  color: var(--primary-color, #1d4ed8);
  font-weight: 600; background: #eff6ff;
}
.mobile-drawer__footer { padding: 16px 20px; border-top: 1px solid #E5E7EB; }
.mobile-drawer__logout {
  display: flex; align-items: center; gap: 8px; background: none;
  border: none; color: #dc2626; font-size: 15px; cursor: pointer; padding: 8px 0; width: 100%;
}
 
.drawer-enter-active, .drawer-leave-active { transition: opacity .3s ease; }
.drawer-enter-from, .drawer-leave-to { opacity: 0; }
.drawer-enter-active .mobile-drawer, .drawer-leave-active .mobile-drawer
  { transition: transform .3s cubic-bezier(.16,1,.3,1); }
.drawer-enter-from .mobile-drawer, .drawer-leave-to .mobile-drawer
  { transform: translateX(-100%); }
</style>
