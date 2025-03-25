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
          <!-- Boutons d'action -->
          <div class="flex justify-end space-x-3 mb-4">
            <button
              v-if="activeStep > 0"
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
              v-if="activeStep < steps.length - 1"
              type="button"
              @click="nextStep"
              class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium"
            >
              Suivant
            </button>
          </div>

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
                      v-for="region in props.localities"
                      :key="region.id"
                      :value="region.id"
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
                  >
                    <option value="">Sélectionner un département</option>
                    <option
                      v-for="department in departments"
                      :key="department.id"
                      :value="department.id"
                    >
                      {{ department.name }}
                    </option>
                  </select>
                </div>
                <!-- Sous-préfecture -->
                <div>
                  <InputLabel for="subPrefecture" value="Sous-préfecture" />
                  <select
                    id="subPrefecture"
                    v-model="selectedSubPrefecture"
                    class="mt-1 block w-full rounded-md border-gray-300"
                    required
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
          </form>
        </div>

        <div v-if="activeStep === 1" class="px-6 py-4">
          <!-- Boutons d'action -->
          <div class="flex justify-end space-x-3 mb-4">
            <button
              v-if="activeStep > 0"
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
              v-if="activeStep < steps.length - 1"
              type="button"
              @click="nextStep"
              class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium"
            >
              Suivant
            </button>
          </div>

          <!-- Étape 2: Ajouter un fichier joint -->
          <form @submit.prevent="nextStep">
            <div class="px-6 py-4 space-y-6">
              <div>
                <InputLabel for="decree" value="Arrêté ou Décret de création" />
                <div class="flex items-center space-x-2">
                  <div class="flex-grow">
                    <input
                      type="file"
                      id="decree"
                      @change="handleFileChange($event, 'decree')"
                      class="mt-1 block w-full"
                      required
                    />
                  </div>
                  <div v-if="form.decree_file" class="flex items-center bg-green-100 text-green-800 px-3 py-1 rounded-md">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm">{{ form.decree_file.name }}</span>
                  </div>
                </div>
                <div v-if="decreePreview" class="mt-2">
                  <template v-if="isImage(decreeFile)">
                    <img :src="decreePreview" alt="Aperçu de l'arrêté" class="max-w-xs" />
                  </template>
                  <template v-else>
                    <object :data="decreePreview" type="application/pdf" class="w-64 h-32 border rounded">
                      <div class="flex items-center p-3 bg-gray-100 rounded-md">
                        <svg class="w-8 h-8 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                          <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                        </svg>
                        <a :href="decreePreview" target="_blank" class="text-blue-600 hover:underline">Voir le PDF</a>
                      </div>
                    </object>
                  </template>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div v-if="activeStep === 2" class="px-6 py-4">
          <!-- Boutons d'action -->
          <div class="flex justify-end space-x-3 mb-4">
            <button
              v-if="activeStep > 0"
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
              v-if="activeStep < steps.length - 1"
              type="button"
              @click="nextStep"
              class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium"
            >
              Suivant
            </button>
          </div>

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
                          :modelValue="presidentDetails.phone"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          disabled
                          @update:modelValue="value => presidentDetails.phone = value"
                        />
                      </div>
                      <div>
                        <InputLabel value="Whatsapp" />
                        <TextInput
                          :modelValue="presidentDetails.whatsapp"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          disabled
                          @update:modelValue="value => presidentDetails.whatsapp = value"
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
                          :modelValue="secretaryDetails.phone"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          disabled
                          @update:modelValue="value => secretaryDetails.phone = value"
                        />
                      </div>
                      <div>
                        <InputLabel value="Adresse" />
                        <TextInput
                          :modelValue="secretaryDetails.whatsapp"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          disabled
                          @update:modelValue="value => secretaryDetails.whatsapp = value"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div v-if="activeStep === 3" class="px-6 py-4">
          <!-- Boutons d'action -->
          <div class="flex justify-end space-x-3 mb-4">
            <button
              v-if="activeStep > 0"
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
              v-if="activeStep < steps.length - 1"
              type="button"
              @click="nextStep"
              class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-sm font-medium"
            >
              Suivant
            </button>
          </div>

          <!-- Étape 4: Renseigner les membres permanents -->
          <form @submit.prevent="nextStep">
            <div class="px-6 py-4 space-y-6">
              <div>
                <InputLabel for="installation_date" value="Date de la réunion d'installation" />
                <input
                  type="date"
                  id="installation_date"
                  v-model="form.installation_date"
                  class="mt-1 block w-full rounded-md border-gray-300"
                  required
                />
              </div>
              <div>
                <InputLabel for="installation_location" value="Lieu de la réunion d'installation" />
                <TextInput
                  id="installation_location"
                  v-model="form.installation_location"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
              </div>
              <div>
                <InputLabel for="installation_minutes" value="Compte rendu de la réunion d'installation" />
                <div class="relative">
                  <input
                    type="file"
                    id="installation_minutes"
                    @change="handleFileChange($event, 'minutes')"
                    class="mt-1 block w-full"
                    required
                  />
                  <div v-if="form.installation_minutes_file" class="absolute top-0 right-0 bg-green-100 text-green-800 px-2 py-1 rounded-md text-xs font-medium">
                    Fichier chargé: {{ form.installation_minutes_file.name }}
                  </div>
                </div>
                <div v-if="minutesPreview" class="mt-2">
                  <div class="flex items-center p-3 bg-gray-100 rounded-md">
                    <svg class="w-8 h-8 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                      <p class="text-sm font-medium">Compte rendu (PDF)</p>
                      <a :href="minutesPreview" target="_blank" class="text-xs text-blue-600 hover:underline">Voir le document</a>
                    </div>
                  </div>
                </div>
              </div>
              <div>
                <InputLabel for="attendance_list" value="Liste de présence de la réunion d'installation" />
                <div class="relative">
                  <input
                    type="file"
                    id="attendance_list"
                    @change="handleFileChange($event, 'attendance')"
                    class="mt-1 block w-full"
                    required
                  />
                  <div v-if="form.attendance_list_file" class="absolute top-0 right-0 bg-green-100 text-green-800 px-2 py-1 rounded-md text-xs font-medium">
                    Fichier chargé: {{ form.attendance_list_file.name }}
                  </div>
                </div>
                <div v-if="attendancePreview" class="mt-2">
                  <div class="flex items-center p-3 bg-gray-100 rounded-md">
                    <svg class="w-8 h-8 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                      <p class="text-sm font-medium">Liste de présence (PDF)</p>
                      <a :href="attendancePreview" target="_blank" class="text-xs text-blue-600 hover:underline">Voir le document</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div v-if="activeStep === 4" class="px-6 py-4">
          <!-- Boutons d'action -->
          <div class="flex justify-end space-x-3 mb-4">
            <button
              v-if="activeStep > 0"
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
              @click="submitAndPublish"
              class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium flex items-center"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Enregistrer et publier
            </button>
          </div>

          <!-- Étape 5: Renseigner les représentants par village -->
          <form @submit.prevent="submit">
            <div class="px-6 py-4 space-y-6">
              <!-- Logique pour les représentants des villages -->
              <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900">Villages disponibles ({{ unaddedVillages.length }} )</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                  <div 
                    v-for="village in unaddedVillages" 
                    :key="village.id"
                    class="bg-gray-50 p-4 rounded-lg shadow-md cursor-pointer hover:bg-gray-100 transition"
                    @click="openRepresentativeModal(village)"
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
                    <button @click="editVillageRepresentatives(village)" class="mt-2 text-blue-500 hover:text-blue-700 transition duration-150 ease-in-out">
                      <i class="fas fa-edit mr-1"></i>Modifier
                    </button>
                    <button @click="removeVillage(index)" class="mt-2 text-red-500 hover:text-red-700 transition duration-150 ease-in-out">
                      <i class="fas fa-trash-alt mr-1"></i>Supprimer
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Ajoutez ceci quelque part dans votre template, généralement à la fin -->
    <div v-if="showToast" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center z-50">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
      <span>{{ toastMessage }}</span>
      <button @click="showToast = false" class="ml-2 text-white">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <!-- Ajouter un bouton pour supprimer le brouillon si nécessaire -->
    <div v-if="props.draft" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-yellow-800 font-medium">Vous travaillez sur un brouillon</p>
          <p class="text-sm text-yellow-600">Dernière modification: {{ new Date(props.draft.updated_at).toLocaleString() }}</p>
        </div>
        <button 
          @click="deleteDraft" 
          class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm"
        >
          Supprimer ce brouillon
        </button>
      </div>
    </div>

    <!-- Modal pour ajouter des représentants -->
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
                        <InputLabel value="Prénom" />
                        <TextInput
                          v-model="rep.first_name"
                          type="text"
                          class="mt-1 block w-full"
                          required
                        />
                      </div>
                      <div>
                        <InputLabel value="Nom" />
                        <TextInput
                          v-model="rep.last_name"
                          type="text"
                          class="mt-1 block w-full"
                          required
                        />
                      </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-4">
                      <div>
                        <InputLabel value="Téléphone" />
                        <TextInput
                          v-model="rep.phone"
                          type="text"
                          class="mt-1 block w-full"
                        />
                      </div>
                      <div>
                        <InputLabel value="Rôle" />
                        <select
                          v-model="rep.role"
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
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import axios from 'axios'

