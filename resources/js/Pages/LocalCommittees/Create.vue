<template>
  <Head title="Nouveau comité local" />

  <AppLayout title="Nouveau comité local">
    <div class="flex flex-col">
      <!-- Navigation par onglets -->
      <div class="flex bg-gray-100 p-4 rounded-t-lg">
        <button
          v-for="(step, index) in steps"
          :key="index"
          @click="activeStep = index"
          :class="{
            'bg-white text-blue-600': activeStep === index,
            'bg-gray-200 text-gray-500': activeStep !== index
          }"
          class="px-4 py-2 rounded-md mx-1 transition duration-300"
        >
          {{ step.label }}
        </button>
      </div>

      <!-- Contenu des étapes -->
      <div class="bg-white shadow rounded-b-lg p-6">
        <div v-if="activeStep === 0" class="px-6 py-4">
          <!-- Étape 1: Sélection de la région, département, sous-préfecture -->
          <form @submit.prevent="nextStep">
            <div class="px-6 py-4 space-y-6">
              <!-- Informations de base -->
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
                    @change="() => { updateCommitteeName(); fetchVillages(); }"
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
            </div>

            <!-- Boutons d'action -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="previousStep"
                  class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium"
                >
                  Précédent
                </button>
                <button
                  type="button"
                  @click="saveProgress"
                  class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md text-sm font-medium"
                >
                  Sauvegarder
                </button>
                <button
                  type="button"
                  @click="nextStep"
                  class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium"
                >
                  Suivant
                </button>
              </div>
            </div>
          </form>
        </div>

        <div v-if="activeStep === 1" class="px-6 py-4">
          <!-- Étape 2: Ajouter un fichier joint -->
          <form @submit.prevent="nextStep">
            <div class="px-6 py-4 space-y-6">
              <div>
                <InputLabel for="decree" value="Arrêté ou Décret de création" />
                <input
                  type="file"
                  id="decree"
                  @change="handleFileChange('decree')"
                  class="mt-1 block w-full"
                  required
                />
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="previousStep"
                  class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium"
                >
                  Précédent
                </button>
                <button
                  type="button"
                  @click="saveProgress"
                  class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md text-sm font-medium"
                >
                  Sauvegarder
                </button>
                <button
                  type="button"
                  @click="nextStep"
                  class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium"
                >
                  Suivant
                </button>
              </div>
            </div>
          </form>
        </div>

        <div v-if="activeStep === 2" class="px-6 py-4">
          <!-- Étape 3: Renseigner les membres permanents -->
          <form @submit.prevent="nextStep">
            <div class="px-6 py-4 space-y-6">
              <!-- Ajout des membres permanents -->
              <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900">Membres permanents</h3>
                <div class="space-y-4">
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                      <div>
                        <InputLabel value="Rôle" />
                        <TextInput
                          value="Président"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          disabled
                        />
                      </div>
                      <div>
                        <InputLabel for="president" value="Sélectionner le Président" />
                        <select
                          id="president"
                          v-model="permanentMembers.president.user_id"
                          class="mt-1 block w-full rounded-md border-gray-300"
                          required
                          @change="updatePresidentDetails"
                        >
                          <option value="">Sélectionner un utilisateur</option>
                          <option
                            v-for="user in sousPrefets"
                            :key="user.id"
                            :value="user.id"
                          >
                            {{ user.name }}
                          </option>
                        </select>
                      </div>
                      <div>
                        <InputLabel value="Téléphone" />
                        <TextInput
                          :value="presidentDetails.phone"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          disabled
                        />
                      </div>
                      <div>
                        <InputLabel value="Whatsapp" />
                        <TextInput
                          :value="presidentDetails.whatsapp"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          disabled
                        />
                      </div>
                    </div>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                      <div>
                        <InputLabel value="Rôle" />
                        <TextInput
                          value="Secrétaire"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          disabled
                        />
                      </div>
                      <div>
                        <InputLabel for="secretary" value="Sélectionner le Secrétaire" />
                        <select
                          id="secretary"
                          v-model="permanentMembers.secretary.user_id"
                          class="mt-1 block w-full rounded-md border-gray-300"
                          required
                          @change="updateSecretaryDetails"
                        >
                          <option value="">Sélectionner un utilisateur</option>
                          <option
                            v-for="user in secretaires"
                            :key="user.id"
                            :value="user.id"
                          >
                            {{ user.name }}
                          </option>
                        </select>
                      </div>
                      <div>
                        <InputLabel value="Téléphone" />
                        <TextInput
                          :value="secretaryDetails.phone"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          disabled
                        />
                      </div>
                      <div>
                        <InputLabel value="Adresse" />
                        <TextInput
                          :value="secretaryDetails.whatsapp"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          disabled
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="previousStep"
                  class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium"
                >
                  Précédent
                </button>
                <button
                  type="button"
                  @click="saveProgress"
                  class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md text-sm font-medium"
                >
                  Sauvegarder
                </button>
                <button
                  type="button"
                  @click="nextStep"
                  class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium"
                >
                  Suivant
                </button>
              </div>
            </div>
          </form>
        </div>

        <div v-if="activeStep === 3" class="px-6 py-4">
          <!-- Étape 4: Réunion d'installation -->
          <form @submit.prevent="nextStep">
            <div class="px-6 py-4 space-y-6">
              <div>
                <InputLabel for="installation_date" value="Date de la réunion d'installation" />
                <input
                  type="date"
                  id="installation_date"
                  v-model="installationDate"
                  class="mt-1 block w-full rounded-md border-gray-300"
                  required
                />
              </div>
              <div>
                <InputLabel for="installation_location" value="Lieu de la réunion d'installation" />
                <TextInput
                  id="installation_location"
                  v-model="installationLocation"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
              </div>
              <div>
                <InputLabel for="installation_minutes" value="Compte rendu de la réunion d'installation" />
                <input
                  type="file"
                  id="installation_minutes"
                  @change="handleFileChange('minutes')"
                  class="mt-1 block w-full"
                  required
                />
              </div>
              <div>
                <InputLabel for="attendance_list" value="Liste de présence de la réunion d'installation" />
                <input
                  type="file"
                  id="attendance_list"
                  @change="handleFileChange('attendance')"
                  class="mt-1 block w-full"
                  required
                />
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="previousStep"
                  class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium"
                >
                  Précédent
                </button>
                <button
                  type="button"
                  @click="saveProgress"
                  class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md text-sm font-medium"
                >
                  Sauvegarder
                </button>
                <button
                  type="button"
                  @click="nextStep"
                  class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium"
                >
                  Suivant
                </button>
              </div>
            </div>
          </form>
        </div>

        <div v-if="activeStep === 4" class="px-6 py-4">
          <!-- Étape 5: Renseigner les représentants par village -->
          <form @submit.prevent="submit">
            <div class="px-6 py-4 space-y-6">
              <!-- Logique pour les représentants des villages -->
              <div class="mb-8">
                <h3 class="text-xl font-medium text-gray-800">Villages non ajoutés</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                  <div
                    v-for="village in unaddedVillages"
                    :key="village.id"
                    class="bg-gray-50 p-4 rounded-lg shadow-md cursor-pointer hover:bg-gray-100 transition"
                    @click="selectVillage(village.id)"
                  >
                    <p class="text-sm text-gray-700">{{ village.name }}</p>
                  </div>
                </div>
              </div>

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
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <button @click="addVillage" class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium transition duration-150 ease-in-out">
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
                    <button @click="editVillage(index)" class="mt-2 text-blue-500 hover:text-blue-700 transition duration-150 ease-in-out">
                      <i class="fas fa-edit mr-1"></i>Modifier
                    </button>
                    <button @click="removeVillage(index)" class="mt-2 text-red-500 hover:text-red-700 transition duration-150 ease-in-out">
                      <i class="fas fa-trash-alt mr-1"></i>Supprimer
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="previousStep"
                  class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium"
                >
                  Précédent
                </button>
                <button
                  type="button"
                  @click="saveProgress"
                  class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md text-sm font-medium"
                >
                  Sauvegarder
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium"
                >
                  Sauvegarder
                </button>
              </div>
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
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import { ref, computed, watch } from 'vue'

