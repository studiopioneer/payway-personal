<template>
  <div>
    <div class="mt-5 mb-5">
      Здесь можно добавить проекты, которые вы хотите монетизировать с помощью payway.store. Пожалуйста, заполняйте поля подробно, отвечая на все вопросы по вашему типу проекта.
    </div>
    <form @submit.prevent="handleSubmit" class="p-fluid">
      <div class="grid formgrid">
        <div class="col-12 md:col-6">
          <div class="field flex flex-column gap-2">
            <label for="url">Ссылка на проект (обязательно)</label>
            <InputText
            class="w-full"
              id="url"
              name="url"
              v-model="form.url"
              :class="{ 'p-invalid': errors.url }"
              placeholder="Пример: ваша-ссылка.ру"
              required
              @input="validate('url', form.url)"
            />
            <small v-if="errors.url" class="p-error block">{{ errors.url }}</small>
            <small class="p-text-secondary">Ссылка на ваш сайт, YouTube канал, приложение и т.д.</small>
          </div>
          <div class="field flex flex-column gap-2">
            <label for="amount">Оборот проекта в месяц (в долларах)</label>
            <InputNumber
            class="w-full"
              id="amount"
              name="amount"
              v-model="form.amount"
              :class="{ 'p-invalid': errors.amount }"
              placeholder="Пример: 3000"
              mode="currency"
              currency="USD"
              locale="ru-RU"
              @input="e => validate('amount', e.value)"
            />
            <small v-if="errors.amount" class="p-error block">{{ errors.amount }}</small>
            <small class="p-text-secondary">Была ли монетизация этого проекта? Если да, какой примерно был оборот в месяц (в долларах).</small>
          </div>
          <div class="field flex flex-column gap-2">
            <label for="contacts">Укажите, как с вами можно связаться? (обязательно)</label>
            <InputText
            class="w-full"
              id="contacts"
              name="contacts"
              v-model="form.contacts"
              :class="{ 'p-invalid': errors.contacts }"
              placeholder="Telegram/Email"
              required
              @input="validate('contacts', form.contacts)"
            />
            <small v-if="errors.contacts" class="p-error block">{{ errors.contacts }}</small>
            <small class="p-text-secondary">Быстрее всего мы ответим в Telegram, либо можете указать ваш email для связи.</small>
          </div>
        </div>
        <div class="col-12 md:col-6">
          <div class="field flex flex-column gap-2">
            <label for="count_users">Количество активных пользователей</label>
            <InputText
            class="w-full"
              id="count_users"
              name="count_users"
              v-model="form.count_users"
              :class="{ 'p-invalid': errors.count_users }"
              placeholder="Пример: 50000"
              @input="validate('count_users', form.count_users)"
            />
            <small v-if="errors.count_users" class="p-error block">{{ errors.count_users }}</small>
            <small class="p-text-secondary">Приблизительное количество ежемесячных пользователей.</small>
          </div>
          <div class="field flex flex-column gap-2">
            <label for="comments">Комментарий</label>
            <Textarea
            class="w-full"
              id="comments"
              name="comments"
              v-model="form.comments"
              :class="{ 'p-invalid': errors.comments }"
              placeholder="Дополнительная информация о проекте"
              :autoResize="true"
              rows="5"
              @input="validate('comments', form.comments)"
            />
            <small v-if="errors.comments" class="p-error block">{{ errors.comments }}</small>
          </div>
        </div>
      </div>
      <Button
        type="submit"
        label="Отправить проект на проверку"
        icon="pi pi-send"
        class="mt-3 w-15rem bg-blue-500 hover:bg-blue-600 border-blue-600 hover:border-blue-700"
      />
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import api from '@/api/index.js'
import { useToastStore } from '@/stores/toast.js'

const router = useRouter()
const toastStore = useToastStore()

const form = reactive({
  url: '',
  amount: '',
  count_users: '',
  comments: '',
  contacts: ''
})

const errors = reactive({})

function validateField(field, value) {
  let error = ''
  if (value == null) value = ''
  switch (field) {
    case 'url':
      if (!value) error = 'Поле ссылка на проект обязательно для заполнения!'
      else if (value.length > 350) error = 'Длина URL не должна превышать 350 символов!'
      else if (!/(http(s)?:\/\/.)?(www\.)?[-a-zA-Z\u0401\u0451\u0410-\u044f0-9@:%._\+~#=]{2,256}\.[a-z\u0401\u0451\u0410-\u044f]{2,6}/i.test(value))
        error = 'Некорректный URL адрес! Пример допустимых URL адресов: http://site.ru, https://мойсайт.рф'
      break
    case 'amount':
      if (String(value).length > 50) error = 'Длина этого поля не должна превышать 50 символов!'
      else if (value && !/^\d*\.?\d*$/.test(String(value))) error = 'Введена некорректная сумма! Используйте только целые числа и числа с плавающей точкой.'
      break
    case 'count_users':
      if (String(value).length > 50) error = 'Длина этого поля не должна превышать 50 символов!'
      else if (value && !/^\d+$/.test(String(value))) error = 'Введено некорректное количество пользователей! Используйте только целые числа.'
      break
    case 'comments':
      if (String(value).length > 500) error = 'Длина этого поля не должна превышать 500 символов!'
      break
    case 'contacts':
      if (!value) error = 'Поле контакты обязательно для заполнения!'
      else if (value.length > 250) error = 'Длина этого поля не должна превышать 250 символов!'
      break
  }
  return error
}

function validate(field, value) {
  errors[field] = validateField(field, value)
}

async function handleSubmit() {
  const validationErrors = {}
  Object.keys(form).forEach(field => {
    const err = validateField(field, form[field])
    if (err) validationErrors[field] = err
  })
  if (Object.keys(validationErrors).length > 0) {
    Object.assign(errors, validationErrors)
    toastStore.showToast('Пожалуйста, исправьте ошибки в форме.', 'error')
    return
  }
  try {
    await api.post('/projects', form)
    Object.assign(form, { url: '', amount: '', count_users: '', comments: '', contacts: '' })
    toastStore.showToast('Проект успешно отправлен на проверку!', 'success')
    router.push('/projects')
  } catch (err) {
    console.error('Ошибка при отправке формы:', err)
    toastStore.showToast(err.response?.data?.message || 'Произошла ошибка при отправке формы.', 'error')
  }
}
</script>
