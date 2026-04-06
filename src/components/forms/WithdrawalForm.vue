<template>
  <div>
    <Toast ref="toastRef" />
    <form @submit.prevent="handleSubmit" class="payway-draw">
      <div class="pt-6 w-full">
        <div class="grid formgrid p-fluid mb-4">
          <div class="col-12 md:col-6">
            <div class="flex flex-column gap-3">
              <label for="amount" class="block">Сумма к выводу</label>
              <InputNumber
                id="amount"
                class="w-full"
                v-model="amount"
                :required="true"
              />
            </div>
            <div class="flex flex-column gap-3 mt-4">
              <label for="details" class="block">Реквизиты</label>
              <InputText
                id="details"
                class="w-full"
                type="text"
                v-model="paymentDetails"
                :required="true"
              />
            </div>
          </div>
          <div class="col-12 md:col-6">
            <div class="flex flex-column gap-3 h-full">
              <label for="comments" class="block">Примечание (Необязательно)</label>
              <Textarea
                id="comments"
                class="w-full flex-grow-1 h-full"
                placeholder="Оставьте комментарий"
                v-model="comments"
                :autoResize="false"
              />
            </div>
          </div>
        </div>

        <!-- Payment method selector -->
        <div>
          <div class="text-900 text-xl mb-3 text-left font-medium">Выберите способ оплаты</div>
          <div
            v-for="method in paymentMethods"
            :key="method.value"
            class="surface-card mb-2 border-2 p-3 flex flex-column align-items-start cursor-pointer"
            :class="paymentType === method.value ? 'border-primary' : 'surface-border'"
            @click="paymentType = method.value"
          >
            <div class="flex align-items-center w-full">
              <div class="p-radiobutton p-component mr-3" :class="paymentType === method.value ? 'p-radiobutton-checked' : ''">
                <div class="p-hidden-accessible">
                  <input type="radio" name="payment_type" :value="method.value" :checked="paymentType === method.value" readonly />
                </div>
                <div class="p-radiobutton-box" :class="paymentType === method.value ? 'p-highlight' : ''">
                  <div class="p-radiobutton-icon"></div>
                </div>
              </div>
              <div class="flex-1">
                <div class="font-medium">{{ method.label }}</div>
              </div>
              <div class="ml-auto flex flex-nowrap">
                <i :class="method.iconClass + ' text-xl'"></i>
              </div>
            </div>
            <div v-if="paymentType === method.value" class="mt-3 text-sm text-400 fadein animation-duration-300">
              {{ method.description }}
            </div>
          </div>
        </div>

        <!-- Commission calculation per payment type -->
        <div v-if="paymentType === 'swift' && amount > 0" class="surface-card border-1 surface-border p-3 mt-3 border-round">
          <div class="flex justify-content-between mb-2">
            <span class="text-600">Сумма к выводу:</span>
            <span class="font-medium">{{ amount }}</span>
          </div>
          <div class="flex justify-content-between mb-2">
            <span class="text-600">Комиссия ({{ SWIFT_RATE }}%):</span>
            <span class="font-medium text-red-500">- {{ swiftCommission }}</span>
          </div>
          <div class="flex justify-content-between border-top-1 surface-border pt-2">
            <span class="text-900 font-bold">Вы получите:</span>
            <span class="text-900 font-bold">{{ swiftNet }}</span>
          </div>
        </div>

        <div v-if="paymentType === 'cards' && amount > 0" class="surface-card border-1 surface-border p-3 mt-3 border-round">
          <div class="flex justify-content-between mb-2">
            <span class="text-600">Сумма к выводу:</span>
            <span class="font-medium">{{ amount }}</span>
          </div>
          <div class="flex justify-content-between mb-2">
            <span class="text-600">Комиссия ({{ CARDS_RATE }}%):</span>
            <span class="font-medium text-red-500">- {{ cardsCommission }}</span>
          </div>
          <div class="flex justify-content-between border-top-1 surface-border pt-2">
            <span class="text-900 font-bold">Вы получите:</span>
            <span class="text-900 font-bold">{{ cardsNet }}</span>
          </div>
        </div>

        <div v-if="paymentType === 'cryptocurrency' && amount > 0" class="surface-card border-1 surface-border p-3 mt-3 border-round">
          <div class="flex justify-content-between mb-2">
            <span class="text-600">Сумма к выводу:</span>
            <span class="font-medium">{{ amount }}</span>
          </div>
          <div class="flex justify-content-between mb-2">
            <span class="text-600">Комиссия ({{ CRYPTO_RATE }}%):</span>
            <span class="font-medium text-red-500">- {{ cryptoCommission }}</span>
          </div>
          <div class="flex justify-content-between border-top-1 surface-border pt-2">
            <span class="text-900 font-bold">Вы получите:</span>
            <span class="text-900 font-bold">{{ cryptoNet }}</span>
          </div>
        </div>
      </div>

      <!-- Balance display -->


      <div v-if="userStore.balance !== null" class="mt-3 mb-2">


        <span :style="{ color: userStore.balance >= 0 ? 'green' : 'red' }" class="font-semibold">


          Ваш баланс: {{ userStore.formatBalance(userStore.balance) }}


        </span>


      </div>


      <div v-if="isOverBalance" class="mb-2">


        <span class="text-red-500 text-sm">Сумма вывода превышает ваш баланс. Пожалуйста, укажите меньшую сумму.</span>


      </div>


      <Button


          type="submit"


          label="Создать заявку"


          class="mt-3 bg-blue-500 hover:bg-blue-600 border-blue-600 hover:border-blue-700"


          :disabled="isOverBalance"


      />
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import api from '@/api/index.js'
import { useToastStore } from '@/stores/toast.js'
import { useUserStore } from '@/stores/user.js'

