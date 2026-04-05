<template>
  <div>
    <Tag :value="statusLabel" :severity="statusSeverity" />
    <div
      v-if="rowData.rejected_comment && rowData.status === 'rejected'"
      style="margin-top: 4px; font-size: 11px; color: #ef4444"
    >
      {{ rowData.rejected_comment }}
    </div>
  </div>
</template>

<script setup>
import Tag from 'primevue/tag'
import { computed } from 'vue'

const props = defineProps({
  rowData: { type: Object, required: true },
  statuses: { type: Array, required: true }
})

const matched = computed(() => props.statuses.find(s => s.value === props.rowData.status))
const statusLabel = computed(() => matched.value ? matched.value.label : props.rowData.status)
const statusSeverity = computed(() => matched.value ? matched.value.severity : null)
</script>
