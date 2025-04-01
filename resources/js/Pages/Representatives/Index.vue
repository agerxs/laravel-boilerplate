<template>
  <Head title="Gestion des représentants" />

  <AppLayout title="Gestion des représentants">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-2xl font-semibold">Liste des représentants</h2>
              <button
                @click="openCreateModal"
                class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition-colors"
              >
                <i class="fas fa-plus mr-2"></i>Nouveau représentant
              </button>
            </div>

            <!-- Filtres -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <InputLabel for="village" value="Village" />
                <select
                  id="village"
                  v-model="filters.village"
                  class="mt-1 block w-full rounded-md border-gray-300"
                >
                  <option value="">Tous les villages</option>
                  <option
                    v-for="village in villages"
                    :key="village.id"
                    :value="village.id"
                  >
                    {{ village.name }}
                  </option>
                </select>
              </div>
              <div>
                <InputLabel for="role" value="Rôle" />
                <select
                  id="role"
                  v-model="filters.role"
                  class="mt-1 block w-full rounded-md border-gray-300"
                >
                  <option value="">Tous les rôles</option>
                  <option value="Chef du village">Chef du village</option>
                  <option value="Représentant des femmes">Représentant des femmes</option>
                  <option value="Représentant des jeunes">Représentant des jeunes</option>
                  <option value="Autre">Autre</option>
                </select>
              </div>
              <div>
                <InputLabel for="search" value="Rechercher" />
                <TextInput
                  id="search"
                  v-model="filters.search"
                  type="text"
                  class="mt-1 block w-full"
                  placeholder="Nom, prénom ou téléphone..."
                />
              </div>
            </div>

            <!-- Tableau des représentants -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Nom complet
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Village
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Localité
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Comité Local
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Rôle
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Téléphone
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="representative in filteredRepresentatives" :key="representative.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ representative.first_name }} {{ representative.last_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ representative.village?.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ representative.locality?.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ representative.local_committee?.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ representative.role }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ representative.phone }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        @click="editRepresentative(representative)"
                        class="text-primary-600 hover:text-primary-900 mr-3"
                        title="Modifier"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                      </button>
                      <button
                        @click="deleteRepresentative(representative)"
                        class="text-red-600 hover:text-red-900"
                        title="Supprimer"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal pour créer/modifier un représentant -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" @click="closeModal">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
        
        <div 
          class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
          @click.stop
        >
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                  {{ isEditing ? 'Modifier le représentant' : 'Nouveau représentant' }}
                </h3>
                
                <div class="mt-4 space-y-4">
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <InputLabel for="first_name" value="Prénom" />
                      <TextInput
                        id="first_name"
                        v-model="form.first_name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                      />
                    </div>
                    <div>
                      <InputLabel for="last_name" value="Nom" />
                      <TextInput
                        id="last_name"
                        v-model="form.last_name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                      />
                    </div>
                  </div>
                  
                  <div>
                    <InputLabel for="locality_id" value="Village" />
                    <select
                      id="locality_id"
                      v-model="form.locality_id"
                      class="mt-1 block w-full rounded-md border-gray-300"
                      required
                    >
                      <option value="">Sélectionner un village</option>
                      <option
                        v-for="village in villages"
                        :key="village.id"
                        :value="village.id"
                      >
                        {{ village.name }}
                      </option>
                    </select>
                  </div>
                  
                  <div>
                    <InputLabel for="role" value="Rôle" />
                    <select
                      id="role"
                      v-model="form.role"
                      class="mt-1 block w-full rounded-md border-gray-300"
                      required
                    >
                      <option value="">Sélectionner un rôle</option>
                      <option value="Chef du village">Chef du village</option>
                      <option value="Représentant des femmes">Représentant des femmes</option>
                      <option value="Représentant des jeunes">Représentant des jeunes</option>
                      <option value="Autre">Autre</option>
                    </select>
                  </div>
                  
                  <div>
                    <InputLabel for="phone" value="Téléphone" />
                    <TextInput
                      id="phone"
                      v-model="form.phone"
                      type="tel"
                      class="mt-1 block w-full"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="button"
              @click="saveRepresentative"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
            >
              {{ isEditing ? 'Modifier' : 'Créer' }}
            </button>
            <button
              type="button"
              @click="closeModal"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Annuler
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import { ref, computed } from 'vue'
import axios from 'axios'

interface Village {
  id: number;
  name: string;
}

interface Representative {
  id: number;
  first_name: string;
  last_name: string;
  role: string;
  phone: string;
  village?: Village;
  locality?: {
    id: number;
    name: string;
  };
  local_committee?: {
    id: number;
    name: string;
  };
}

const props = defineProps<{
  representatives: Representative[];
  villages: Village[];
}>();

const showModal = ref(false);
const isEditing = ref(false);
const selectedRepresentative = ref<Representative | null>(null);

const form = ref({
  first_name: '',
  last_name: '',
  locality_id: '',
  role: '',
  phone: ''
});

const filters = ref({
  village: '',
  role: '',
  search: ''
});

const filteredRepresentatives = computed(() => {
  return props.representatives.filter(rep => {
    const matchesVillage = !filters.value.village || rep.village?.id.toString() === filters.value.village;
    const matchesRole = !filters.value.role || rep.role === filters.value.role;
    const matchesSearch = !filters.value.search || 
      rep.first_name.toLowerCase().includes(filters.value.search.toLowerCase()) ||
      rep.last_name.toLowerCase().includes(filters.value.search.toLowerCase()) ||
      rep.phone.includes(filters.value.search);
    
    return matchesVillage && matchesRole && matchesSearch;
  });
});

const openCreateModal = () => {
  isEditing.value = false;
  selectedRepresentative.value = null;
  form.value = {
    first_name: '',
    last_name: '',
    locality_id: '',
    role: '',
    phone: ''
  };
  showModal.value = true;
};

const editRepresentative = (representative: Representative) => {
  isEditing.value = true;
  selectedRepresentative.value = representative;
  form.value = {
    first_name: representative.first_name,
    last_name: representative.last_name,
    locality_id: representative.locality?.id.toString() || '',
    role: representative.role,
    phone: representative.phone
  };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedRepresentative.value = null;
};

const saveRepresentative = async () => {
  try {
    if (isEditing.value && selectedRepresentative.value) {
      const formData = {
        ...form.value,
        _method: 'PATCH'
      };
      await axios.post(route('representatives.update', selectedRepresentative.value.id), formData);
    } else {
      await axios.post(route('representatives.store'), form.value);
    }
    closeModal();
    // Recharger la page pour mettre à jour la liste
    window.location.reload();
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
  }
};

const deleteRepresentative = async (representative: Representative) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce représentant ?')) {
    try {
      await axios.delete(route('representatives.destroy', representative.id));
      // Recharger la page pour mettre à jour la liste
      window.location.reload();
    } catch (error) {
      console.error('Erreur lors de la suppression:', error);
    }
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