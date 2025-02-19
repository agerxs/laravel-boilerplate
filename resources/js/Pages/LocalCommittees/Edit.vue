<template>
  <Head :title="`Modifier ${committee.name}`" />

  <AppLayout :title="`Modifier ${committee.name}`">
    <div class="max-w-7xl mx-auto py-6">
      <div class="bg-white shadow rounded-lg">
        <form @submit.prevent="submit">
          <!-- En-tête -->
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <h2 class="text-xl font-semibold text-gray-900">
                Modifier le comité
              </h2>
            </div>
          </div>

          <!-- Contenu du formulaire -->
          <div class="px-6 py-4 space-y-6">
            <!-- Sélection de la localité -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Région -->
              <div>
                <InputLabel for="region" value="Région" />
                <select
                  id="region"
                  v-model="selectedRegion"
                  class="mt-1 block w-full rounded-md border-gray-300"
                  required
                >
                  <option value="">Sélectionner une région</option>
                  <option
                    v-for="region in localities"
                    :key="region.id"
                    :value="region"
                  >
                    {{ region.name }}
                  </option>
                </select>
              </div>

              <!-- Département -->
              <div>
                <InputLabel for="department" value="Département" />
                <select
                  id="department"
                  v-model="selectedDepartment"
                  class="mt-1 block w-full rounded-md border-gray-300"
                  required
                  :disabled="!selectedRegion"
                >
                  <option value="">Sélectionner un département</option>
                  <option
                    v-for="department in departments"
                    :key="department.id"
                    :value="department"
                  >
                    {{ department.name }}
                  </option>
                </select>
              </div>

              <!-- Sous-préfecture -->
              <div>
                <InputLabel for="locality_id" value="Sous-préfecture" />
                <select
                  id="locality_id"
                  v-model="form.locality_id"
                  class="mt-1 block w-full rounded-md border-gray-300"
                  required
                  :disabled="!selectedDepartment"
                  @change="updateCommitteeName"
                >
                  <option value="">Sélectionner une sous-préfecture</option>
                  <option
                    v-for="subPrefecture in subPrefectures"
                    :key="subPrefecture.id"
                    :value="subPrefecture.id"
                  >
                    {{ subPrefecture.name }}
                  </option>
                </select>
                <InputError :message="form.errors.locality_id" class="mt-2" />
              </div>
            </div>

            <!-- Nom du comité -->
            <div class="col-span-full">
              <InputLabel for="name" value="Nom du comité" />
              <TextInput
                id="name"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full"
                required
              />
              <InputError :message="form.errors.name" class="mt-2" />
              <p class="mt-1 text-sm text-gray-500">
                Le nom est automatiquement généré lors de la sélection d'une sous-préfecture, mais peut être modifié.
              </p>
            </div>

            <!-- Section des membres -->
            <div class="mt-8">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Membres du comité</h3>
                <button
                  type="button"
                  @click="addMember"
                  class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-md"
                >
                  <PlusIcon class="h-5 w-5 mr-2" />
                  Ajouter un membre
                </button>
              </div>

              <!-- Liste des membres -->
              <div class="space-y-4">
                <div v-for="(member, index) in form.members" :key="index" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <div class="flex justify-between items-start">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 flex-grow">
                      <div>
                        <InputLabel :for="`member-${index}-type`" value="Type de membre" />
                        <select
                          :id="`member-${index}-type`"
                          v-model="member.is_user"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                          <option :value="true">Utilisateur existant</option>
                          <option :value="false">Nouveau membre</option>
                        </select>
                      </div>

                      <template v-if="member.is_user">
                        <div>
                          <InputLabel :for="`member-${index}-user`" value="Utilisateur" />
                          <select
                            :id="`member-${index}-user`"
                            v-model="member.user_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            required
                          >
                            <option value="">Sélectionner un utilisateur</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                              {{ user.name }} ({{ user.locality_name }})
                            </option>
                          </select>
                        </div>
                      </template>

                      <template v-else>
                        <div>
                          <InputLabel :for="`member-${index}-firstname`" value="Prénom" />
                          <TextInput
                            :id="`member-${index}-firstname`"
                            v-model="member.first_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                          />
                        </div>

                        <div>
                          <InputLabel :for="`member-${index}-lastname`" value="Nom" />
                          <TextInput
                            :id="`member-${index}-lastname`"
                            v-model="member.last_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                          />
                        </div>

                        <div>
                          <InputLabel :for="`member-${index}-phone`" value="Téléphone" />
                          <TextInput
                            :id="`member-${index}-phone`"
                            v-model="member.phone"
                            type="tel"
                            class="mt-1 block w-full"
                          />
                        </div>
                      </template>

                      <div>
                        <InputLabel :for="`member-${index}-role`" value="Rôle" />
                        <select
                          :id="`member-${index}-role`"
                          v-model="member.role"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                          required
                        >
                          <option value="">Sélectionner un rôle</option>
                          <option value="secretary">Secrétaire</option>
                          <option value="member">Membre</option>
                        </select>
                      </div>
                    </div>

                    <button
                      type="button"
                      @click="removeMember(index)"
                      class="ml-4 text-red-600 hover:text-red-800"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Message si aucun membre -->
              <div v-if="form.members.length === 0" class="text-center py-6 text-gray-500">
                Aucun membre. Cliquez sur "Ajouter un membre" pour commencer.
              </div>
            </div>
          </div>

          <!-- Boutons d'action -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-end space-x-3">
              <Link
                :href="route('local-committees.show', committee.id)"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
              >
                Annuler
              </Link>
              <button
                type="submit"
                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium"
                :disabled="form.processing"
              >
                Enregistrer
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