interface User {
  id: number;
  name: string;
  phone?: string;
  whatsapp?: string;
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
  [key: string]: any;
}

interface Props {
  users: User[];
  localities: Locality[];
  sousPrefets: User[];
  secretaires: User[];
  draft: any;
}

const props = defineProps<Props>();

const form = useForm<{
  name: string;
  locality_id: string | number;
  status: string;
  members: Member[];
  villages: any[];
  installation_date: string;
  installation_location: string;
  decree_file: File | null;
  installation_minutes_file: File | null;
  attendance_list_file: File | null;
}>({
  name: '',
  locality_id: '',
  status: 'active',
  members: [],
  villages: [],
  installation_date: '',
  installation_location: '',
  decree_file: null,
  installation_minutes_file: null,
  attendance_list_file: null
});

// État pour la sélection en cascade
const selectedRegion = ref(null);
const selectedDepartment = ref(null);
const selectedSubPrefecture = ref(null);

// État pour la sélection en cascade
const departments = ref([]);
const subPrefectures = ref([]);
const villages = ref([]);

// Fonction pour mettre à jour les départements lorsqu'une région est sélectionnée
watch(selectedRegion, (newRegionId) => {
  if (newRegionId) {
    const region = props.localities.find(r => r.id === newRegionId);
    console.log(region);
    departments.value = region ? region.children || [] : [];
    selectedDepartment.value = null;
    subPrefectures.value = [];
    form.locality_id = newRegionId;
  } else {
    departments.value = [];
    form.locality_id = '';
  }
});

