<template>
  <AppLayout title="Nouveau Comité Local">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Nouveau Comité Local
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <!-- Informations de base -->
            <div class="mb-6">
              <div>
                <InputLabel for="name" value="Nom du comité" />
                <TextInput
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.name" class="mt-2" />
              </div>
            </div>

            <!-- Bloc localité -->
            <div class="mb-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Localisation</h3>
              <LocalitySelector
                v-model="selectedLocation"
                :localities="localities"
              />
            </div>

            <!-- Adresse -->
            <div class="mb-6">
              <InputLabel for="address" value="Adresse complète" />
              <TextInput
                id="address"
                v-model="form.address"
                type="text"
                class="mt-1 block w-full"
                required
              />
              <InputError :message="form.errors.address" class="mt-2" />
            </div>

            <!-- Description -->
            <div class="mb-6">
              <InputLabel for="description" value="Description" />
              <TextArea
                id="description"
                v-model="form.description"
                class="mt-1 block w-full"
                :rows="3"
              />
              <InputError :message="form.errors.description" class="mt-2" />
            </div>

            <!-- Section des membres -->
            <div class="mt-6">
              <h3 class="text-lg font-medium text-gray-900">Membres du comité</h3>
              <p class="mt-1 text-sm text-gray-600">
                Ajoutez les membres du comité local
              </p>

              <div class="mt-4 space-y-4">
                <div v-for="(member, index) in form.members" :key="index" class="flex gap-4 items-start p-4 bg-gray-50 rounded-lg">
                  <!-- Sélection du rôle -->
                  <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700">Rôle</label>
                    <select
                      v-model="member.role"
                      class="mt-1 block w-full rounded-md border-gray-300"
                      required
                    >
                      <option value="">Sélectionner un rôle</option>
                      <option 
                        v-for="(label, role) in ROLE_LABELS" 
                        :key="role"
                        :value="role"
                        :disabled="role === ROLES.SECRETARY && index !== 0"
                      >
                        {{ label }}
                      </option>
                    </select>
                  </div>

                  <!-- Champs pour utilisateur (secrétaire) -->
                  <template v-if="member.role === ROLES.SECRETARY">
                    <div class="flex-1">
                      <label class="block text-sm font-medium text-gray-700">Utilisateur</label>
                      <select
                        v-model="member.user_id"
                        class="mt-1 block w-full rounded-md border-gray-300"
                        required
                      >
                        <option value="">Sélectionner un utilisateur</option>
                        <option
                          v-for="user in availableUsers"
                          :key="user.id"
                          :value="user.id"
                          :disabled="isUserSelected(user.id, index)"
                        >
                          {{ user.name }}
                        </option>
                      </select>
                    </div>
                  </template>

                  <!-- Champs pour non-utilisateur -->
                  <template v-else>
                    <div class="flex-1">
                      <label class="block text-sm font-medium text-gray-700">Nom</label>
                      <TextInput
                        v-model="member.last_name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                      />
                    </div>
                    <div class="flex-1">
                      <label class="block text-sm font-medium text-gray-700">Prénom</label>
                      <TextInput
                        v-model="member.first_name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                      />
                    </div>
                    <div class="flex-1">
                      <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                      <TextInput
                        v-model="member.phone"
                        type="tel"
                        class="mt-1 block w-full"
                        required
                      />
                    </div>
                  </template>

                  <!-- Bouton supprimer -->
                  <button
                    type="button"
                    class="mt-6"
                    @click="removeMember(index)"
                    :disabled="index === 0"
                    :class="{ 'opacity-50 cursor-not-allowed': index === 0 }"
                  >
                    <TrashIcon class="w-5 h-5 text-red-500" />
                  </button>
                </div>
              </div>

              <!-- Bouton ajouter membre -->
              <button
                type="button"
                class="mt-4 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                @click="addMember"
              >
                <PlusIcon class="w-4 h-4 mr-2" />
                Ajouter un membre
              </button>
            </div>

            <div class="flex justify-end space-x-3">
              <Link
                :href="route('local-committees.index')"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
              >
                Annuler
              </Link>
              <PrimaryButton :disabled="form.processing">
                Créer le comité
              </PrimaryButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import LocalitySelector from '@/Components/LocalitySelector.vue'

