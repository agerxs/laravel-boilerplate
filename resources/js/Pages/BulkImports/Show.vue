<template>
  <Head :title="`Import - ${importItem.original_filename}`" />

  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Détails de l'import
        </h2>
                 <div class="flex space-x-2">
           <Link
             :href="route('bulk-imports.index')"
             class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium inline-flex items-center"
           >
             <ArrowLeftIcon class="h-4 w-4 mr-2" />
             Retour à la liste
           </Link>
           <Link
             v-if="importItem.file_path"
             :href="route('bulk-imports.download', importItem.id)"
             class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium inline-flex items-center"
           >
             <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
             Télécharger le fichier
           </Link>
         </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Informations générales -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informations générales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <div>
                 <h4 class="text-sm font-medium text-gray-500">Fichier importé</h4>
                 <p class="mt-1 text-sm text-gray-900">{{ importItem.original_filename }}</p>
                 <p class="mt-1 text-sm text-gray-500">{{ importItem.formatted_file_size }}</p>
               </div>
              
              <div>
                <h4 class="text-sm font-medium text-gray-500">Statut</h4>
                                 <span
                   :class="{
                     'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                     'bg-yellow-100 text-yellow-800': importItem.status === 'pending',
                     'bg-blue-100 text-blue-800': importItem.status === 'processing',
                     'bg-green-100 text-green-800': importItem.status === 'completed',
                     'bg-red-100 text-red-800': importItem.status === 'failed'
                   }"
                 >
                   {{ importItem.status_label }}
                 </span>
              </div>

                             <div>
                 <h4 class="text-sm font-medium text-gray-500">Comité local</h4>
                                  <p class="mt-1 text-sm text-gray-900">{{ importItem.local_committee && importItem.local_committee.name }}</p>
               </div>

               <div>
                 <h4 class="text-sm font-medium text-gray-500">Utilisateur</h4>
                 <p class="mt-1 text-sm text-gray-900">{{ importItem.user && importItem.user.name }}</p>
               </div>

               <div>
                 <h4 class="text-sm font-medium text-gray-500">Date d'import</h4>
                 <p class="mt-1 text-sm text-gray-900">{{ formatDate(importItem.created_at) }}</p>
               </div>

               <div>
                 <h4 class="text-sm font-medium text-gray-500">Type d'import</h4>
                 <p class="mt-1 text-sm text-gray-900">{{ importItem.import_type }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Statistiques -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Statistiques</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">Réunions créées</p>
                                         <p class="text-2xl font-bold text-blue-900">{{ importItem.meetings_created }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-green-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Pièces jointes</p>
                                         <p class="text-2xl font-bold text-green-900">{{ importItem.attachments_count }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-purple-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">Taille du fichier</p>
                                         <p class="text-2xl font-bold text-purple-900">{{ importItem.formatted_file_size }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

                 <!-- Message d'erreur si échec -->
         <div v-if="importItem.error_message" class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Erreur</h3>
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-3">
                                     <p class="text-sm text-red-800">{{ importItem.error_message }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

                 <!-- Données importées -->
         <div v-if="importItem.import_data && importItem.import_data.length > 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Données importées</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Titre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Heure
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Lieu
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                                     <tr v-for="(meeting, index) in importItem.import_data" :key="index">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ meeting.title }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ meeting.date }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ meeting.time }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ meeting.location }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

                 <!-- Pièces jointes -->
         <div v-if="importItem.attachments_info && importItem.attachments_info.length > 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Pièces jointes communes</h3>
            <div class="space-y-3">
                             <div v-for="(attachment, index) in importItem.attachments_info" :key="index"  
                   class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                      </svg>
                    </div>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ attachment.original_name }}</p>
                    <p class="text-sm text-gray-500">{{ formatFileSize(attachment.size) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

                 <!-- Réunions créées -->
         <div v-if="importItem.meetings && importItem.meetings.length > 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Réunions créées</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Titre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Date et heure
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Lieu
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Statut
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                                     <tr v-for="meeting in importItem.meetings" :key="meeting.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ meeting.title }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ formatDateTime(meeting.scheduled_date) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ meeting.location }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        :class="{
                          'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                          'bg-yellow-100 text-yellow-800': meeting.status === 'scheduled',
                          'bg-green-100 text-green-800': meeting.status === 'completed',
                          'bg-red-100 text-red-800': meeting.status === 'cancelled'
                        }"
                      >
                        {{ meeting.status }}
                      </span>
                    </td>
                                         <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                       <Link
                         :href="route('meetings.show', meeting.id)"
                         class="text-indigo-600 hover:text-indigo-900 p-1 rounded-md hover:bg-indigo-50 inline-flex items-center"
                         title="Voir la réunion"
                       >
                         <EyeIcon class="h-4 w-4" />
                       </Link>
                     </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ArrowDownTrayIcon, ArrowLeftIcon, EyeIcon } from '@heroicons/vue/24/outline'

interface BulkImport {
  id: number
  original_filename: string
  file_size: number
  formatted_file_size: string
  status: string
  status_label: string
  meetings_created: number
  attachments_count: number
  created_at: string
  error_message?: string
  import_data?: any[]
  attachments_info?: any[]
  local_committee?: {
    id: number
    name: string
  }
  user?: {
    id: number
    name: string
  }
  meetings?: any[]
}

const props = defineProps<{
  import: BulkImport
}>()

const importItem = props.import

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 B'
  
  const units = ['B', 'KB', 'MB', 'GB']
  const k = 1024
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + units[i]
}
</script> 