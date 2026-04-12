<template>
  <div class="audit-result">
    <div class="grid">
      <div class="col-12">
        <div class="surface-card border-round-xl shadow-1 p-4 mb-3">
          <div class="flex align-items-center gap-2 mb-2">
            <i class="pi pi-info-circle text-primary"></i>
            <span class="font-semibold text-900">Итог аудита</span>
          </div>
          <p class="text-700 m-0 line-height-3">{{ report.summary }}</p>
        </div>
      </div>

      <!-- Допуск к монетизации -->
      <div class="col-12 md:col-4">
        <div class="surface-card border-round-xl shadow-1 p-4 h-full" :class="cardClass(report.admission?.risk)">
          <div class="flex align-items-center gap-2 mb-3">
            <i :class="riskIcon(report.admission?.risk)" style="font-size:1.4rem"></i>
            <span class="font-bold text-900">Допуск</span>
            <Tag :value="riskLabel(report.admission?.risk)" :severity="riskSeverity(report.admission?.risk)" class="ml-auto" />
          </div>
          <p class="text-700 m-0 line-height-3 text-sm">{{ report.admission?.details || '—' }}</p>
        </div>
      </div>

      <!-- Монетизация -->
      <div class="col-12 md:col-4">
        <div class="surface-card border-round-xl shadow-1 p-4 h-full" :class="cardClass(report.demonetization?.risk)">
          <div class="flex align-items-center gap-2 mb-3">
            <i :class="riskIcon(report.demonetization?.risk)" style="font-size:1.4rem"></i>
            <span class="font-bold text-900">Монетизация</span>
            <Tag :value="riskLabel(report.demonetization?.risk)" :severity="riskSeverity(report.demonetization?.risk)" class="ml-auto" />
          </div>
          <p class="text-700 m-0 line-height-3 text-sm">{{ report.demonetization?.details || '—' }}</p>
        </div>
      </div>

      <!-- Авторские права -->
      <div class="col-12 md:col-4">
        <div class="surface-card border-round-xl shadow-1 p-4 h-full" :class="cardClass(report.copyright?.risk)">
          <div class="flex align-items-center gap-2 mb-3">
            <i :class="riskIcon(report.copyright?.risk)" style="font-size:1.4rem"></i>
            <span class="font-bold text-900">Авторские права</span>
            <Tag :value="riskLabel(report.copyright?.risk)" :severity="riskSeverity(report.copyright?.risk)" class="ml-auto" />
          </div>
          <p class="text-700 m-0 line-height-3 text-sm">{{ report.copyright?.details || '—' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  report: {
    type: Object,
    required: true
  }
})

function riskIcon(risk) {
  if (risk === 'low') return 'pi pi-check-circle text-green-500'
  if (risk === 'medium') return 'pi pi-exclamation-triangle text-yellow-500'
  if (risk === 'high') return 'pi pi-times-circle text-red-500'
  return 'pi pi-question-circle text-500'
}

function riskLabel(risk) {
  if (risk === 'low') return 'Низкий'
  if (risk === 'medium') return 'Средний'
  if (risk === 'high') return 'Высокий'
  return 'Неизвестно'
}

function riskSeverity(risk) {
  if (risk === 'low') return 'success'
  if (risk === 'medium') return 'warning'
  if (risk === 'high') return 'danger'
  return 'secondary'
}

function cardClass(risk) {
  if (risk === 'high') return 'border-left-3 border-red-400'
  if (risk === 'medium') return 'border-left-3 border-yellow-400'
  if (risk === 'low') return 'border-left-3 border-green-400'
  return ''
}
</script>
