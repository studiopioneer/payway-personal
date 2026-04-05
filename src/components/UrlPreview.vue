<template>
  <div class="flex align-items-center">
    <Skeleton v-if="loading" width="10rem" height="1rem" />
    <template v-else>
      <i :class="iconClass" :style="iconStyle"></i>
      <a :href="url" target="_blank" rel="noopener noreferrer" class="ml-2 text-sm">{{ title }}</a>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Skeleton from 'primevue/skeleton'

const props = defineProps({
  url: { type: String, required: true }
})

const title = ref(null)
const loading = ref(true)

const isYoutube = computed(() =>
  /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:@[^\/\n\s]+|[^\/\n\s]+\/\S+\/|(?:v|embed)\/|\S*?[?&]v=)|youtu\.be\/)/.test(props.url)
)

const isGooglePlay = computed(() =>
  /https?:\/\/play\.google\.com\/store/.test(props.url)
)

const iconClass = computed(() => {
  if (isYoutube.value) return 'pi pi-youtube'
  if (isGooglePlay.value) return 'pi pi-google'
  return 'pi pi-globe'
})

const iconStyle = computed(() => {
  if (isYoutube.value) return { color: 'red' }
  if (isGooglePlay.value) return { color: '#4285F4' }
  return { color: '#666' }
})

onMounted(async () => {
  try {
    const cached = localStorage.getItem(props.url)
    if (cached) {
      title.value = cached
      loading.value = false
      return
    }
    let fetchUrl = props.url
    if (props.url.startsWith('@')) {
      fetchUrl = `https://www.youtube.com/${props.url}`
    }
    const resp = await fetch(`https://api.allorigins.win/get?url=${encodeURIComponent(fetchUrl)}`)
    const json = await resp.json()
    const doc = new DOMParser().parseFromString(json.contents, 'text/html')
    const pageTitle = doc.querySelector('title')?.textContent || props.url
    localStorage.setItem(props.url, pageTitle)
    title.value = pageTitle
  } catch (err) {
    console.error('Ошибка при получении заголовка:', err)
    title.value = props.url
  } finally {
    loading.value = false
  }
})
</script>
