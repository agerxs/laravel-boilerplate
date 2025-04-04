export const STATUS_MEETING = {
  draft: 'Brouillon',
  submitted: 'Soumis',
  validated: 'Validé',
  rejected: 'Rejeté',
  completed: 'Terminée',
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
  prefet: 'Préfet',
  sous_prefet: 'Sous-préfet',
  secretaire: 'Secrétaire',
  representant: 'Représentant',
  gestionnaire: 'Gestionnaire',
  member: 'Membre',
  guest: 'Invité',
  observer: 'Observateur'
}

export const STATUS_CLASSES = {
  draft: 'bg-gray-100 text-gray-800',
  submitted: 'bg-yellow-100 text-yellow-800',
  validated: 'bg-green-100 text-green-800',
  rejected: 'bg-red-100 text-red-800',
  completed: 'bg-blue-100 text-blue-800',
  cancelled: 'bg-red-100 text-red-800',
  scheduled: 'bg-yellow-100 text-yellow-800',
  planned: 'bg-yellow-100 text-yellow-800',
  in_progress: 'bg-purple-100 text-purple-800',
  pending: 'bg-yellow-100 text-yellow-800',
  paid: 'bg-green-100 text-green-800',
  prevalidated: 'bg-blue-100 text-blue-800',
  late: 'bg-orange-100 text-orange-800',
  present: 'bg-green-100 text-green-800',
  absent: 'bg-red-100 text-red-800',
  replaced: 'bg-yellow-100 text-yellow-800',
  expected: 'bg-gray-100 text-gray-800'
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