// Fonction pour mettre à jour les sous-préfectures lorsqu'un département est sélectionné
watch(selectedDepartment, (newDepartmentId) => {
  if (newDepartmentId) {
    const department = departments.value.find(d => d.id === newDepartmentId);
    subPrefectures.value = department ? department.children || [] : [];
    selectedSubPrefecture.value = null;
    form.locality_id = newDepartmentId;
  } else {
    subPrefectures.value = [];
  }
});

// Fonction pour mettre à jour la localité sélectionnée lorsqu'une sous-préfecture est sélectionnée
watch(selectedSubPrefecture, (newSubPrefectureId) => {
  if (newSubPrefectureId) {
    form.locality_id = newSubPrefectureId;
  
    fetchVillages();
  }
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
    user_id: permanentMembers.value.president.user_id || undefined,
    role: 'president',
    status: 'active',
    first_name: permanentMembers.value.president.first_name,
    last_name: permanentMembers.value.president.last_name,
    phone: presidentDetails.value.phone
  });

  form.members.push({
    is_user: true,
    user_id: permanentMembers.value.secretary.user_id || undefined,
    role: 'secretary',
    status: 'active',
    first_name: permanentMembers.value.secretary.first_name,
    last_name: permanentMembers.value.secretary.last_name,
    phone: secretaryDetails.value.phone
  });

  // Ajoutez les villages et leurs représentants au formulaire
  form.villages = addedVillages.value;

  // Ajoutez les informations de la réunion d'installation et de l'arrêté
  form.installation_date = installationDate.value;
  form.installation_location = installationLocation.value;
  form.decree_file = decreeFile.value;
  form.installation_minutes_file = installationMinutesFile.value;
  form.attendance_list_file = attendanceListFile.value;

  form.post(route('local-committees.store'), {
    onSuccess: () => {
      // Affichez un message de succès ou redirigez vers une page de confirmation
      alert('Comité et représentants enregistrés avec succès.');
    },
    forceFormData: true
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
  const village = villages.value.find(v => v.id === villageId);
  if (village) {
    openRepresentativeModal(village);
  }
};

const addVillage = () => {
  if (selectedVillage.value) {
    const village = villages.value.find(v => v.id === selectedVillage.value.id);
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

const decreePreview = ref<string | null>(null);
const minutesPreview = ref<string | null>(null);
const attendancePreview = ref<string | null>(null);

const decreeFile = ref<File | null>(null);

const isImage = (file) => {
  return file && file.type.startsWith('image/');
};

const handleFileChange = (event:Event,stype: string) =>  {
  const target = event.target as HTMLInputElement;
  const file = target.files ? target.files[0] : null;
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      console.log(stype);
      const result = e.target?.result as string;
      if (stype === 'decree') {
        decreeFile.value = file;
        form.decree_file = file;
        decreePreview.value = result;
      } else if (stype === 'minutes') {
        installationMinutesFile.value = file;
        form.installation_minutes_file = file;
        minutesPreview.value = result;
      } else if (stype === 'attendance') {
        attendanceListFile.value = file;
        form.attendance_list_file = file;
        attendancePreview.value = result;
      }
    };
    reader.readAsDataURL(file);
  }
};

// Ajoutez ces variables d'état pour le toast
const showToast = ref(false);
const toastMessage = ref('');

// Fonction pour afficher un toast
const showToastMessage = (message) => {
  toastMessage.value = message;
  showToast.value = true;
  setTimeout(() => {
    showToast.value = false;
  }, 3000); // Le toast disparaît après 3 secondes
};

// Modifiez la fonction saveProgress pour inclure les membres permanents et les représentants par village
const saveProgress = () => {
  // Créez un FormData pour envoyer les fichiers
  const formData = new FormData();
  
  // Ajoutez les données du formulaire au FormData
  formData.append('name', form.name);
  formData.append('locality_id', form.locality_id);
  formData.append('status', form.status);
  formData.append('active_step', activeStep.value);
  
  // Préparez les membres permanents
  const members = [];
  
  // Ajoutez le président s'il est sélectionné
  if (permanentMembers.value.president.user_id) {
    members.push({
      is_user: true,
      user_id: permanentMembers.value.president.user_id,
      role: 'president',
      status: 'active',
      first_name: permanentMembers.value.president.first_name,
      last_name: permanentMembers.value.president.last_name,
      phone: presidentDetails.value.phone
    });
  }
  
  // Ajoutez le secrétaire s'il est sélectionné
  if (permanentMembers.value.secretary.user_id) {
    members.push({
      is_user: true,
      user_id: permanentMembers.value.secretary.user_id,
      role: 'secretary',
      status: 'active',
      first_name: permanentMembers.value.secretary.first_name,
      last_name: permanentMembers.value.secretary.last_name,
      phone: secretaryDetails.value.phone
    });
  }
  
  // Ajoutez les membres au FormData
  formData.append('members', JSON.stringify(members));
  
  // Ajoutez les villages et leurs représentants
  formData.append('villages', JSON.stringify(addedVillages.value));
  
  // Ajoutez les dates et lieux
  formData.append('installation_date', form.installation_date);
  formData.append('installation_location', form.installation_location);
  
  // Ajoutez les fichiers
  if (form.decree_file) {
    formData.append('decree_file', form.decree_file);
  }
  
  if (form.installation_minutes_file) {
    formData.append('installation_minutes_file', form.installation_minutes_file);
  }
  
  if (form.attendance_list_file) {
    formData.append('attendance_list_file', form.attendance_list_file);
  }
  
  // Envoyez la requête avec Axios
  axios.post(route('local-committees.save-progress'), formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
  .then(response => {
    showToastMessage('Progression sauvegardée avec succès');
  })
  .catch(error => {
    console.error('Erreur lors de la sauvegarde:', error);
    showToastMessage('Erreur lors de la sauvegarde');
  });
};

// Modifiez la fonction loadDraftData pour utiliser nextTick
const loadDraftData = () => {
  if (props.draft) {
    console.log('Chargement du brouillon:', props.draft);
    
    // Charger les données de base
    form.name = props.draft.name || '';
    form.status = props.draft.status || 'active';
    
    // Si une localité est définie, nous devons mettre à jour la cascade de sélection
    if (props.draft.locality_id) {
      // Trouver la localité dans la hiérarchie
      const findLocalityInHierarchy = (localities, targetId) => {
        for (const region of localities) {
          if (region.id === targetId) {
            return { region: region, department: null, subPrefecture: null };
          }
          
          for (const department of region.children || []) {
            if (department.id === targetId) {
              return { region: region, department: department, subPrefecture: null };
            }
            
            for (const subPrefecture of department.children || []) {
              if (subPrefecture.id === targetId) {
                return { region: region, department: department, subPrefecture: subPrefecture };
              }
            }
          }
        }
        return null;
      };
      
      const result = findLocalityInHierarchy(props.localities, props.draft.locality_id);
      
      if (result) {
        if (result.region) {
          // Définir d'abord la région
          selectedRegion.value = result.region.id;
          
          // Utiliser nextTick pour s'assurer que les départements sont chargés
          nextTick(() => {
            // Vérifier que les départements sont chargés
            if (departments.value.length === 0) {
              departments.value = result.region.children || [];
            }
            
            if (result.department) {
              // Définir le département
              selectedDepartment.value = result.department.id;
              
              // Utiliser nextTick pour s'assurer que les sous-préfectures sont chargées
              nextTick(() => {
                // Vérifier que les sous-préfectures sont chargées
                if (subPrefectures.value.length === 0) {
                  subPrefectures.value = result.department.children || [];
                }
                
                if (result.subPrefecture) {
                  // Définir la sous-préfecture
                  selectedSubPrefecture.value = result.subPrefecture.id;
                  form.locality_id = result.subPrefecture.id;
                } else {
                  form.locality_id = result.department.id;
                }
                console.log(form.locality_id);
                console.log("form.locality_id");
                // Charger les villages si nécessaire
                if (form.locality_id) {
                  fetchVillages();
                }
              });
            } else {
              form.locality_id = result.region.id;
            }
          });
        }
      }
    }
    
    // Charger l'étape active
    activeStep.value = props.draft.last_active_step || 0;
    
    // Charger les données du formulaire
    if (props.draft.form_data) {
      console.log('Données du formulaire:', props.draft.form_data);
      
      // Si form_data est une chaîne JSON, la parser
      const formData = typeof props.draft.form_data === 'string' 
        ? JSON.parse(props.draft.form_data) 
        : props.draft.form_data;
      
      // Charger les membres
      if (formData.members && formData.members.length > 0) {
        console.log('Membres trouvés:', formData.members);
        formData.members.forEach(member => {
          if (member.role === 'president') {
            permanentMembers.value.president.user_id = member.user_id;
            updatePresidentDetails();
          } else if (member.role === 'secretary') {
            permanentMembers.value.secretary.user_id = member.user_id;
            updateSecretaryDetails();
          }
        });
      }
      
      // Charger les villages
      if (formData.villages && formData.villages.length > 0) {
        console.log('Villages trouvés:', formData.villages);
        addedVillages.value = formData.villages;
      }
      
      // Charger les données de la réunion d'installation
      form.installation_date = formData.installation_date || '';
      form.installation_location = formData.installation_location || '';
    }
    
    // Charger les fichiers
    if (props.draft.files) {
      console.log('Fichiers trouvés:', props.draft.files);
      
      // Si files est une chaîne JSON, la parser
      const files = typeof props.draft.files === 'string' 
        ? JSON.parse(props.draft.files) 
        : props.draft.files;
      
      if (files.decree_file) {
        decreePreview.value = `/storage/${files.decree_file}`;
        form.decree_file = { name: files.decree_file.split('/').pop() };
      }
      
      if (files.installation_minutes_file) {
        minutesPreview.value = `/storage/${files.installation_minutes_file}`;
        form.installation_minutes_file = { name: files.installation_minutes_file.split('/').pop() };
      }
      
      if (files.attendance_list_file) {
        attendancePreview.value = `/storage/${files.attendance_list_file}`;
        form.attendance_list_file = { name: files.attendance_list_file.split('/').pop() };
      }
    }
  }
};

// Assurez-vous que le chargement des données se fait après le montage du composant
onMounted(() => {
  console.log('Composant monté, props.draft:', props.draft);
  loadDraftData();
  checkForDrafts();
});

// Vérifier s'il y a des brouillons disponibles au chargement
const checkForDrafts = () => {
  if (!props.draft) {
    axios.get(route('local-committees.drafts'))
      .then(response => {
        if (response.data.draft) {
          // Afficher une boîte de dialogue pour demander à l'utilisateur s'il souhaite continuer son brouillon
          if (confirm('Vous avez un brouillon en cours. Voulez-vous le continuer?')) {
            window.location.href = route('local-committees.load-draft', response.data.draft.id);
          }
        }
      })
      .catch(error => {
        console.error('Erreur lors de la récupération des brouillons:', error);
      });
  }
};

// Fonction pour supprimer un brouillon
const deleteDraft = () => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce brouillon? Cette action est irréversible.')) {
    axios.delete(route('local-committees.delete-draft', props.draft.id))
      .then(response => {
        showToastMessage('Brouillon supprimé avec succès');
        // Rediriger vers la page de création sans brouillon
        window.location.href = route('local-committees.create');
      })
      .catch(error => {
        console.error('Erreur lors de la suppression du brouillon:', error);
        showToastMessage('Erreur lors de la suppression du brouillon');
      });
  }
};

