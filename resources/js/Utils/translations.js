export const STATUS_MEETING = {
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
  late: 'En retard'
}

export const STATUS_PAYMENT = {
  pending: 'En attente',
  validated: 'Validé',
  rejected: 'Rejeté',
  paid: 'Payé',
  cancelled: 'Annulé',
  draft: 'Brouillon',
  submitted: 'Soumis'
}

export const STATUS_MINUTES = {
  draft: 'Brouillon',
  published: 'Publié',
  pending_validation: 'En attente de validation',
  validated: 'Validé'
}

export const STATUS_ATTENDANCE = {
  present: 'Présent',
  absent: 'Absent',
  replaced: 'Remplacé',
  expected: 'Attendu'
}

export const ROLES = {
  sous_prefet: 'Sous-préfet',
  secretaire: 'Secrétaire',
  representant: 'Représentant',
  gestionnaire: 'Gestionnaire',
  member: 'Membre',
  guest: 'Invité',
  observer: 'Observateur'
}

export const STATUS_CLASSES = {
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
}

export const getStatusText = (status, type = 'meeting') => {
  switch (type) {
    case 'payment':
      return STATUS_PAYMENT[status] || status
    case 'minutes':
      return STATUS_MINUTES[status] || status
    case 'attendance':
      return STATUS_ATTENDANCE[status] || status
    case 'meeting':
    default:
      return STATUS_MEETING[status] || status
  }
}

export const getStatusClass = (status) => {
  return STATUS_CLASSES[status] || 'bg-gray-100 text-gray-800'
}

export const translateRole = (role) => {
  return ROLES[role] || role
} 