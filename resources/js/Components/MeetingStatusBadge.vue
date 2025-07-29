<template>
  <span
    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full transition-all duration-200"
    :class="statusClass"
  >
    <span v-if="showIcon" class="mr-1.5">
      <component :is="statusIcon" class="w-3 h-3" />
    </span>
    {{ statusText }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { 
  CheckCircleIcon, 
  XCircleIcon, 
  ClockIcon, 
  DocumentIcon,
  ExclamationTriangleIcon,
  PlayIcon,
  CalendarIcon
} from '@heroicons/vue/16/solid'
import { getStatusText, getStatusClass } from '@/utils/translations';

interface Props {
  status: string;
  showIcon?: boolean;
  isLate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showIcon: true,
  isLate: false
});

const statusClass = computed(() => {
  if (props.isLate) {
    return 'bg-orange-100 text-orange-700 border border-orange-200';
  }
  return getStatusClass(props.status);
});

const statusIcon = computed(() => {
  if (props.isLate) {
    return ExclamationTriangleIcon;
  }
  
  const iconMap = {
    // Statuts positifs
    validated: CheckCircleIcon,
    completed: CheckCircleIcon,
    published: CheckCircleIcon,
    
    // Statuts négatifs
    rejected: XCircleIcon,
    cancelled: XCircleIcon,
    
    // Statuts en attente
    pending: ClockIcon,
    submitted: ClockIcon,
    pending_validation: ClockIcon,
    
    // Statuts de travail
    draft: DocumentIcon,
    in_progress: PlayIcon,
    
    // Statuts de planification
    scheduled: CalendarIcon,
    planned: CalendarIcon,
    prevalidated: ClockIcon
  };
  
  return iconMap[props.status] || DocumentIcon;
});

const statusText = computed(() => {
  if (props.isLate) {
    return 'En retard';
  }
  return getStatusText(props.status, 'meeting');
});
</script>