// Ajoutez cette fonction pour soumettre et publier le comité local
const submitAndPublish = () => {
  updateCommitteeName();
  // Vérifier que toutes les informations nécessaires sont présentes
  if (!validateForm()) {
    showToastMessage('Veuillez remplir tous les champs obligatoires avant de publier');
    return;
  }
  
  // Préparer les données pour la soumission
  const formData = new FormData();
  
  // Ajouter les données de base
  formData.append('name', form.name);
  formData.append('locality_id', form.locality_id);
  formData.append('status', 'active'); // Définir le statut comme actif pour la publication
  
  // Ajouter les membres permanents
  const members = [];
  
  // Ajouter le président
  if (permanentMembers.value.president.user_id) {
    members.push({
      is_user: true,
      user_id: permanentMembers.value.president.user_id,
      role: 'president',
      status: 'active',
      first_name: permanentMembers.value.president.first_name,
      last_name: permanentMembers.value.president.last_name,
      phone: presidentDetails.value.phone
    });
  }
  
  // Ajouter le secrétaire
  if (permanentMembers.value.secretary.user_id) {
    members.push({
      is_user: true,
      user_id: permanentMembers.value.secretary.user_id,
      role: 'secretary',
      status: 'active',
      first_name: permanentMembers.value.secretary.first_name,
      last_name: permanentMembers.value.secretary.last_name,
      phone: secretaryDetails.value.phone
    });
  }
  
  // Ajouter les membres au FormData
  formData.append('members', JSON.stringify(members));
  
  // Ajouter les villages et leurs représentants
  formData.append('villages', JSON.stringify(addedVillages.value));
  
  // Ajoutez les informations de la réunion d'installation
  formData.append('installation_date', form.installation_date);
  formData.append('installation_location', form.installation_location);
  
  // Ajouter les fichiers
  if (form.decree_file instanceof File) {
    formData.append('decree_file', form.decree_file);
  }
  
  if (form.installation_minutes_file instanceof File) {
    formData.append('installation_minutes_file', form.installation_minutes_file);
  }
  
  if (form.attendance_list_file instanceof File) {
    formData.append('attendance_list_file', form.attendance_list_file);
  }
  
  // Soumettre le formulaire
  axios.post(route('local-committees.store'), formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
  .then(response => {
    // Si le brouillon existe, le supprimer après la publication réussie
    if (props.draft && props.draft.id) {
      axios.delete(route('local-committees.delete-draft', props.draft.id))
        .catch(error => {
          console.error('Erreur lors de la suppression du brouillon:', error);
        });
    }
    
    // Rediriger vers la page de détails du comité local créé
    window.location.href = route('local-committees.show', response.data.id);
  })
  .catch(error => {
    console.error('Erreur lors de la publication:', error);
    showToastMessage('Erreur lors de la publication du comité local');
  });
};

// Fonction pour valider le formulaire avant la soumission
const validateForm = () => {
  // Vérifier les champs obligatoires
  console.log(form.name, form.locality_id);
  if ( !form.locality_id) {
    console.log("stop one");
    return false;
  }
  
  // Vérifier que le président et le secrétaire sont sélectionnés
  if (!permanentMembers.value.president.user_id || !permanentMembers.value.secretary.user_id) {
    console.log("stop two");
    return false;
  }
  
  // Vérifier qu'au moins un village est ajouté
  //if (addedVillages.value.length === 0) {
  //  console.log("stop three");
  //  return false;
  //}
  
  // Vérifier que les fichiers obligatoires sont présents
  //if (!form.decree_file) {
  //  console.log("stop four");
  //  return false;
  //}
  
  // Vérifier les informations de la réunion d'installation
  //if (!form.installation_date || !form.installation_location) {
  //  console.log("stop five");
  //  return false;
  //}
  
  return true;
};

// Ajoutez ces variables d'état pour le modal
const showRepresentativeModal = ref(false);

// Fonction pour ouvrir le modal des représentants
const openRepresentativeModal = (village) => {
  selectedVillage.value = village;
  
  // Vérifier si ce village a déjà des représentants dans addedVillages
  const existingVillage = addedVillages.value.find(v => v.id === village.id);
  
  if (existingVillage && existingVillage.representatives) {
    // Utiliser les représentants existants
    villageRepresentatives.value = [...existingVillage.representatives];
  } else {
    // Initialiser avec un représentant vide
    villageRepresentatives.value = [{
      first_name: '',
      last_name: '',
      phone: '',
      role: ''
    }];
  }
  
  showRepresentativeModal.value = true;
};

// Fonction pour fermer le modal
const closeRepresentativeModal = () => {
  showRepresentativeModal.value = false;
  selectedVillage.value = null;
  villageRepresentatives.value = [];
};

// Fonction pour ajouter un représentant
const addRepresentative = () => {
  villageRepresentatives.value.push({
    first_name: '',
    last_name: '',
    phone: '',
    role: ''
  });
};

// Fonction pour supprimer un représentant
const removeRepresentative = (index) => {
  villageRepresentatives.value.splice(index, 1);
  
  // S'assurer qu'il y a toujours au moins un représentant
  if (villageRepresentatives.value.length === 0) {
    addRepresentative();
  }
};

// Fonction pour enregistrer les représentants
const saveRepresentatives = () => {
  if (!selectedVillage.value) return;
  
  // Valider les données
  const isValid = villageRepresentatives.value.every(rep => 
    rep.first_name.trim() !== '' && 
    rep.last_name.trim() !== '' && 
    rep.role.trim() !== ''
  );
  
  if (!isValid) {
    alert('Veuillez remplir tous les champs obligatoires pour chaque représentant.');
    return;
  }
  
  // Trouver si ce village existe déjà dans addedVillages
  const existingIndex = addedVillages.value.findIndex(v => v.id === selectedVillage.value.id);
  
  if (existingIndex >= 0) {
    // Mettre à jour les représentants du village existant
    addedVillages.value[existingIndex].representatives = [...villageRepresentatives.value];
  } else {
    // Ajouter le village avec ses représentants
    addedVillages.value.push({
      id: selectedVillage.value.id,
      name: selectedVillage.value.name,
      representatives: [...villageRepresentatives.value]
    });
  }
  
  // Fermer le modal
  closeRepresentativeModal();
};

// Ajoutez ces fonctions pour vérifier si un village est déjà ajouté et obtenir le nombre de représentants
const isVillageAdded = (villageId) => {
  return addedVillages.value.some(v => v.id === villageId);
};

const getRepresentativesCount = (villageId) => {
  const village = addedVillages.value.find(v => v.id === villageId);
  return village && village.representatives ? village.representatives.length : 0;
};

// Ajoutez cette fonction pour éditer les représentants d'un village
const editVillageRepresentatives = (village) => {
  // Ouvrir le modal avec les représentants existants du village
  selectedVillage.value = village;
  villageRepresentatives.value = [...village.representatives];
  showRepresentativeModal.value = true;
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