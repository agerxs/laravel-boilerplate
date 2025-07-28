<template>
  <Head title="Ajouter des représentants pour les villages" />

  <AppLayout title="Ajouter des représentants pour les villages">
    <div class="max-w-7xl mx-auto py-6">
      <h1 class="text-3xl font-extrabold mb-6 text-gray-900">Ajouter des représentants pour les villages</h1>
      
      <!-- Afficher le nom du comité -->
      <h2 class="text-2xl font-semibold mb-4 text-indigo-600">Comité: {{ committee.name }}</h2>

      <!-- Afficher les membres permanents -->
      <div class="mb-8">
        <h3 class="text-xl font-medium text-gray-800">Membres permanents</h3>
        <ul class="list-disc list-inside mt-2">
          <li v-for="member in permanentMembers" :key="member.id" class="text-sm text-gray-700">
            {{ member.user.name }} - <span class="font-semibold">{{ member.role }}</span>
          </li>
        </ul>
      </div>

      <!-- Liste des villages non ajoutés -->
      <div class="mb-8">
        <h3 class="text-xl font-medium text-gray-800">Villages non ajoutés</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
          <div
            v-for="village in unaddedVillages"
            :key="village.id"
            :class="[
              'bg-gray-50 p-4 rounded-lg shadow-md transition',
              (hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')) 
                ? 'cursor-pointer hover:bg-gray-100' 
                : 'cursor-not-allowed opacity-60'
            ]"
            @click="(hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')) ? selectVillage(village.id) : null"
          >
            <p class="text-sm text-gray-700">{{ village.name }}</p>
          </div>
        </div>
      </div>

      <!-- Formulaire pour ajouter un village et ses représentants -->
      <div v-if="selectedVillage" class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h3 class="text-lg font-medium text-gray-900 mt-4">Représentants du village</h3>
        <div class="space-y-4">
          <div v-for="(rep, index) in villageRepresentatives" :key="index" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h4 class="text-md font-semibold text-gray-800">{{ rep.role }}</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
              <div>
                <InputLabel :for="`representative-${index}-firstname`" value="Prénom" />
                <TextInput
                  :id="`representative-${index}-firstname`"
                  v-model="rep.first_name"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
              </div>

              <div>
                <InputLabel :for="`representative-${index}-lastname`" value="Nom" />
                <TextInput
                  :id="`representative-${index}-lastname`"
                  v-model="rep.last_name"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
              </div>

              <div>
                <InputLabel :for="`representative-${index}-phone`" value="Téléphone" />
                <TextInput
                  :id="`representative-${index}-phone`"
                  v-model="rep.phone"
                  type="tel"
                  class="mt-1 block w-full"
                  maxlength="10"
                  pattern="\\d*"
                  inputmode="numeric"
                />
              </div>
            </div>
          </div>
        </div>
        <button 
          v-if="hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')"
          @click="addVillage" 
          class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium transition duration-150 ease-in-out"
        >
          <i class="fas fa-plus-circle mr-2"></i>Ajouter le village
        </button>
      </div>

      <!-- Liste des villages ajoutés -->
      <div class="mt-8">
        <h3 class="text-lg font-medium text-gray-900">Villages ajoutés</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div v-for="(village, index) in addedVillages" :key="village.id" class="bg-white p-4 rounded-lg shadow-md">
            <strong class="text-indigo-600">{{ village.name }}</strong>
            <ul class="list-inside mt-2">
              <li v-for="rep in village.representatives" :key="rep.first_name + rep.last_name" class="text-sm text-gray-700">
                {{ rep.first_name }} {{ rep.last_name }} - {{ rep.phone }} ({{ rep.role }})
              </li>
            </ul>
            <button 
              v-if="hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')"
              @click="editVillage(index)" 
              class="mt-2 text-blue-500 hover:text-blue-700 transition duration-150 ease-in-out"
            >
              <i class="fas fa-edit mr-1"></i>Modifier
            </button>
            <button 
              v-if="hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')"
              @click="removeVillage(index)" 
              class="mt-2 text-red-500 hover:text-red-700 transition duration-150 ease-in-out"
            >
              <i class="fas fa-trash-alt mr-1"></i>Supprimer
            </button>
          </div>
        </div>
      </div>

      <!-- Bouton pour sauvegarder tous les villages ajoutés -->
      <div class="mt-8">
        <button 
          v-if="hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')"
          @click="saveAll" 
          class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition duration-150 ease-in-out"
        >
          <i class="fas fa-save mr-1"></i>Sauvegarder tous les villages
        </button>
      </div>

      <!-- Formulaire de modification -->
      <div v-if="editIndex !== null" class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h3 class="text-lg font-medium text-gray-900 mt-4">Modifier les représentants du village</h3>
        <div class="space-y-4">
          <div v-for="(rep, index) in editRepresentatives" :key="index" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h4 class="text-md font-semibold text-gray-800">{{ rep.role }}</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
              <div>
                <InputLabel :for="`edit-representative-${index}-firstname`" value="Prénom" />
                <TextInput
                  :id="`edit-representative-${index}-firstname`"
                  v-model="rep.first_name"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
              </div>

              <div>
                <InputLabel :for="`edit-representative-${index}-lastname`" value="Nom" />
                <TextInput
                  :id="`edit-representative-${index}-lastname`"
                  v-model="rep.last_name"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
              </div>

              <div>
                <InputLabel :for="`edit-representative-${index}-phone`" value="Téléphone" />
                <TextInput
                  :id="`edit-representative-${index}-phone`"
                  v-model="rep.phone"
                  type="tel"
                  class="mt-1 block w-full"
                  maxlength="10"
                  pattern="\\d*"
                  inputmode="numeric"
                />
              </div>
            </div>
          </div>
        </div>
        <button 
          v-if="hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')"
          @click="saveEdit" 
          class="mt-4 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition duration-150 ease-in-out"
        >
          <i class="fas fa-save mr-1"></i>Enregistrer les modifications
        </button>
        <button 
          v-if="hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')"
          @click="cancelEdit" 
          class="mt-4 ml-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium transition duration-150 ease-in-out"
        >
          <i class="fas fa-times mr-1"></i>Annuler
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import axios from 'axios'
import { hasRole } from '@/utils/authUtils'
import { Role } from '@/types/Role'