interface User {
  id: number;
  name: string;
  phone?: string;
  address?: string;
  role: string;
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

interface Props {
  users: User[];
  localities: Locality[];
  sousPrefets: User[];
  secretaires: User[];
}

const props = defineProps<Props>();

const form = useForm<{
  name: string;
  locality_id: string | number;
  status: string;
  members: Member[];
  villages: any[];
}>({
  name: '',
  locality_id: '',
  status: 'active',
  members: [],
  villages: []
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

// Réinitialiser les sélections en cascade
watch(selectedRegion, () => {
  selectedDepartment.value = null;
  form.locality_id = '';
  form.name = '';
});

watch(selectedDepartment, () => {
  form.locality_id = '';
  form.name = '';
});

const updateCommitteeName = () => {
  const subPrefecture = subPrefectures.value.find(sp => sp.id.toString() === form.locality_id.toString());
  if (subPrefecture) {
    form.name = `Comité Local de ${subPrefecture.name}`;
  }
};

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

const villages = ref([])

const fetchVillages = () => {
  if (form.locality_id) {
    fetch(`/api/sub-prefectures/${form.locality_id}/villages`)
      .then(response => response.json())
      .then(data => {
        villages.value = data;
        localStorage.setItem('villages', JSON.stringify(villages.value));
      })
      .catch(error => {
        console.error('Erreur lors de la récupération des villages:', error);
      });
  }
};

const permanentMembers = ref({
  president: { user_id: null, first_name: 'Sous-Préfet', last_name: '', phone: '' },
  secretary: { user_id: null, first_name: 'Secrétaire', last_name: '', phone: '' }
});

const presidentDetails = ref({ phone: '', whatsapp: '' });
const secretaryDetails = ref({ phone: '', whatsapp: '' });

const sousPrefets = computed(() => props.sousPrefets);
const secretaires = computed(() => props.secretaires);

const updatePresidentDetails = () => {
  const user = props.sousPrefets.find(u => u.id === permanentMembers.value.president.user_id);
  if (user) {
    presidentDetails.value.phone = user.phone || '';
    presidentDetails.value.whatsapp = user.whatsapp || '';
  } else {
    presidentDetails.value.phone = '';
    presidentDetails.value.whatsapp = '';
  }
};

const updateSecretaryDetails = () => {
  const user = props.secretaires.find(u => u.id === permanentMembers.value.secretary.user_id);
  if (user) {
    secretaryDetails.value.phone = user.phone || '';
    secretaryDetails.value.whatsapp = user.whatsapp || '';
  } else {
    secretaryDetails.value.phone = '';
    secretaryDetails.value.whatsapp = '';
  }
};

const submit = () => {
  // Ajoutez les membres permanents au formulaire
  form.members.push({
    is_user: true,
    user_id: permanentMembers.value.president.user_id,
    role: 'president',
    status: 'active',
    first_name: permanentMembers.value.president.first_name,
    last_name: permanentMembers.value.president.last_name,
    phone: presidentDetails.value.phone
  });

  form.members.push({
    is_user: true,
    user_id: permanentMembers.value.secretary.user_id,
    role: 'secretary',
    status: 'active',
    first_name: permanentMembers.value.secretary.first_name,
    last_name: permanentMembers.value.secretary.last_name,
    phone: secretaryDetails.value.phone
  });

  // Ajoutez les villages et leurs représentants au formulaire
  form.villages = addedVillages.value;

  form.post(route('local-committees.store'), {
    onSuccess: () => {
      // Affichez un message de succès ou redirigez vers une page de confirmation
      alert('Comité et représentants enregistrés avec succès.');
    }
  });
};

// État pour les étapes
const activeStep = ref(0);
const steps = [
  { label: 'Sélection de la région' },
  { label: 'Ajouter un fichier joint' },
  { label: 'Membres permanents' },
  { label: 'Réunion d\'installation' },
  { label: 'Représentants par village' }
];

const nextStep = () => {
  if (activeStep.value < steps.length - 1) {
    activeStep.value++;
  }
};

const previousStep = () => {
  if (activeStep.value > 0) {
    activeStep.value--;
  }
};

// Logique pour les représentants des villages
const selectedVillage = ref(null);
const representativeRoles = ['Chef du village', 'Représentant des femmes', 'Représentant des jeunes'];
const villageRepresentatives = ref(representativeRoles.map(role => ({
  first_name: '',
  last_name: '',
  phone: '',
  role: role
})));

const unaddedVillages = computed(() => {
  return villages.value.filter(village => !addedVillages.value.some(added => added.id === village.id));
});

const selectVillage = (villageId) => {
  selectedVillage.value = villageId;
};

const addVillage = () => {
  if (selectedVillage.value) {
    const village = villages.value.find(v => v.id === selectedVillage.value);
    if (village) {
      addedVillages.value.push({
        ...village,
        representatives: villageRepresentatives.value.map(rep => ({
          ...rep
        }))
      });
      // Réinitialiser les champs
      selectedVillage.value = null;
      villageRepresentatives.value = representativeRoles.map(role => ({
        first_name: '',
        last_name: '',
        phone: '',
        role: role
      }));
    }
  }
};

const addedVillages = ref([]);

const editVillage = (index: number) => {
  editIndex.value = index;
  editRepresentatives.value = JSON.parse(JSON.stringify(addedVillages.value[index].representatives));
};

const saveEdit = () => {
  if (editIndex.value !== null) {
    addedVillages.value[editIndex.value].representatives = JSON.parse(JSON.stringify(editRepresentatives.value));
    editIndex.value = null;
  }
};

const cancelEdit = () => {
  editIndex.value = null;
};

const removeVillage = (index: number) => {
  addedVillages.value.splice(index, 1);
};

const editIndex = ref(null);
const editRepresentatives = ref([]);

const installationDate = ref('');
const installationLocation = ref('');
const installationMinutesFile = ref<File | null>(null);
const attendanceListFile = ref<File | null>(null);

const handleFileChange = (type: string) => (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files ? target.files[0] : null;
  if (type === 'minutes') {
    installationMinutesFile.value = file;
  } else if (type === 'attendance') {
    attendanceListFile.value = file;
  }
};

const saveProgress = () => {
  form.post(route('local-committees.save-progress'), {
    preserveState: true,
    onSuccess: () => {
      alert('Progression sauvegardée avec succès.');
    }
  });
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