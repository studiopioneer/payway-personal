<template>
  <header class="mobile-header">
    <button class="mobile-header__burger" @click="$emit('toggle-drawer')" aria-label="Открыть меню">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round">
        <line x1="3" y1="6" x2="21" y2="6" />
        <line x1="3" y1="12" x2="21" y2="12" />
        <line x1="3" y1="18" x2="21" y2="18" />
      </svg>
    </button>
    <span class="mobile-header__logo">PAYWAY</span>
    <div class="mobile-header__avatar">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="1.5">
        <circle cx="12" cy="8" r="4" />
        <path d="M4 20c0-4 4-7 8-7s8 3 8 7" />
      </svg>
    </div>
  </header>
</template>
 
<script setup>
defineEmits(['toggle-drawer'])
</script>
 
<style scoped>
.mobile-header {
  position: fixed; top: 0; left: 0; right: 0; height: 56px; background: #fff;
  display: flex; align-items: center; justify-content: space-between; padding: 0 16px;
  border-bottom: 1px solid #E5E7EB; z-index: 100;
}
.mobile-header__burger {
  background: none; border: none; padding: 8px; margin: -8px; cursor: pointer;
  color: #374151; display: flex; align-items: center; justify-content: center;
}
.mobile-header__logo {
  font-size: 18px; font-weight: 700;
  color: var(--primary-color, #1d4ed8); letter-spacing: 1px;
}
.mobile-header__avatar {
  width: 36px; height: 36px; border-radius: 50%; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center;
}
</style>
