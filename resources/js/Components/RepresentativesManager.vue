<template>
  <div>
    <!-- Liste des villages disponibles -->
    <div class="mt-6">
      <h3 class="text-lg font-medium text-gray-900">Villages disponibles ({{ unaddedVillages.length }})</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div 
          v-for="village in unaddedVillages" 
          :key="village.id"
          :class="[
            'bg-gray-50 p-4 rounded-lg shadow-md transition',
            (hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')) 
              ? 'cursor-pointer hover:bg-gray-100' 
              : 'cursor-not-allowed opacity-60'
          ]"
          @click="(hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')) ? openRepresentativeModal(village) : null"
        >
          <p class="text-sm text-gray-700">{{ village.name }}</p>
        </div>
      </div>
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
            @click="editVillageRepresentatives(village)" 
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

    <!-- Modal pour gérer les représentants -->
    <div v-if="showRepresentativeModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" @click="closeRepresentativeModal">
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
                  Représentants du village {{ selectedVillage ? selectedVillage.name : '' }}
                </h3>
                
                <div class="mt-4 space-y-4">
                  <div v-for="(rep, index) in villageRepresentatives" :key="index" class="border-b pb-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <InputLabel value="Prénom" :isRequired="true" />
                        <TextInput
                          v-model="rep.first_name"
                          type="text"
                          class="mt-1 block w-full"
                          required
                        />
                      </div>
                      <div>
                        <InputLabel value="Nom" :isRequired="true" />
                        <TextInput
                          v-model="rep.last_name"
                          type="text"
                          class="mt-1 block w-full"
                          required
                        />
                      </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mt-4">
                      <div>
                        <InputLabel value="Téléphone" />
                        <TextInput
                          v-model="rep.phone"
                          type="text"
                          class="mt-1 block w-full"
                        />
                      </div>
                      <div>
                        <InputLabel value="Sexe" />
                        <select
                          v-model="rep.gender"
                          class="mt-1 block w-full rounded-md border-gray-300"
                        >
                          <option value="">Sélectionner...</option>
                          <option value="M">Masculin</option>
                          <option value="F">Féminin</option>
                        </select>
                      </div>
                      <div>
                        <Select2Input
                          v-model="rep.role"
                          :options="roleOptions"
                          label="Rôle"
                          placeholder="Tapez pour rechercher un rôle..."
                          help-text="Sélectionnez un rôle existant ou tapez un nouveau rôle"
                          :isRequired="true"
                          @custom-value="handleCustomRole"
                        />
                      </div>
                    </div>
                    
                    <div class="mt-2 flex justify-end">
                      <button
                        type="button"
                        @click="removeRepresentative(index)"
                        class="text-red-600 hover:text-red-800"
                      >
                        Supprimer
                      </button>
                    </div>
                  </div>
                  
                  <div class="flex justify-center">
                    <button
                      type="button"
                      @click="addRepresentative"
                      class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm font-medium flex items-center"
                    >
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                      </svg>
                      Ajouter un représentant
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="button"
              @click="saveRepresentatives"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Enregistrer
            </button>
            <button
              type="button"
              @click="closeRepresentativeModal"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Annuler
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import Select2Input from '@/Components/Select2Input.vue'
import { hasRole } from '@/Utils/authUtils'
import { Role } from '@/types/Role'

interface Village {
  id: number;
  name: string;
  representatives?: Representative[];
}

interface Representative {
  first_name: string;
  last_name: string;
  phone: string;
  gender?: string;
  role: string;
}

const props = defineProps<{
  villages: Village[];
  addedVillages: Village[];
  auth: {
    user: {
      roles: Role[];
    };
  };
}>();

const emit = defineEmits<{
  (e: 'update:addedVillages', value: Village[]): void;
}>();

const showRepresentativeModal = ref(false);
const selectedVillage = ref<Village | null>(null);
const villageRepresentatives = ref<Representative[]>([]);

