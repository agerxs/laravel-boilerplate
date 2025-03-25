<template>
  <Head title="Planifier une réunion" />

  <AppLayout title="Planifier une réunion">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <!-- Informations de base de la réunion -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <InputLabel for="title" value="Titre de la réunion" />
                <TextInput
                  id="title"
                  v-model="form.title"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.title" class="mt-2" />
              </div>

              <div>
                <InputLabel for="local_committee_id" value="Comité local" />
                <select
                  id="local_committee_id"
                  v-model="form.local_committee_id"
                  class="mt-1 block w-full rounded-md border-gray-300"
                  required
                  @change="loadCommitteeVillages"
                >
                  <option value="">Sélectionner un comité local</option>
                  <option
                    v-for="committee in localCommittees"
                    :key="committee.id"
                    :value="committee.id"
                  >
                    {{ committee.name }}
                  </option>
                </select>
                <InputError :message="form.errors.local_committee_id" class="mt-2" />
              </div>

              <div>
                <InputLabel for="scheduled_date" value="Date de la réunion" />
                <TextInput
                  id="scheduled_date"
                  v-model="form.scheduled_date"
                  type="date"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.scheduled_date" class="mt-2" />
              </div>

              <div>
                <InputLabel for="scheduled_time" value="Heure de la réunion" />
                <TextInput
                  id="scheduled_time"
                  v-model="form.scheduled_time"
                  type="time"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.scheduled_time" class="mt-2" />
              </div>

              <div>
                <InputLabel for="location" value="Lieu de la réunion" />
                <TextInput
                  id="location"
                  v-model="form.location"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.location" class="mt-2" />
              </div>

              
            </div>

            

            <!-- Section des représentants des villages -->
            <div v-if="committeeVillages.length > 0" class="mt-8 border-t pt-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Représentants des villages</h3>
              <p class="text-sm text-gray-600 mb-6">
                Ces représentants sont issus du comité local sélectionné. Vous pouvez les modifier pour cette réunion spécifique.
              </p>

              <div class="space-y-6">
                <div v-for="village in committeeVillages" :key="village.id" class="bg-gray-50 p-4 rounded-lg border">
                  <div class="flex justify-between items-center mb-4">
                    <h4 class="font-medium text-gray-900">{{ village.name }}</h4>
                    <button 
                      type="button" 
                      @click="toggleVillageRepresentatives(village.id)"
                      class="text-sm text-blue-600 hover:text-blue-800"
                    >
                      {{ expandedVillages.includes(village.id) ? 'Masquer' : 'Modifier les représentants' }}
                    </button>
                  </div>
                  
                  <div v-if="expandedVillages.includes(village.id)" class="space-y-4">
                    <div v-for="(rep, index) in meetingRepresentatives[village.id]" :key="index" class="bg-white p-3 rounded border">
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                          <InputLabel :for="`rep-${village.id}-${index}-name`" value="Nom complet" />
                          <TextInput
                            :id="`rep-${village.id}-${index}-name`"
                            v-model="rep.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                          />
                        </div>
                        <div>
                          <InputLabel :for="`rep-${village.id}-${index}-phone`" value="Téléphone" />
                          <TextInput
                            :id="`rep-${village.id}-${index}-phone`"
                            v-model="rep.phone"
                            type="text"
                            class="mt-1 block w-full"
                          />
                        </div>
                        <div>
                          <InputLabel :for="`rep-${village.id}-${index}-role`" value="Rôle" />
                          <select
                            :id="`rep-${village.id}-${index}-role`"
                            v-model="rep.role"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            required
                          >
                            <option value="">Sélectionner un rôle</option>
                            <option value="Chef de village">Chef de village</option>
                            <option value="Représentant des femmes">Représentant des femmes</option>
                            <option value="Représentant des jeunes">Représentant des jeunes</option>
                            <option value="Autre">Autre</option>
                          </select>
                        </div>
                      </div>
                      
                      <div class="flex justify-between items-center mt-3">
                        <div class="flex items-center">
                          <input 
                            :id="`rep-${village.id}-${index}-attending`" 
                            v-model="rep.is_attending" 
                            type="checkbox" 
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                          />
                          <label :for="`rep-${village.id}-${index}-attending`" class="ml-2 text-sm text-gray-700">
                            Participera à la réunion
                          </label>
                        </div>
                        
                        <button 
                          type="button" 
                          @click="removeRepresentative(village.id, index)"
                          class="text-sm text-red-600 hover:text-red-800"
                        >
                          Supprimer
                        </button>
                      </div>
                    </div>
                    
                    <div class="flex justify-center">
                      <button
                        type="button"
                        @click="addRepresentative(village.id)"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm font-medium flex items-center"
                      >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Ajouter un représentant
                      </button>
                    </div>
                  </div>
                  
                  <div v-else class="text-sm text-gray-600">
                    {{ getAttendingCount(village.id) }} / {{ meetingRepresentatives[village.id]?.length || 0 }} représentants participeront
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-end mt-6">
              <button
                type="button"
                @click="cancel"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm font-medium mr-2"
              >
                Annuler
              </button>
              <button
                type="submit"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium"
                :disabled="form.processing"
              >
                Planifier la réunion
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, watch, computed } from 'vue'
import { useToast } from '@/Composables/useToast'
import { defineProps } from 'vue'
import axios from 'axios'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'

