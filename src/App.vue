<template>
  <div class="min-h-screen surface-ground">
    <!-- Mobile header -->
    <MobileHeader @toggle-menu="sidebarVisible = !sidebarVisible" />
 
    <!-- Mobile sidebar overlay -->
    <div
      v-if="sidebarVisible && isAuthenticated"
      class="lg:hidden fixed inset-0 z-5"
    >
      <div class="absolute inset-0 bg-black-alpha-50" @click="sidebarVisible = false"></div>
      <div class="absolute left-0 top-0 bottom-0 w-19rem surface-section z-6 shadow-4 overflow-y-auto">
        <AppSidebar @navigate="sidebarVisible = false" />
      </div>
    </div>
 
    <div class="grid relative m-0 p-0">
      <!-- Desktop sidebar -->
      <div
        v-if="isAuthenticated"
        class="col-fixed w-19rem col pr-0 mr-0 surface-section h-screen hidden lg:block flex-shrink-0 absolute lg:static left-0 top-0 z-1 border-right-1 surface-border select-none"
      >
        <AppSidebar />
      </div>
 
      <!-- Main content -->
      <div class="col p-0 m-0 overflow-hidden">
        <div class="pb-7 lg:pb-0">
          <router-view />
        </div>
      </div>
    </div>
 
    <!-- Mobile bottom tab bar -->
    <MobileTabBar />
  </div>
</template>
 
<script setup>
import { ref } from 'vue'
import AppSidebar from '@/components/AppSidebar.vue'
import MobileHeader from '@/components/MobileHeader.vue'
import MobileTabBar from '@/components/MobileTabBar.vue'
import { useAuth } from '@/composables/useAuth.js'
 
const { isAuthenticated } = useAuth()
const sidebarVisible = ref(false)
</script>