const toast = useToast()

interface User {
  id: number
  name: string
  email: string
}

interface Member {
  user_id?: number | ''  // Optionnel car seul le secrétaire est un utilisateur
  role: string
  first_name?: string    // Pour les membres non-utilisateurs
  last_name?: string     // Pour les membres non-utilisateurs
  phone?: string         // Pour les membres non-utilisateurs
  is_user: boolean       // Pour distinguer les types de membres
}

interface Props {
  users: User[]
  localities: any[]  // On peut typer plus précisément si nécessaire
}

const props = defineProps<Props>()

const ROLES = {
  SECRETARY: 'secretaire',
  VILLAGE_CHIEF: 'chef_village',
  YOUTH_PRESIDENT: 'president_jeunes',
  WOMEN_PRESIDENT: 'presidente_femmes'
} as const

const ROLE_LABELS = {
  [ROLES.SECRETARY]: 'Secrétaire',
  [ROLES.VILLAGE_CHIEF]: 'Chef du village',
  [ROLES.YOUTH_PRESIDENT]: 'Président des jeunes',
  [ROLES.WOMEN_PRESIDENT]: 'Présidente des femmes'
} as const

// État initial du formulaire
const initialFormState = {
  name: '',
  description: '',
  address: '',
  city: '',  // Ajout du champ city pour la validation
  members: [{
    user_id: '' as number | '',
    role: ROLES.SECRETARY,
    is_user: true,
    first_name: '',
    last_name: '',
    phone: ''
  }] as Member[]
}

const form = useForm(initialFormState)

const selectedLocation = ref('')

// Surveiller les changements de localité pour mettre à jour l'adresse et la ville
watch(selectedLocation, (newLocation) => {
  if (newLocation) {
    // Si une adresse existe déjà, on la préserve en l'ajoutant à la fin
    const existingAddress = form.address.split(' - ')[1] || ''
    form.address = newLocation + (existingAddress ? ' - ' + existingAddress : '')
    form.city = newLocation  // Mettre à jour le champ city
  }
})

// Filtrer les utilisateurs déjà sélectionnés
const availableUsers = computed(() => {
  if (!props.users) return []
  const selectedUserIds = form.members
    .map(m => m.user_id)
    .filter((id): id is number => id !== '') // Type guard pour TypeScript
  return props.users.filter(user => {
    if (!selectedUserIds.includes(user.id)) return true
    return selectedUserIds.indexOf(user.id) === selectedUserIds.lastIndexOf(user.id)
  })
})

function isUserSelected(userId: number, currentIndex: number) {
  return form.members.some((member, index) => 
    Number(member.user_id) === userId && index !== currentIndex
  )
}

function addMember() {
  form.members.push({
    user_id: '' as number | '',
    role: '',
    first_name: '',
    last_name: '',
    phone: '',
    is_user: false
  })
}

function removeMember(index: number) {
  if (form.members.length > 1) {
    form.members.splice(index, 1)
  } else {
    toast.error('Le comité doit avoir au moins un membre')
  }
}

function submit() {
  if (!form.members.length) {
    toast.error('Ajoutez au moins un membre')
    return
  }

  // Préparer les données avant l'envoi
  form.members = form.members.map(member => {
    if (member.role === ROLES.SECRETARY) {
      // Pour le secrétaire, on garde user_id et on supprime les autres champs
      return {
        user_id: member.user_id,
        role: member.role,
        is_user: true
      }
    } else {
      // Pour les autres membres, on supprime user_id
      const { user_id, ...memberData } = member
      return memberData
    }
  })

  // S'assurer que l'adresse contient la localité
  if (selectedLocation.value && !form.address.startsWith(selectedLocation.value)) {
    form.address = selectedLocation.value + ' - ' + form.address
    form.city = selectedLocation.value
  }

  form.post(route('local-committees.store'), {
    onSuccess: () => {
      toast.success('Comité créé avec succès')
    },
    onError: () => {
      toast.error('Une erreur est survenue')
    }
  })
}
</script> 