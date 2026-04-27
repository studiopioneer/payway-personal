import { ref, onMounted, onBeforeUnmount } from 'vue'
 
const MOBILE_BREAKPOINT = 768
 
export function useIsMobile() {
  const isMobile = ref(false)
  let mq = null
 
  function handleChange(e) {
    isMobile.value = e.matches
  }
 
  onMounted(() => {
    mq = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT}px)`)
    isMobile.value = mq.matches
    mq.addEventListener('change', handleChange)
  })
 
  onBeforeUnmount(() => {
    if (mq) mq.removeEventListener('change', handleChange)
  })
 
  return { isMobile }
}
