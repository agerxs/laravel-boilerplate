<template>
  <AppLayout title="Démonstration des Statuts">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Démonstration des Statuts Harmonisés
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="mb-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              Palette de Couleurs Harmonisée
            </h3>
            <p class="text-gray-600 mb-6">
              Cette page démontre la nouvelle palette de couleurs harmonisée pour tous les statuts de l'application.
            </p>
          </div>

          <!-- Statuts de Réunions -->
          <div class="mb-8">
            <h4 class="text-md font-semibold text-gray-800 mb-4">📅 Statuts de Réunions</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div v-for="status in meetingStatuses" :key="status" class="flex items-center space-x-3">
                <StatusBadge :status="status" />
                <span class="text-sm text-gray-600">{{ status }}</span>
              </div>
            </div>
          </div>

          <!-- Statuts de Paiements -->
          <div class="mb-8">
            <h4 class="text-md font-semibold text-gray-800 mb-4">💰 Statuts de Paiements</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div v-for="status in paymentStatuses" :key="status" class="flex items-center space-x-3">
                <StatusBadge :status="status" />
                <span class="text-sm text-gray-600">{{ status }}</span>
              </div>
            </div>
          </div>

          <!-- Statuts de Présence -->
          <div class="mb-8">
            <h4 class="text-md font-semibold text-gray-800 mb-4">👥 Statuts de Présence</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div v-for="status in attendanceStatuses" :key="status" class="flex items-center space-x-3">
                <StatusBadge :status="status" />
                <span class="text-sm text-gray-600">{{ status }}</span>
              </div>
            </div>
          </div>

          <!-- Statuts Génériques -->
          <div class="mb-8">
            <h4 class="text-md font-semibold text-gray-800 mb-4">🔧 Statuts Génériques</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div v-for="status in genericStatuses" :key="status" class="flex items-center space-x-3">
                <StatusBadge :status="status" />
                <span class="text-sm text-gray-600">{{ status }}</span>
              </div>
            </div>
          </div>

          <!-- Variantes avec et sans icônes -->
          <div class="mb-8">
            <h4 class="text-md font-semibold text-gray-800 mb-4">🎨 Variantes</h4>
            <div class="space-y-4">
              <div>
                <h5 class="text-sm font-medium text-gray-700 mb-2">Avec icônes (par défaut)</h5>
                <div class="flex space-x-4">
                  <StatusBadge status="validated" />
                  <StatusBadge status="pending" />
                  <StatusBadge status="rejected" />
                </div>
              </div>
              <div>
                <h5 class="text-sm font-medium text-gray-700 mb-2">Sans icônes</h5>
                <div class="flex space-x-4">
                  <StatusBadge status="validated" :show-icon="false" />
                  <StatusBadge status="pending" :show-icon="false" />
                  <StatusBadge status="rejected" :show-icon="false" />
                </div>
              </div>
            </div>
          </div>

          <!-- Badges de réunions spécialisés -->
          <div class="mb-8">
            <h4 class="text-md font-semibold text-gray-800 mb-4">🏢 Badges de Réunions Spécialisés</h4>
            <div class="space-y-4">
              <div class="flex space-x-4">
                <MeetingStatusBadge status="scheduled" />
                <MeetingStatusBadge status="scheduled" :is-late="true" />
                <MeetingStatusBadge status="completed" />
                <MeetingStatusBadge status="cancelled" />
              </div>
            </div>
          </div>

          <!-- Légende des couleurs -->
          <div class="mt-12 p-6 bg-gray-50 rounded-lg">
            <h4 class="text-md font-semibold text-gray-800 mb-4">🎨 Légende des Couleurs</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <div class="flex items-center space-x-2">
                  <div class="w-4 h-4 bg-emerald-100 border border-emerald-200 rounded"></div>
                  <span class="text-sm text-gray-700">🟢 Vert Émeraude - Succès, validation</span>
                </div>
                <div class="flex items-center space-x-2">
                  <div class="w-4 h-4 bg-red-100 border border-red-200 rounded"></div>
                  <span class="text-sm text-gray-700">🔴 Rouge - Erreurs, rejets</span>
                </div>
                <div class="flex items-center space-x-2">
                  <div class="w-4 h-4 bg-amber-100 border border-amber-200 rounded"></div>
                  <span class="text-sm text-gray-700">🟡 Ambre - En attente, soumis</span>
                </div>
                <div class="flex items-center space-x-2">
                  <div class="w-4 h-4 bg-indigo-100 border border-indigo-200 rounded"></div>
                  <span class="text-sm text-gray-700">🔵 Indigo - Planification</span>
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex items-center space-x-2">
                  <div class="w-4 h-4 bg-purple-100 border border-purple-200 rounded"></div>
                  <span class="text-sm text-gray-700">🟣 Violet - En cours</span>
                </div>
                <div class="flex items-center space-x-2">
                  <div class="w-4 h-4 bg-blue-100 border border-blue-200 rounded"></div>
                  <span class="text-sm text-gray-700">🔷 Bleu - Terminé</span>
                </div>
                <div class="flex items-center space-x-2">
                  <div class="w-4 h-4 bg-orange-100 border border-orange-200 rounded"></div>
                  <span class="text-sm text-gray-700">🟠 Orange - Retards, alertes</span>
                </div>
                <div class="flex items-center space-x-2">
                  <div class="w-4 h-4 bg-slate-100 border border-slate-200 rounded"></div>
                  <span class="text-sm text-gray-700">⚫ Slate - Neutre, brouillon</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import MeetingStatusBadge from '@/Components/MeetingStatusBadge.vue'

const meetingStatuses = [
  'draft',
  'submitted',
  'validated',
  'rejected',
  'completed',
  'cancelled',
  'scheduled',
  'planned',
  'in_progress',
  'pending',
  'prevalidated',
  'late'
]

const paymentStatuses = [
  'pending',
  'processing',
  'paid',
  'cancelled'
]

const attendanceStatuses = [
  'present',
  'absent',
  'replaced',
  'expected'
]

const genericStatuses = [
  'active',
  'inactive',
  'published',
  'pending_validation'
]
</script> 