interface User {
  id: number;
  name: string;
  locality_name?: string;
}

interface Locality {
  id: number;
  name: string;
  type: string;
  children?: Locality[];
}

interface Member {
  id?: number;
  is_user: boolean;
  user_id?: number;
  first_name?: string;
  last_name?: string;
  phone?: string;
  role: string;
  status: string;
}

interface Committee {
  id: number;
  name: string;
  locality_id: number;
  status: string;
  members: Member[];
}

interface Props {
  committee: Committee;
  users: User[];
  localities: Locality[];
}

const props = defineProps<Props>();

// Initialiser les sélections en cascade
const initializeSelections = () => {
  const currentLocality = props.localities
    .find(region => region.children?.some(dept => 
      dept.children?.some(sp => sp.id === props.committee.locality_id)
    ));

  if (currentLocality) {
    selectedRegion.value = currentLocality;
    const currentDepartment = currentLocality.children?.find(dept => 
      dept.children?.some(sp => sp.id === props.committee.locality_id)
    );
    if (currentDepartment) {
      selectedDepartment.value = currentDepartment;
    }
  }
};

const form = useForm({
  name: props.committee.name,
  locality_id: props.committee.locality_id,
  status: props.committee.status,
  members: props.committee.members.map(member => ({
    ...member,
    is_user: !!member.user_id
  }))
});

// État pour la sélection en cascade
const selectedRegion = ref<Locality | null>(null);
const selectedDepartment = ref<Locality | null>(null);

// Computed properties pour les listes filtrées
const departments = computed(() => {
  if (!selectedRegion.value) return [];
  return selectedRegion.value.children || [];
});

const subPrefectures = computed(() => {
  if (!selectedDepartment.value) return [];
  return selectedDepartment.value.children || [];
});

// Initialiser les sélections
initializeSelections();

const addMember = () => {
  form.members.push({
    is_user: false,
    role: '',
    status: 'active',
    first_name: '',
    last_name: '',
    phone: ''
  });
};

const removeMember = (index: number) => {
  form.members.splice(index, 1);
};

const submit = () => {
  form.put(route('local-committees.update', props.committee.id));
};

const updateCommitteeName = () => {
  const subPrefecture = subPrefectures.value.find(sp => sp.id.toString() === form.locality_id.toString());
  if (subPrefecture) {
    form.name = `Comité Local de ${subPrefecture.name}`;
  }
};
</script>

<style>
.bg-primary-600 {
  background-color: rgb(79, 70, 229);
}
.hover\:bg-primary-700:hover {
  background-color: rgb(67, 56, 202);
}
</style> 