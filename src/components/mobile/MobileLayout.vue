<template>
  <div class="mobile-layout">
    <MobileHeader @toggle-drawer="drawerOpen = true" />
    <main class="mobile-layout__content">
      <slot />
    </main>
    <BottomNav />
    <MobileDrawer v-model="drawerOpen" :user-name="userName" :user-email="userEmail" />
  </div>
</template>
 
<script setup>
import { ref, onMounted } from 'vue'
import MobileHeader  from './MobileHeader.vue'
import BottomNav     from './BottomNav.vue'
import MobileDrawer  from './MobileDrawer.vue'
 
const drawerOpen = ref(false)
const userName   = ref('Пользователь')
const userEmail  = ref('')
 
onMounted(() => {
  if (window.paywayUser) {
    userName.value  = window.paywayUser.name  || 'Пользователь'
    userEmail.value = window.paywayUser.email || ''
  }
})
</script>
 
<style scoped>
.mobile-layout { min-height: 100vh; background: #F9FAFB; }
.mobile-layout__content {
  padding-top: 56px;
  padding-bottom: calc(60px + env(safe-area-inset-bottom, 0px));
  min-height: 100vh;
}
</style>
