<template>
  <Head title="Taux de paiement" />

  <AppLayout title="Taux de paiement">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Taux de paiement
        </h2>
        <div class="flex space-x-4">
          <Link
            :href="route('payment-rates.create')"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700"
          >
            Ajouter un taux
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <!-- Filtres -->
          <div class="mb-6 flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
            <div class="flex-1">
              <InputLabel for="search" value="Rechercher" />
              <TextInput
                id="search"
                v-model="search"
                type="text"
                class="mt-1 block w-full"
                placeholder="Nom ou email"
                @keyup.enter="applyFilters"
              />
            </div>
            <div class="flex-1">
              <InputLabel for="role" value="Rôle" />
              <select
                id="role"
                v-model="selectedRole"
                class="mt-1 block w-full rounded-md border-gray-300"
                @change="applyFilters"
              >
                <option value="">Tous les rôles</option>
                <option v-for="role in roles" :key="role.id" :value="role.name">
                  {{ role.name }}
                </option>
              </select>
            </div>
            <div class="flex-none">
              <button
                @click="applyFilters"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm font-medium"
              >
                Filtrer
              </button>
            </div>
          </div>

          <!-- Tableau des taux de paiement -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Utilisateur
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Rôles
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Taux par réunion
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Statut
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="user in users.data" :key="user.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div>
                        <div class="text-sm font-medium text-gray-900">
                          {{ user.name }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ user.email }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ user.roles.join(', ') }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div v-for="role in user.roles" :key="role" class="mb-2">
                      <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">{{ role }}:</span>
                        <input
                          v-model="userRates[user.id + '-' + role]"
                          type="number"
                          min="0"
                          step="0.01"
                          class="w-24 rounded-md border-gray-300 text-sm"
                          :placeholder="user.payment_rates[role]?.meeting_rate || '0.00'"
                        />
                        <span class="text-sm text-gray-500">FCFA</span>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div v-for="role in user.roles" :key="role" class="mb-2">
                      <StatusBadge 
                        :status="user.payment_rates[role]?.is_active ? 'active' : (!user.payment_rates[role] ? 'inactive' : 'inactive')"
                        :show-icon="false"
                      />
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button
                      @click="saveUserRates(user)"
                      class="text-indigo-600 hover:text-indigo-900 mr-3"
                      :disabled="isSaving"
                    >
                      Enregistrer
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="mt-4">
            <Pagination :links="users.links" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import Pagination from '@/Components/Pagination.vue'
import { useToast } from '@/Composables/useToast'
import axios from 'axios'
import StatusBadge from '@/Components/StatusBadge.vue'

const props = defineProps({
  users: Object,
  roles: Array,
  filters: Object
})

const toast = useToast()
const search = ref(props.filters.search || '')
const selectedRole = ref(props.filters.role || '')
const userRates = ref({})
const isSaving = ref(false)

// Initialiser les taux pour chaque utilisateur
onMounted(() => {
  props.users.data.forEach(user => {
    user.roles.forEach(role => {
      const rate = user.payment_rates[role]
      if (rate) {
        userRates.value[user.id + '-' + role] = rate.meeting_rate
      }
    })
  })
})

const applyFilters = () => {
  router.get(route('payment-rates.index'), {
    search: search.value,
    role: selectedRole.value
  }, {
    preserveState: true,
    replace: true
  })
}

const saveUserRates = async (user) => {
  isSaving.value = true
  
  const rates = []
  
  user.roles.forEach(role => {
    const rateKey = user.id + '-' + role
    const rateValue = userRates.value[rateKey]
    
    if (rateValue) {
      rates.push({
        user_id: user.id,
        role: role,
        meeting_rate: rateValue,
        is_active: true
      })
    }
  })
  
  try {
    await axios.post(route('payment-rates.bulk-update'), {
      rates: rates
    })
    
    toast.success('Taux de paiement mis à jour avec succès')
    
    // Recharger la page pour afficher les modifications
    router.reload()
  } catch (error) {
    console.error('Erreur lors de la mise à jour des taux:', error)
    toast.error('Erreur lors de la mise à jour des taux de paiement')
  } finally {
    isSaving.value = false
  }
}
</script> 