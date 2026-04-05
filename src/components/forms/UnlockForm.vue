<template>
  <div>
    <Toast ref="toastRef" />
    <div class="mt-5 mb-5">
      Вы можете преждевременно снять все или часть своих средств не дожидаясь даты выплаты. Поскольку досрочные выплаты осуществляются из собственных средств, за это взимается дополнительная комиссия, размер которой 5%, но может измениться в зависимости от суммы. Доступная для разблокировки сумма имеет ограничение. Оставшаяся сумма, указанная ниже, является общей для всех пользователей платформы и может изменяться.
    </div>
    <form @submit.prevent="handleSubmit" class="payway-draw">
      <div class="pt-6 w-full">
        <div class="grid formgrid p-fluid mb-4">
          <div class="col-12 md:col-6">
            <div class="flex flex-column gap-3">
              <label for="amount" class="block">Сумма к разблокировке</label>
              <InputNumber
                id="amount"
                class="w-full"
                v-model="amount"
                :required="true"
                mode="currency"
                currency="USD"
                locale="ru-RU"
                :minFractionDigits="2"
                :maxFractionDigits="2"
                :min="0"
                :max="maxAmount"
                @input="onAmountChange"
              />
              <Button
                type="button"
                label="Максимальная сумма"
                class="p-button-outlined mt-2 text-700 border-blue-600 hover:border-blue-700"
                @click="setMaxAmount"
              />
            </div>
          </div>
          <div class="col-12 md:col-6">
            <div class="p-3 mt-5 surface-card border-1 surface-border border-round">
              <div class="text-900 font-medium mb-3">Расчет комиссии</div>
              <Divider />
              <div class="flex flex-column gap-2">
                <div class="flex justify-content-between">
                  <span>Комиссия ({{ commissionRate }}%)</span>
                  <span>${{ commission.toFixed(2) }}</span>
                </div>
                <div class="flex justify-content-between">
                  <span>Вы получите на счёт</span>
                  <span>${{ netAmount.toFixed(2) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="flex flex-column gap-3 mt-4">
          <label for="comments" class="block">Примечание (Необязательно)</label>
          <Textarea
            id="comments"
            class="w-full"
            placeholder="Оставьте комментарий"
            v-model="comments"
            :autoResize="false"
          />
        </div>
      </div>
      <Button
        type="submit"
        label="Создать заявку"
        class="mt-3 bg-blue-500 hover:bg-blue-600 border-blue-600 hover:border-blue-700"
      />
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import Toast from 'primevue/toast'
import api from '@/api/index.js'
import { useToastStore } from '@/stores/toast.js'

const router = useRouter()
const toastStore = useToastStore()
const toastRef = ref(null)

const amount = ref(0)
const comments = ref('')
const commission = ref(0)
const netAmount = ref(0)
const maxAmount = 11203.43
const commissionRate = 5

function calculateCommission(val) {
  const comm = val * commissionRate / 100
  const net = val - comm
  commission.value = comm
  netAmount.value = net
}

function onAmountChange(event) {
  let val = event.value
  if (isNaN(val) || val < 0) val = 0
  if (val > maxAmount) val = maxAmount
  amount.value = val
  calculateCommission(val)
}

function setMaxAmount() {
  amount.value = maxAmount
  calculateCommission(maxAmount)
}

async function handleSubmit() {
  const payload = {
    amount: amount.value,
    comments: comments.value
  }
  try {
    await api.post('/unlock', payload)
    amount.value = 0
    comments.value = ''
    commission.value = 0
    netAmount.value = 0
    toastStore.showToast('Заявка на разблокировку средств успешно создана!', 'success')
    router.push('/unlock')
  } catch (err) {
    console.error('Ошибка при отправке формы:', err)
    if (err.response) {
      toastRef.value.show({
        severity: 'error',
        summary: 'Ошибка',
        detail: err.response.data.message || 'Произошла ошибка при отправке формы.',
        life: 3000
      })
    } else {
      toastRef.value.show({
        severity: 'error',
        summary: 'Ошибка',
        detail: 'Сервер недоступен. Попробуйте позже.',
        life: 3000
      })
    }
  }
}
</script>
