<template>
  <span
    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full transition-all duration-200"
    :class="colorClass"
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
  StopIcon,
  UserIcon
} from '@heroicons/vue/16/solid'

interface Props {
  status: string;
  showIcon?: boolean;
  size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
  showIcon: true,
  size: 'md'
});

const colorClass = computed(() => {
  const baseClasses = {
    // Statuts de réunions
    draft: 'bg-slate-100 text-slate-700 border border-slate-200',
    submitted: 'bg-amber-100 text-amber-700 border border-amber-200',
    validated: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    rejected: 'bg-red-100 text-red-700 border border-red-200',
    completed: 'bg-blue-100 text-blue-700 border border-blue-200',
    cancelled: 'bg-red-100 text-red-700 border border-red-200',
    scheduled: 'bg-indigo-100 text-indigo-700 border border-indigo-200',
    planned: 'bg-indigo-100 text-indigo-700 border border-indigo-200',
    in_progress: 'bg-purple-100 text-purple-700 border border-purple-200',
    pending: 'bg-amber-100 text-amber-700 border border-amber-200',
    prevalidated: 'bg-cyan-100 text-cyan-700 border border-cyan-200',
    late: 'bg-orange-100 text-orange-700 border border-orange-200',
    
    // Statuts de paiements
    paid: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    processing: 'bg-blue-100 text-blue-700 border border-blue-200',
    
    // Statuts de présence
    present: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    absent: 'bg-red-100 text-red-700 border border-red-200',
    replaced: 'bg-amber-100 text-amber-700 border border-amber-200',
    expected: 'bg-slate-100 text-slate-700 border border-slate-200',
    
    // Statuts génériques
    active: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    inactive: 'bg-slate-100 text-slate-700 border border-slate-200',
    published: 'bg-purple-100 text-purple-700 border border-purple-200',
    pending_validation: 'bg-amber-100 text-amber-700 border border-amber-200'
  };
  
  return baseClasses[props.status] || 'bg-slate-100 text-slate-700 border border-slate-200';
});

const statusIcon = computed(() => {
  const iconMap = {
    // Statuts positifs
    validated: CheckCircleIcon,
    completed: CheckCircleIcon,
    paid: CheckCircleIcon,
    present: CheckCircleIcon,
    active: CheckCircleIcon,
    published: CheckCircleIcon,
    
    // Statuts négatifs
    rejected: XCircleIcon,
    cancelled: XCircleIcon,
    absent: XCircleIcon,
    inactive: XCircleIcon,
    
    // Statuts en attente
    pending: ClockIcon,
    submitted: ClockIcon,
    pending_validation: ClockIcon,
    expected: ClockIcon,
    
    // Statuts de travail
    draft: DocumentIcon,
    in_progress: PlayIcon,
    processing: PlayIcon,
    
    // Statuts spéciaux
    late: ExclamationTriangleIcon,
    replaced: UserIcon,
    scheduled: ClockIcon,
    planned: ClockIcon,
    prevalidated: ClockIcon
  };
  
  return iconMap[props.status] || DocumentIcon;
});

const statusText = computed(() => {
  const statusMap: { [key: string]: string } = {
    // Réunions
    draft: 'Brouillon',
    submitted: 'Soumis',
    validated: 'Validé',
    rejected: 'Rejeté',
    completed: 'Publiée',
    cancelled: 'Annulée',
    scheduled: 'Planifiée',
    planned: 'Planifiée',
    in_progress: 'En cours',
    pending: 'En attente',
    prevalidated: 'Prévalidée',
    late: 'En retard',
    
    // Paiements
    paid: 'Payé',
    processing: 'En traitement',
    
    // Présence
    present: 'Présent',
    absent: 'Absent',
    replaced: 'Remplacé',
    expected: 'Attendu',
    
    // Génériques
    active: 'Actif',
    inactive: 'Inactif',
    published: 'Publié',
    pending_validation: 'En attente de validation'
  };
  
  return statusMap[props.status] || props.status;
});
</script> 