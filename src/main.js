import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import ToastService from 'primevue/toastservice'
import Ripple from 'primevue/ripple'
import router from './router'
import App from './App.vue'

import 'primeicons/primeicons.css'
import 'primeflex/primeflex.css'

// Global sans-serif font override
const style = document.createElement('style')
style.textContent = 'body, html, #root, * { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important; } .pi, .pi::before { font-family: "primeicons" !important; }'
document.head.appendChild(style)

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: false,
    },
  },
  ripple: true,
})
app.use(ToastService)
app.directive('ripple', Ripple)

app.mount('#root')
