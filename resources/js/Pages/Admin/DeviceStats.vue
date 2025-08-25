<template>
  <Head title="Statistiques des Tablettes" />
  <AppLayout title="Statistiques des Tablettes">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Statistiques générales -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Tablettes</p>
                <p class="text-2xl font-semibold text-gray-900">{{ deviceStats.total_devices || 0 }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Tablettes Actives</p>
                <p class="text-2xl font-semibold text-gray-900">{{ deviceStats.active_devices || 0 }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Activité Récente</p>
                <p class="text-2xl font-semibold text-gray-900">{{ deviceStats.recent_activity || 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Graphiques et détails -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          


          <!-- Statut des téléchargements -->
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Statut des Téléchargements</h3>
            <div class="space-y-3">
              <div v-for="(count, status) in downloadStats.by_status" :key="status" class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">{{ getStatusLabel(status) }}</span>
                <div class="flex items-center">
                  <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                    <div class="h-2 rounded-full" :class="getStatusColor(status)" :style="{ width: `${(count / downloadStats.total_downloads) * 100}%` }"></div>
                  </div>
                  <span class="text-sm font-semibold text-gray-900">{{ count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Liste des tablettes récentes -->
        <div class="mt-8 bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Tablettes Récentes</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appareil</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plateforme</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version App</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière Activité</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="device in recentDevices" :key="device.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ device.device_name || 'N/A' }}</div>
                      <div class="text-sm text-gray-500">{{ device.device_model || 'N/A' }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getPlatformBadgeClass(device.platform)">
                      {{ device.platform_label }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="device.is_tablet ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'">
                      {{ device.device_type }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ device.app_version || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(device.last_seen_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Statistiques des versions d'APK -->
        <div class="mt-8 bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Statistiques par Version d'APK</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléchargements</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Échecs</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appareils Actifs</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taille</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="version in versionStats" :key="version.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ version.version_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ version.version_code }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      {{ version.download_count }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span v-if="version.failed_count > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                      {{ version.failed_count }}
                    </span>
                    <span v-else class="text-gray-400">0</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ version.active_device_count }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ version.file_size || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(version.created_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Bouton de rafraîchissement -->
        <div class="mt-6 flex justify-center">
          <button @click="refreshStats" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium disabled:opacity-50">
            <span v-if="loading">Chargement...</span>
            <span v-else>Rafraîchir les Statistiques</span>
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  deviceStats: Object,
  downloadStats: Object,
  recentDevices: Array,
  versionStats: Array,
})

const deviceStats = ref(props.deviceStats || {})
const downloadStats = ref(props.downloadStats || {})
const recentDevices = ref(props.recentDevices || [])
const versionStats = ref(props.versionStats || [])
const loading = ref(false)

const refreshStats = async () => {
  loading.value = true
  try {
    const response = await fetch('/admin/device-stats/refresh')
    const data = await response.json()
    
    if (data.success) {
      deviceStats.value = data.deviceStats
      downloadStats.value = data.downloadStats
    }
  } catch (error) {
    console.error('Erreur lors du rafraîchissement:', error)
  } finally {
    loading.value = false
  }
}



const getStatusLabel = (status) => {
  const labels = {
    completed: 'Terminé',
    failed: 'Échoué',
    started: 'En cours',
    cancelled: 'Annulé'
  }
  return labels[status] || status
}

const getStatusColor = (status) => {
  const colors = {
    completed: 'bg-green-600',
    failed: 'bg-red-600',
    started: 'bg-blue-600',
    cancelled: 'bg-gray-600'
  }
  return colors[status] || 'bg-gray-600'
}

const getPlatformBadgeClass = (platform) => {
  const classes = {
    android: 'bg-green-100 text-green-800',
    ios: 'bg-blue-100 text-blue-800',
    web: 'bg-gray-100 text-gray-800'
  }
  return classes[platform] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}


</script>
