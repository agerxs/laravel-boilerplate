<template>
  <AppLayout :title="`Modifier ${committee.name}`">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Modifier {{ committee.name }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <!-- Informations du comité -->
            <div class="grid grid-cols-2 gap-6">
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

              <div>
                <InputLabel for="city" value="Localité" />
                <TextInput
                  id="city"
                  v-model="form.city"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.city" class="mt-2" />
              </div>
            </div>

            <div>
              <InputLabel for="address" value="Adresse" />
              <TextInput
                id="address"
                v-model="form.address"
                type="text"
                class="mt-1 block w-full"
                required
              />
              <InputError :message="form.errors.address" class="mt-2" />
            </div>

            <div>
              <InputLabel for="description" value="Description" />
              <TextArea
                id="description"
                v-model="form.description"
                class="mt-1 block w-full"
                rows="3"
              />
              <InputError :message="form.errors.description" class="mt-2" />
            </div>

            <!-- Section des membres -->
            <div class="mt-6">
              <h3 class="text-lg font-medium text-gray-900">Membres du comité</h3>
              <p class="mt-1 text-sm text-gray-600">
                Gérez les membres de ce comité
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

              <!-- Bouton pour ajouter un membre -->
              <button
                type="button"
                @click="addMember"
                class="mt-4 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              >
                <PlusIcon class="h-5 w-5 mr-2" />
                Ajouter un membre
              </button>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
              <Link
                :href="route('local-committees.index')"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
              >
                Annuler
              </Link>
              <PrimaryButton :disabled="form.processing">
                Enregistrer les modifications
              </PrimaryButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'

const toast = useToast()

interface User {
  id: number
  name: string
  email: string
}

interface Member {
  id?: number
  user_id?: number | ''
  role: string
  first_name?: string
  last_name?: string
  phone?: string
  is_user: boolean
}

interface Committee {
  id: number
  name: string
  description: string
  city: string
  address: string
  members: Member[]
}

interface Props {
  committee: Committee
  users: User[]
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

const form = useForm({
  name: props.committee.name,
  description: props.committee.description,
  city: props.committee.city,
  address: props.committee.address,
  members: props.committee.members.map(member => ({
    ...member,
    is_user: !!member.user_id
  }))
})

// Filtrer les utilisateurs déjà sélectionnés
const availableUsers = computed(() => {
  if (!props.users) return []
  const selectedUserIds = form.members
    .map(m => m.user_id)
    .filter((id): id is number => id !== '')
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

  form.put(route('local-committees.update', props.committee.id), {
    onSuccess: () => {
      toast.success('Comité mis à jour avec succès')
    },
    onError: () => {
      toast.error('Une erreur est survenue')
    }
  })
}
</script> 