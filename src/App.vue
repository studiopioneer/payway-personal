<template>
  <div class="grid relative surface-ground m-0 p-0">
    <!-- Sidebar - only shown when authenticated -->
    <div
      v-if="isAuthenticated"
      class="col-fixed w-19rem col pr-0 mr-0 surface-section h-screen hidden lg:block flex-shrink-0 absolute lg:static left-0 top-0 z-1 border-right-1 surface-border select-none"
    >
      <AppSidebar />
    </div>

    <!-- Main content -->
    <div class="col p-0 m-0 overflow-hidden">
      <div>
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import AppSidebar from '@/components/AppSidebar.vue'
import { useAuth } from '@/composables/useAuth.js'

const { isAuthenticated } = useAuth()
</script>