const router = useRouter()
const toastStore = useToastStore()
const userStore = useUserStore()
const toastRef = ref(null)

const amount = ref(0)

onMounted(() => {
  userStore.fetchBalance()
})

const isOverBalance = computed(() => {
  return userStore.balance !== null && amount.value > userStore.balance
})
const paymentDetails = ref('')
const comments = ref('')
const paymentType = ref('swift')
const userBalance = ref(0)
const balanceLoaded = ref(false)

const CRYPTO_RATE = 11
const SWIFT_RATE = 12
const CARDS_RATE = 15

onMounted(async () => {
  try {
    const resp = await api.get('/user/balance')
    const data = resp.data
    const val = data && typeof data === 'object' && data.balance !== undefined ? data.balance : data
    if (val != null && val !== '') {
      userBalance.value = parseFloat(val)
    }
    balanceLoaded.value = true
  } catch (err) {
    console.error('Failed to fetch balance:', err)
    balanceLoaded.value = true
  }
})

const swiftCommission = computed(() => amount.value ? parseFloat((amount.value * SWIFT_RATE / 100).toFixed(2)) : 0)
const swiftNet = computed(() => amount.value ? parseFloat((amount.value - swiftCommission.value).toFixed(2)) : 0)
const cardsCommission = computed(() => amount.value ? parseFloat((amount.value * CARDS_RATE / 100).toFixed(2)) : 0)
const cardsNet = computed(() => amount.value ? parseFloat((amount.value - cardsCommission.value).toFixed(2)) : 0)
const cryptoCommission = computed(() => amount.value ? parseFloat((amount.value * CRYPTO_RATE / 100).toFixed(2)) : 0)
const cryptoNet = computed(() => amount.value ? parseFloat((amount.value - cryptoCommission.value).toFixed(2)) : 0)

const paymentMethods = [
  {
    value: 'swift',
    label: `Swift - ${SWIFT_RATE}%`,
    iconClass: 'pi pi-globe',
    description: 'Выплата на ваш банковский счёт в долларах или евро. Переводы не осуществляются в страны, попавшие под санкции, включая Россию. Однако вы можете заказать перевод в другие страны, такие как государства ЕС, Казахстан, Грузия и т.д. Мы не взимаем комиссию за перевод, но её может удержать ваш банк или банк-корреспондент, так что уточните этот момент у своего финансового учреждения.'
  },
  {
    value: 'cards',
    label: `Visa, MasterCard, МИР - ${CARDS_RATE}%`,
    iconClass: 'pi pi-credit-card',
    description: 'Выплаты на карты Visa, Mastercard, Мир любых стран, в России в рублях на любой банк без ограничений. Для вывода в фиатной валюте может потребоваться верификация личности получателя по документам.'
  },
  {
    value: 'cryptocurrency',
    label: `Криптовалюта (USDT TRC 20) - ${CRYPTO_RATE}%`,
    iconClass: 'pi pi-wallet',
    description: 'Выплата в стейблкоине USDT TRC20. О том как зарегистрироваться на криптобирже и начать получать платежи, читайте в нашем блоге. Минимальная сумма к выводу - 20 Евро или 30 долларов США Смотрите наш гайд'
  }
]

async function handleSubmit() {
  const payload = {
    amount: amount.value,
    payment_details: paymentDetails.value,
    comments: comments.value,
    payment_type: paymentType.value
  }
  try {
    await api.post('/withdrawal', payload)
    amount.value = 0
    paymentDetails.value = ''
    comments.value = ''
    paymentType.value = 'swift'
    toastStore.showToast('Заявка на вывод средств успешно создана!', 'success')
    router.push('/account')
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
