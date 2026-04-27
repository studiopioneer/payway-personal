<template>
  <Teleport to="body">
    <Transition name="bottom-sheet">
      <div v-if="modelValue" class="bottom-sheet-wrapper">
        <div class="overlay" @click="close" />
        <div class="sheet" :style="{ maxHeight }">
          <div class="handle-bar" />
          <div class="sheet-header">
            <h3 v-if="title" class="sheet-title">{{ title }}</h3>
            <button class="close-btn" @click="close"><i class="pi pi-times" /></button>
          </div>
          <div class="sheet-content"><slot /></div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
 
<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title:      { type: String,  default: '' },
  maxHeight:  { type: String,  default: '85vh' },
})
 
const emit = defineEmits(['update:modelValue'])
function close() { emit('update:modelValue', false) }
</script>
 
<style scoped>
.bottom-sheet-wrapper { position: fixed; inset: 0; z-index: 300; }
.overlay { position: absolute; inset: 0; background: rgba(0,0,0,.4); }
.sheet {
  position: absolute; bottom: 0; left: 0; right: 0; background: #fff;
  border-radius: 16px 16px 0 0; overflow-y: auto;
  padding-bottom: env(safe-area-inset-bottom, 0px);
}
.handle-bar {
  width: 40px; height: 4px; background: #D1D5DB;
  border-radius: 2px; margin: 12px auto 0;
}
.sheet-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; border-bottom: 1px solid #E5E7EB;
}
.sheet-title  { font-size: 17px; font-weight: 600; color: #111827; margin: 0; }
.close-btn    { background: none; border: none; cursor: pointer; padding: 4px; color: #6b7280; }
.sheet-content { padding: 20px; }
 
.bottom-sheet-enter-active, .bottom-sheet-leave-active { transition: opacity .25s ease; }
.bottom-sheet-enter-from, .bottom-sheet-leave-to { opacity: 0; }
.bottom-sheet-enter-active .sheet, .bottom-sheet-leave-active .sheet
  { transition: transform .3s cubic-bezier(.16,1,.3,1); }
.bottom-sheet-enter-from .sheet, .bottom-sheet-leave-to .sheet
  { transform: translateY(100%); }
</style>
