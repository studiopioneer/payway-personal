<template>
  <div class="audit-full-report">
    <div class="surface-card border-round-xl shadow-1 p-4 mb-3">
      <div class="flex align-items-center gap-2 mb-4">
        <i class="pi pi-file-edit text-primary" style="font-size:1.4rem"></i>
        <span class="font-bold text-xl text-900">Полный AI-анализ</span>
        <Tag value="Разблокирован" severity="success" class="ml-auto" />
      </div>

      <!-- AI рекомендации -->
      <div v-if="report.recommendations" class="mb-4">
        <h3 class="text-900 font-semibold mt-0 mb-2" style="font-size:1rem">
          <i class="pi pi-lightbulb text-yellow-500 mr-2"></i>Рекомендации
        </h3>
        <ul class="m-0 pl-4 text-700 line-height-3">
          <li v-for="(rec, i) in report.recommendations" :key="i" class="mb-1">{{ rec }}</li>
        </ul>
      </div>

      <!-- Проблемные видео -->
      <div v-if="report.problematic_videos && report.problematic_videos.length" class="mb-4">
        <h3 class="text-900 font-semibold mt-0 mb-2" style="font-size:1rem">
          <i class="pi pi-exclamation-triangle text-orange-400 mr-2"></i>Проблемные видео
        </h3>
        <div v-for="(video, i) in report.problematic_videos" :key="i"
             class="surface-50 border-round p-3 mb-2 flex align-items-start gap-3">
          <i class="pi pi-video text-500 mt-1"></i>
          <div>
            <div class="font-medium text-900 text-sm">{{ video.title }}</div>
            <div class="text-500 text-xs mt-1">{{ video.issue }}</div>
          </div>
        </div>
      </div>

      <!-- План устранения -->
      <div v-if="report.action_plan" class="mb-3">
        <h3 class="text-900 font-semibold mt-0 mb-2" style="font-size:1rem">
          <i class="pi pi-list-check text-green-500 mr-2"></i>План устранения нарушений
        </h3>
        <ol class="m-0 pl-4 text-700 line-height-3">
          <li v-for="(step, i) in report.action_plan" :key="i" class="mb-1">{{ step }}</li>
        </ol>
      </div>
    </div>

    <Button
      label="Новый аудит"
      icon="pi pi-plus"
      class="p-button-outlined"
      @click="emit('reset')"
    />
  </div>
</template>

<script setup>
defineProps({
  report: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['reset'])
</script>
