<template>
  <div
    v-if="isAuthenticated"
    class="lg:hidden flex align-items-center justify-content-between px-3 surface-section border-bottom-1 surface-border"
    style="height: 56px"
  >
    <button
      class="p-link inline-flex align-items-center justify-content-center cursor-pointer border-none bg-transparent"
      @click="$emit('toggle-menu')"
    >
      <i class="pi pi-bars text-xl text-900"></i>
    </button>
    <router-link to="/account" class="no-underline">
      <span class="text-900 font-bold text-xl" style="letter-spacing: 0.05em">PAYWAY</span>
    </router-link>
    <router-link to="/profile" class="no-underline">
      <i class="pi pi-user text-xl text-600"></i>
    </router-link>
  </div>
</template>
 
<script setup>
import { useAuth } from '@/composables/useAuth.js'
 
const { isAuthenticated } = useAuth()
 
defineEmits(['toggle-menu'])
</script>