interface LocalCommittee {
  id: number;
  name: string;
}

interface Meeting {
  title: string;
  scheduled_date: string;
  scheduled_time: string;
  localCommittee: number | null;
}

const props = defineProps<{ localCommittees: LocalCommittee[] }>()

const form = useForm({
  title: '',
  local_committee_id: '',
  scheduled_date: '',
  scheduled_time: '',
  location: '',
  agenda: '',
  representatives: {},
  target_enrollments: '',
  actual_enrollments: '',
})

const toast = useToast()

const selectedLocalCommittee = ref(null)
const committeeVillages = ref([])
const meetingRepresentatives = ref({})
const expandedVillages = ref([])

watch(() => form.local_committee_id, async (committeeId) => {
  if (!committeeId) {
    selectedLocalCommittee.value = null
    committeeVillages.value = []
    meetingRepresentatives.value = {}
    return
  }
  
  try {
    const response = await axios.get(route('local-committees.get-villages', committeeId))
    selectedLocalCommittee.value = response.data.committee
    committeeVillages.value = response.data.villages
    
    const representatives = {}
    response.data.villages.forEach(village => {
      representatives[village.id] = village.representatives.map(rep => ({
        id: rep.id,
        name: `${rep.first_name} ${rep.last_name}`,
        phone: rep.phone,
        role: rep.role,
        is_attending: true
      }))
    })
    
    meetingRepresentatives.value = representatives
  } catch (error) {
    console.error('Erreur lors du chargement des villages:', error)
  }
})

const toggleVillageRepresentatives = (villageId) => {
  if (expandedVillages.value.includes(villageId)) {
    expandedVillages.value = expandedVillages.value.filter(id => id !== villageId)
  } else {
    expandedVillages.value.push(villageId)
  }
}

const addRepresentative = (villageId) => {
  if (!meetingRepresentatives.value[villageId]) {
    meetingRepresentatives.value[villageId] = []
  }
  
  meetingRepresentatives.value[villageId].push({
    id: null,
    name: '',
    phone: '',
    role: '',
    is_attending: true
  })
}

const removeRepresentative = (villageId, index) => {
  if (meetingRepresentatives.value[villageId]) {
    meetingRepresentatives.value[villageId].splice(index, 1)
  }
}

const getAttendingCount = (villageId) => {
  if (!meetingRepresentatives.value[villageId]) return 0
  return meetingRepresentatives.value[villageId].filter(rep => rep.is_attending).length
}

const loadCommitteeVillages = async () => {
  if (!form.local_committee_id) {
    committeeVillages.value = []
    meetingRepresentatives.value = {}
    return
  }
  
  try {
    const response = await axios.get(route('local-committees.get-villages', form.local_committee_id))
    committeeVillages.value = response.data.villages
    
    const representatives = {}
    response.data.villages.forEach(village => {
      representatives[village.id] = village.representatives.map(rep => ({
        id: rep.id,
        name: `${rep.first_name} ${rep.last_name}`,
        phone: rep.phone,
        role: rep.role,
        is_attending: true
      }))
    })
    
    meetingRepresentatives.value = representatives
    form.representatives = representatives
  } catch (error) {
    console.error('Erreur lors du chargement des villages:', error)
  }
}

const submit = () => {
  form.representatives = meetingRepresentatives.value
  form.post(route('meetings.store'))
}

const cancel = () => {
  window.history.back()
}

// Calculer le pourcentage de progression
const calculateProgress = computed(() => {
  if (!form.target_enrollments || !form.actual_enrollments) return 0;
  const progress = (form.actual_enrollments / form.target_enrollments) * 100;
  return Math.round(progress);
});

// Valider les nombres d'enrôlements
const validateEnrollments = () => {
  // Convertir en nombres
  const target = Number(form.target_enrollments);
  const actual = Number(form.actual_enrollments);

  // Vérifier que les valeurs sont positives
  if (target < 0) form.target_enrollments = 0;
  if (actual < 0) form.actual_enrollments = 0;

  // Vérifier que le nombre réel ne dépasse pas la cible
  if (actual > target) {
    form.actual_enrollments = form.target_enrollments;
  }
};
</script>

<style scoped>
.bg-primary-600 {
  background-color: rgb(79, 70, 229)
}
.hover\:bg-primary-700:hover {
  background-color: rgb(67, 56, 202)
}
.focus\:ring-primary-500:focus {
  --tw-ring-color: rgb(99, 102, 241)
}

select[multiple] {
  min-height: 200px;
}

select[multiple] option {
  padding: 0.5rem;
  margin: 0.25rem 0;
}

select[multiple] optgroup {
  font-weight: 600;
  color: #4B5563;
  padding: 0.5rem 0;
}

/* Style pour les options sélectionnées */
select[multiple] option:checked {
  background: linear-gradient(0deg, #4F46E5 0%, #4F46E5 100%);
  color: white;
}

/* Style pour le hover des options */
select[multiple] option:hover {
  background-color: #F3F4F6;
}

.required::after {
  content: " *";
  color: #dc2626;
}
</style>