const { props } = usePage()
const committee = props.committee
const permanentMembers = props.permanentMembers

const villages = ref([])
const addedVillages = ref([])
const editIndex = ref(null)
const editRepresentatives = ref([])

onMounted(() => {
  const storedVillages = localStorage.getItem('villages')
  if (storedVillages) {
    villages.value = JSON.parse(storedVillages)
  }
})

const selectedVillage = ref(null)
const representativeRoles = ['Chef du village', 'Représentant des femmes', 'Représentant des jeunes']
const villageRepresentatives = ref(representativeRoles.map(role => ({
  first_name: '',
  last_name: '',
  phone: '',
  role: role
})))

const unaddedVillages = computed(() => {
  return villages.value.filter(village => !addedVillages.value.some(added => added.id === village.id))
})

const selectVillage = (villageId) => {
  selectedVillage.value = villageId
}

const addVillage = () => {
  if (selectedVillage.value) {
    const village = villages.value.find(v => v.id === selectedVillage.value)
    if (village) {
      addedVillages.value.push({
        ...village,
        representatives: villageRepresentatives.value.map(rep => ({
          ...rep
        }))
      })
      // Réinitialiser les champs
      selectedVillage.value = null
      villageRepresentatives.value = representativeRoles.map(role => ({
        first_name: '',
        last_name: '',
        phone: '',
        role: role
      }))
    }
  }
}

const saveAll = () => {
  axios.post(route('local-committees.save-villages', { committeeId: committee.id }), {
    villages: addedVillages.value
  })
}

const editVillage = (index: number) => {
  editIndex.value = index
  editRepresentatives.value = JSON.parse(JSON.stringify(addedVillages.value[index].representatives))
}

const saveEdit = () => {
  if (editIndex.value !== null) {
    addedVillages.value[editIndex.value].representatives = JSON.parse(JSON.stringify(editRepresentatives.value))
    editIndex.value = null
  }
}

const cancelEdit = () => {
  editIndex.value = null
}

const removeVillage = (index: number) => {
  addedVillages.value.splice(index, 1)
}
</script>

<style>
.bg-primary-600 {
  background-color: rgb(79, 70, 229);
}
.hover\:bg-primary-700:hover {
  background-color: rgb(67, 56, 202);
}
</style> 