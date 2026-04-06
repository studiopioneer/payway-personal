<template>
  <div>
    <div class="auth-page">
      <Toast ref="toastRef" />
      <div class="auth-form-container">
        <div class="text-center mb-5">
          <PaywayLogo :width="150" :height="70" fill="#3D73FF" class="mb-3" />
          <div class="text-500 text-3xl font-medium mb-3">
            {{ isRegistration ? 'Регистрация аккаунта' : 'Вход в личный кабинет' }}
          </div>
          <span class="text-600 font-medium line-height-3">
            {{ isRegistration ? 'Уже есть аккаунт?' : 'Еще нет аккаунта?' }}
          </span>
          <a
            class="font-medium no-underline ml-2 text-blue-500 cursor-pointer"
            @click="isRegistration = !isRegistration"
          >
            {{ isRegistration ? 'Войти' : 'Зарегистрируйтесь!' }}
          </a>
        </div>
        <form @submit.prevent="handleSubmit">
          <div class="mb-3">
            <label for="email" class="block text-900 font-medium mb-2">Email</label>
            <InputText
              id="email"
              v-model="email"
              placeholder="Введите email"
              class="w-full"
              required
            />
          </div>
          <div class="mb-3">
            <label for="password" class="block text-900 font-medium mb-2">Пароль</label>
            <Password
              id="password"
              v-model="password"
              placeholder="Введите пароль"
              class="w-full"
              inputClass="w-full"
              :feedback="false"
              required
              toggleMask
            />
          </div>
          <template v-if="isRegistration">
            <div class="mb-3">
              <label for="confirmPassword" class="block text-900 font-medium mb-2">Повторите пароль</label>
              <div class="p-inputgroup">
                <Password
                  id="confirmPassword"
                  v-model="confirmPassword"
                  placeholder="Повторите пароль"
                  class="w-full"
                  inputClass="w-full"
                  :feedback="false"
                  required
                  toggleMask
                />
                <span v-if="passwordsMatch && confirmPassword" class="p-inputgroup-addon p-password-match">
                  <i class="pi pi-check" style="color: green"></i>
                </span>
              </div>
            </div>
            <div class="mb-3 flex align-items-center text-sm">
              <Checkbox
                inputId="agreeTerms"
                v-model="agreeTerms"
                :binary="true"
                required
              />
              <label for="agreeTerms" class="p-checkbox-label">
                Я прочитал(а)
                <a href="/terms" class="p-checkbox-link">пользовательское соглашение</a>
                и
                <a href="/privacy" class="p-checkbox-link">политику конфиденциальности</a>
              </label>
            </div>
            <div class="mb-3 flex align-items-center text-sm">
              <Checkbox
                inputId="agreePrivacy"
                v-model="agreePrivacy"
                :binary="true"
                required
              />
              <label for="agreePrivacy" class="p-checkbox-label">
                Я согласен на
                <a href="/data-processing" class="p-checkbox-link">обработку персональных данных</a>
              </label>
            </div>
          </template>
          <Button
            type="submit"
            class="p-button p-component w-full mt-3 bg-blue-500 hover:bg-blue-600"
          >
            <span class="p-button-icon p-c p-button-icon-left pi pi-user"></span>
            <span class="p-button-label p-c">{{ isRegistration ? 'Зарегистрироваться' : 'Войти' }}</span>
          </Button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import PaywayLogo from '@/components/PaywayLogo.vue'
import { useAuth } from '@/composables/useAuth.js'
import { loginUser, registerUser } from '@/api/auth.js'

const router = useRouter()
const { isAuthenticated, login } = useAuth()
const toastRef = ref(null)
const toast = useToast()

const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const isRegistration = ref(false)
const agreeTerms = ref(false)
const agreePrivacy = ref(false)

const passwordsMatch = computed(() => password.value === confirmPassword.value)

watch(isAuthenticated, (val) => {
  if (val) router.push('/account')
}, { immediate: true })

function handleError(err) {
  if (err.response && err.response.data) {
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: err.response.data.message || 'Произошла ошибка',
      life: 3000
    })
  } else {
    toast.add({
      severity: 'error',
      summary: 'Ошибка соединения',
      detail: 'Сервер недоступен. Попробуйте позже.',
      life: 3000
    })
  }
}

async function handleSubmit() {
  try {
    if (isRegistration.value) {
      if (password.value !== confirmPassword.value) {
        toast.add({ severity: 'error', summary: 'Ошибка', detail: 'Пароли не совпадают', life: 3000 })
        return
      }
      if (!agreeTerms.value || !agreePrivacy.value) {
        toast.add({ severity: 'error', summary: 'Ошибка', detail: 'Необходимо согласиться с условиями', life: 3000 })
        return
      }
      await registerUser(email.value, password.value)
      toast.add({ severity: 'success', summary: 'Успешно', detail: 'Регистрация прошла успешно!', life: 3000 })
      isRegistration.value = false
    } else {
      const data = await loginUser(email.value, password.value)
      login(data.token)
      toast.add({ severity: 'success', summary: 'Успешно', detail: 'Вы успешно авторизовались!', life: 3000 })
      router.push('/account')
    }
  } catch (err) {
    handleError(err)
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding-right: 10%;
  position: relative;
  overflow: hidden;
}

.auth-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: url('https://payway.store/wp-content/uploads/2025/01/home-3-2-scaled.jpg');
  background-size: cover;
  background-position: center;
  transform: scaleX(-1);
}

.auth-form-container {
  background: rgba(255, 255, 255, 0.9);
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 450px;
  position: relative;
  z-index: 1;
}

.p-password-match {
  color: green;
}

.p-checkbox-label {
  margin-left: 0.5rem;
}

.p-checkbox-link {
  color: #3D73FF;
  text-decoration: none;
}

.p-checkbox-link:hover {
  text-decoration: underline;
}
</style>
