<template>
  <!-- МОБИЛЬНЫЙ ЛЕЙАУТ (<= 768px) -->
  <template v-if="isMobile && isAuthenticated">
    <MobileLayout>
      <router-view />
    </MobileLayout>
  </template>
 
  <!-- ДЕСКТОПНЫЙ ЛЕЙАУТ (> 768px) — без изменений -->
  <template v-else>
    <div class="grid relative surface-ground m-0 p-0">
      <div
        v-if="isAuthenticated"
        class="col-fixed w-19rem col pr-0 mr-0 surface-section h-screen hidden lg:block flex-shrink-0 absolute lg:static left-0 top-0 z-1 border-right-1 surface-border select-none"
      >
        <AppSidebar />
      </div>
      <div class="col p-0 m-0 overflow-hidden">
        <router-view />
      </div>
    </div>
  </template>
</template>
 
<script setup>
import AppSidebar   from '@/components/AppSidebar.vue'
import MobileLayout from '@/components/mobile/MobileLayout.vue'
import { useAuth }     from '@/composables/useAuth.js'
import { useIsMobile } from '@/composables/useIsMobile.js'
 
const { isAuthenticated } = useAuth()
const { isMobile }        = useIsMobile()
</script>
