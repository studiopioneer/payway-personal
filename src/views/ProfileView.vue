<template>
  <div class="p-3">
    <Message v-if="successMessage" severity="success" :text="successMessage" class="mb-4" />
    <form id="payway-edit-profile-form" @submit.prevent="handleSubmit" autocomplete="off">
      <div class="grid formgrid">
        <div class="col-12 mb-4">
          <label for="name" class="block mb-2">Имя (Обязательно)</label>
          <InputText
            id="name"
            name="name"
            v-model="form.name"
            placeholder="Ваше имя"
            required
            class="w-full"
          />
        </div>
        <div class="col-12 mb-4">
          <label for="email" class="block mb-2">Почта (Обязательно)</label>
          <InputText
            id="email"
            name="email"
            type="email"
            v-model="form.email"
            placeholder="Ваш email"
            required
            class="w-full"
          />
        </div>
        <div class="col-12 mb-4">
          <label for="password" class="block mb-2">Новый пароль</label>
          <Password
            id="password"
            name="password"
            v-model="form.password"
            toggleMask
            :feedback="false"
            class="w-full"
          />
        </div>
        <div class="col-12 mb-4">
          <label for="repeatPassword" class="block mb-2">Повторите пароль</label>
          <Password
            id="repeatPassword"
            name="repeatPassword"
            v-model="form.repeatPassword"
            toggleMask
            :feedback="false"
            class="w-full"
          />
        </div>
        <div class="col-12 mt-3">
          <Button
            label="Сохранить данные"
            icon="pi pi-save"
            type="submit"
            class="p-button-success w-full"
          />
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'

const form = reactive({
  name: '',
  email: '',
  password: '',
  repeatPassword: ''
})

const successMessage = ref('')

function handleSubmit() {
  if (form.password !== form.repeatPassword) {
    alert('Пароли не совпадают')
    return
  }
  successMessage.value = 'Данные успешно сохранены!'
  console.log('Отправлено:', { ...form })
  Object.assign(form, { name: '', email: '', password: '', repeatPassword: '' })
}
</script>
