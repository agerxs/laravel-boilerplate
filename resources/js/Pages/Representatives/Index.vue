<template>
  <Head title="Représentants" />

  <AppLayout title="Gestion des Représentants">
    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
          <div class="flex space-x-4 border-b mb-4">
            <button
              class="px-4 py-2 -mb-px border-b-2"
              :class="{ 'border-indigo-600 text-indigo-600 font-bold': activeTab === 'reps', 'border-transparent text-gray-500': activeTab !== 'reps' }"
              @click="activeTab = 'reps'"
            >
              Par représentant
            </button>
            <button
              class="px-4 py-2 -mb-px border-b-2"
              :class="{ 'border-indigo-600 text-indigo-600 font-bold': activeTab === 'villages', 'border-transparent text-gray-500': activeTab !== 'villages' }"
              @click="activeTab = 'villages'"
            >
              Par village
            </button>
          </div>
        </div>

        <div v-if="activeTab === 'reps'">
          <div v-if="(page.props.flash as any)?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ (page.props.flash as any).success }}
          </div>

          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-2xl font-bold">Liste des Représentants</h2>
              <button @click="openModal(representatives.data[0] || null, true)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium inline-flex items-center">
                <PlusIcon class="h-4 w-4 mr-2" />
                Nouveau Représentant
              </button>
            </div>

            <!-- Filtres -->
            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
              <h3 class="text-lg font-medium mb-4">Filtres</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                 <input type="text" v-model="filters.search" placeholder="Rechercher par nom..." class="w-full border-gray-300 rounded-md shadow-sm">
                 <select v-model="filters.local_committee_id" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Tous les comités</option>
                    <option v-for="committee in localCommittees" :key="committee.id" :value="committee.id">{{ committee.name }}</option>
                 </select>
                 <select v-model="filters.locality_id" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Tous les villages</option>
                    <option v-for="village in villages" :key="village.id" :value="village.id">{{ village.name }}</option>
                 </select>
              </div>
              <div class="mt-4 flex space-x-2 justify-end">
                 <button @click="applyFilters" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">Appliquer</button>
                 <button @click="clearFilters" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">Réinitialiser</button>
              </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                      <tr>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Village</th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comité Local</th>
                          <th scope="col" class="relative px-6 py-3">
                              <span class="sr-only">Actions</span>
                          </th>
                      </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-if="representatives.data.length === 0">
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Aucun représentant trouvé.</td>
                      </tr>
                      <tr v-for="rep in representatives.data" :key="rep.id" class="hover:bg-gray-50">
                          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ rep.name }}</td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ rep.role }}</td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ rep.phone || 'N/A' }}</td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ rep.locality?.name || 'N/A' }}</td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ rep.local_committee?.name || 'N/A' }}</td>
                          <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                              <div class="flex items-center justify-end space-x-2">
                                  <button 
                                      @click="openModal(rep)" 
                                      class="p-1 text-indigo-600 hover:text-indigo-900 transition-colors duration-200 rounded-full hover:bg-indigo-100"
                                      title="Modifier le représentant"
                                  >
                                      <PencilIcon class="h-5 w-5" />
                                  </button>
                                  <button 
                                      @click="deleteRepresentative(rep.id)" 
                                      class="p-1 text-red-600 hover:text-red-900 transition-colors duration-200 rounded-full hover:bg-red-100"
                                      title="Supprimer le représentant"
                                  >
                                      <TrashIcon class="h-5 w-5" />
                                  </button>
                              </div>
                          </td>
                      </tr>
                  </tbody>
              </table>
            </div>
            
            <div class="mt-4">
              <Pagination :links="representatives.links" />
            </div>
          </div>
        </div>

        <div v-else>
          <!-- Vue par village -->
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-4">Liste des villages</h2>
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Village</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Représentants</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="village in villages" :key="village.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ village.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <!-- Liste des représentants ou bouton modal -->
                    <button @click="showVillageReps(village)" class="text-indigo-600 hover:underline">
                      Voir les représentants
                    </button>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ (village.representatives ? village.representatives.length : 0) }}/3
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 flex space-x-2">
                    <button @click="showVillageReps(village)" class="text-blue-600 hover:text-blue-800" title="Voir">
                      <EyeIcon class="h-5 w-5 inline" />
                    </button>
                    <button @click="addVillageRep(village)" class="text-green-600 hover:text-green-800" title="Ajouter">
                      <PlusIcon class="h-5 w-5 inline" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Modal pour afficher les représentants d'un village -->
          <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Représentants du village {{ selectedVillage?.name }}</h3>
              <ul>
                <li v-for="rep in selectedVillage?.representatives || []" :key="rep.id" class="flex items-center justify-between">
                  <span>{{ rep.name }} - {{ rep.role }} - {{ rep.phone }}</span>
                  <button @click="editVillageRep(rep)" class="ml-2 text-indigo-600 hover:text-indigo-900" title="Modifier">
                    <PencilIcon class="h-4 w-4 inline" />
                  </button>
                </li>
              </ul>
            </div>
          </Modal>
        </div>
      </div>
    </div>

    <Modal :show="isModalOpen" @close="closeModal">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-medium text-gray-900">
                    {{ isEditing ? 'Modifier le représentant' : 'Nouveau représentant' }}
                </h3>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="saveRepresentative" class="space-y-4">
                <!-- Nom -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <!-- Rôle -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rôle *</label>
                    <select
                        id="role"
                        v-model="form.role"
                        required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': form.errors.role }"
                    >
                        <option value="">Sélectionner un rôle</option>
                        <option value="Chef du village">Chef du village</option>
                        <option value="Représentant des femmes">Représentant des femmes</option>
                        <option value="Représentant des jeunes">Représentant des jeunes</option>
                        <option value="Autre">Autre</option>
                    </select>
                    <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                </div>

                <!-- Téléphone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        placeholder="Ex: 0123456789"
                        maxlength="10"
                        minlength="10"
                        pattern="[0-9]{10}"
                        inputmode="tel"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': form.errors.phone }"
                    />
                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                </div>

                <!-- Village -->
                <div>
                    <label for="locality_id" class="block text-sm font-medium text-gray-700 mb-1">Village *</label>
                    <!-- :disabled="!!form.locality_id" -->
                    <select
                        id="locality_id"
                        v-model="form.locality_id"
                        required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': form.errors.locality_id }"
                    >
                        <option value="">Sélectionner un village</option>
                        <option v-for="village in villages" :key="village.id" :value="village.id">
                            {{ village.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.locality_id" class="mt-1 text-sm text-red-600">{{ form.errors.locality_id }}</p>
                </div>

                <!-- Comité Local -->
                <div>
                    <label for="local_committee_id" class="block text-sm font-medium text-gray-700 mb-1">Comité Local *</label>
                    <select
                        id="local_committee_id"
                        v-model="form.local_committee_id"
                        required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': form.errors.local_committee_id }"
                    >
                        <option value="">Sélectionner un comité</option>
                        <option v-for="committee in localCommittees" :key="committee.id" :value="committee.id">
                          {{ committee.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.local_committee_id" class="mt-1 text-sm text-red-600">{{ form.errors.local_committee_id }}</p>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end space-x-3 pt-4">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing">Enregistrement...</span>
                        <span v-else>{{ isEditing ? 'Modifier' : 'Créer' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </Modal>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline';

// Interfaces
interface Locality { id: number; name: string; }
interface LocalCommittee { id: number; name: string; }

interface Representative {
  id: number;
  name: string;
  role: string;
  phone: string;
  locality: Locality;
  local_committee: LocalCommittee;
}

interface Props {
  representatives: {
    data: Representative[];
    links: any[];
  };
  villages: Array<Locality>;
  localCommittees: Array<LocalCommittee>;
  filters: {
    search: string;
    local_committee_id: string;
    locality_id: string;
  };
}

const props = defineProps<Props>();
const page = usePage();

const isModalOpen = ref(false);
const isEditing = ref(false);
const activeTab = ref<'reps' | 'villages'>('reps');
const showModal = ref(false);
const selectedVillage = ref<any>(null);

const form = useForm({
  id: null as number | null,
  name: '',
  role: '',
  phone: '',
  locality_id: '' as number | string,
  local_committee_id: '' as number | string,
});

const filters = reactive({
  search: props.filters.search || '',
  local_committee_id: props.filters.local_committee_id || '',
  locality_id: props.filters.locality_id || '',
});

const applyFilters = () => {
  router.get(route('representatives.index'), { ...filters }, { preserveState: true, replace: true });
};

const clearFilters = () => {
  filters.search = '';
  filters.local_committee_id = '';
  filters.locality_id = '';
  applyFilters();
};

const openModal = (representative: Representative | null = null, created: boolean) => {
  isEditing.value = !!representative;

  form.reset();

  if (representative && created) {
    form.local_committee_id = representative.local_committee?.id;
  } else if (representative) {
    form.id = representative.id;
    form.name = representative.name;
    form.role = representative.role;
    form.phone = representative.phone;
    form.locality_id = representative.locality?.id;
    form.local_committee_id = representative.local_committee?.id;
  }

  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  form.reset();
};

const saveRepresentative = () => {
  const options = {
    onSuccess: () => closeModal(),
  };
  if (isEditing.value && form.id) {
    form.put(route('representatives.update', form.id), options);
  } else {
    form.post(route('representatives.store'), options);
  }
};

const deleteRepresentative = (id: number) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce représentant ?')) {
    router.delete(route('representatives.destroy', id));
  }
};

function showVillageReps(village: any) {
  selectedVillage.value = village;
  showModal.value = true;
}

function addVillageRep(village: any) {
  isEditing.value = false;
  form.reset();
  form.locality_id = village.id;
  isModalOpen.value = true;
}

function editVillageRep(rep) {
  isEditing.value = true;
  form.reset();
  form.id = rep.id;
  form.name = rep.name;
  form.role = rep.role;
  form.phone = rep.phone;
  form.locality_id = rep.locality_id || (selectedVillage.value && selectedVillage.value.id);
  form.local_committee_id = rep.local_committee_id;
  isModalOpen.value = true;
  showModal.value = false;
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