// Gestion des rôles
const roleOptions = ref<Array<{value: string, label: string, count?: number}>>([]);
const isLoadingRoles = ref(false);

const unaddedVillages = computed(() => {
  return props.villages.filter(village => !props.addedVillages.some(added => added.id === village.id));
});

const openRepresentativeModal = (village: Village) => {
  selectedVillage.value = village;
  
  const existingVillage = props.addedVillages.find(v => v.id === village.id);
  
  if (existingVillage?.representatives) {
    villageRepresentatives.value = [...existingVillage.representatives];
  } else {
    villageRepresentatives.value = [{
      first_name: '',
      last_name: '',
      phone: '',
      gender: '',
      role: ''
    }];
  }
  
  // Charger les rôles disponibles
  loadRoleOptions();
  
  showRepresentativeModal.value = true;
};

const closeRepresentativeModal = () => {
  showRepresentativeModal.value = false;
  selectedVillage.value = null;
  villageRepresentatives.value = [];
};

const addRepresentative = () => {
  villageRepresentatives.value.push({
    first_name: '',
    last_name: '',
    phone: '',
    gender: '',
    role: ''
  });
};

const removeRepresentative = (index: number) => {
  villageRepresentatives.value.splice(index, 1);
  
  if (villageRepresentatives.value.length === 0) {
    addRepresentative();
  }
};

const saveRepresentatives = () => {
  if (!selectedVillage.value) return;
  
  const isValid = villageRepresentatives.value.every(rep => 
    rep.first_name.trim() !== '' && 
    rep.last_name.trim() !== '' && 
    rep.role.trim() !== ''
  );
  
  if (!isValid) {
    alert('Veuillez remplir tous les champs obligatoires pour chaque représentant.');
    return;
  }
  
  const existingIndex = props.addedVillages.findIndex(v => v.id === selectedVillage.value?.id);
  const updatedVillages = [...props.addedVillages];
  
  if (existingIndex >= 0) {
    updatedVillages[existingIndex].representatives = [...villageRepresentatives.value];
  } else {
    updatedVillages.push({
      id: selectedVillage.value.id,
      name: selectedVillage.value.name,
      representatives: [...villageRepresentatives.value]
    });
  }
  
  emit('update:addedVillages', updatedVillages);
  closeRepresentativeModal();
};

const removeVillage = (index: number) => {
  const updatedVillages = [...props.addedVillages];
  updatedVillages.splice(index, 1);
  emit('update:addedVillages', updatedVillages);
};

const editVillageRepresentatives = (village: Village) => {
  selectedVillage.value = village;
  villageRepresentatives.value = [...village.representatives || []];
  
  // Charger les rôles disponibles
  loadRoleOptions();
  
  showRepresentativeModal.value = true;
};

// Méthodes pour la gestion des rôles
const loadRoleOptions = async () => {
  if (isLoadingRoles.value) return;
  
  try {
    isLoadingRoles.value = true;
    
    // Charger les rôles suggérés (les plus populaires)
    const response = await fetch('/api/roles/suggested?limit=20');
    if (response.ok) {
      const data = await response.json();
      if (data.success) {
        roleOptions.value = data.data;
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des rôles:', error);
  } finally {
    isLoadingRoles.value = false;
  }
};

const handleCustomRole = (customRole: string) => {
  // Ajouter le nouveau rôle aux options
  const newRole = {
    value: customRole,
    label: customRole,
    count: 1
  };
  
  // Vérifier si le rôle n'existe pas déjà
  const exists = roleOptions.value.some(role => 
    role.value.toLowerCase() === customRole.toLowerCase()
  );
  
  if (!exists) {
    roleOptions.value.unshift(newRole);
  }
  
  // Optionnel : Envoyer le nouveau rôle au serveur pour traçabilité
  logNewRole(customRole);
};

const logNewRole = async (role: string) => {
  try {
    await fetch('/api/roles/normalize', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ role })
    });
  } catch (error) {
    console.error('Erreur lors de la journalisation du nouveau rôle:', error);
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