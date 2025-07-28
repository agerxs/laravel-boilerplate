<template>
  <div>
    <!-- Bouton de confirmation (pour les secrétaires) -->
   
    
    <!-- Bouton de prévalidation (pour les secrétaires) -->
   
    <!-- Bouton de validation (pour les sous-préfets) -->
   
    
    <!-- Modal de validation définitive -->
    <Modal :show="showValidationModal" @close="closeValidationModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Validation définitive de la réunion
        </h3>
        
        <p class="mb-4 text-sm text-gray-600">
          Cette action validera définitivement la réunion. Une fois validée, la réunion ne pourra plus être modifiée.
        </p>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Commentaires (optionnel)
          </label>
          <textarea
            v-model="validationComments"
            rows="3"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            placeholder="Commentaires sur la validation..."
          ></textarea>
        </div>
        
        <div class="flex justify-end">
          <button
            @click="closeValidationModal"
            class="bg-white px-4 py-2 border border-gray-300 rounded-md mr-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            @click="validateMeeting"
            class="bg-violet-600 px-4 py-2 rounded-md text-sm font-medium text-white hover:bg-violet-700"
          >
            Valider définitivement
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { 
  ClipboardIcon, 
  ShieldCheckIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import axios from 'axios'
import Modal from '@/Components/Modal.vue'
import { usePage } from '@inertiajs/vue3'

interface Meeting {
  id: number;
  status: string;
}

interface Role {
  id: number;
  name: string;
}

interface User {
  id: number;
  name: string;
  roles?: Role[];
}

interface PageProps {
  auth: {
    user: User;
  };
}

const props = defineProps<{
  meeting: Meeting;
}>()

const emit = defineEmits<{
  (e: 'meeting-updated', meeting: Meeting): void;
}>()

const toast = useToast()
const page = usePage<PageProps>()

// État pour la modal de validation
const showValidationModal = ref(false)
const validationComments = ref('')

// Récupérer les rôles de l'utilisateur depuis Inertia
const userRoles = computed<Role[]>(() => {
  return page.props.auth?.user?.roles || []
})

// Vérifier si l'utilisateur est un secrétaire
const isSecretary = computed(() => {
  console.log("userRoles.value");
  console.log(userRoles.value);
  //return true;
  return userRoles.value.some((role: Role) => 
    ['secretaire', 'Secrétaire', 'admin', 'Admin'].includes(role.name)
  )
})

// Vérifier si l'utilisateur est un sous-préfet
const isSubPrefect = computed(() => {
  return userRoles.value.some((role: Role) => 
    ['sous-prefet', 'Sous-prefet'].includes(role.name)
  )
})

// Vérifier si la réunion peut être confirmée
const canConfirm = computed(() => {
  return isSecretary.value && props.meeting.status === 'scheduled'
})

// Vérifier si la réunion peut être prévalidée
const canPrevalidate = computed(() => {
  console.log("canPrevalidate");
  return isSecretary.value &&
         props.meeting.status === 'completed'
})

// Vérifier si la réunion peut être validée
const canValidate = computed(() => {
  return isSubPrefect.value &&
         props.meeting.status === 'completed'
})

// Confirmer la réunion
const confirmMeeting = async () => {
  if (!confirm('Êtes-vous sûr de vouloir confirmer cette réunion ? Cela permettra de commencer la gestion des paiements.')) return
  
  try {
    const response = await axios.post(
      route('meetings.confirm', props.meeting.id)
    )
    
    toast.success('Réunion confirmée avec succès')
    emit('meeting-updated', response.data.meeting)
    
    // Recharger la page pour afficher les changements
    window.location.reload()
  } catch (error: any) {
    toast.error(
      error.response?.data?.message || 
      'Une erreur est survenue lors de la confirmation'
    )
  }
}



// Valider définitivement la réunion
const validateMeeting = async () => {
  try {
    const response = await axios.post(
      route('meetings.validate', props.meeting.id), 
      { validation_comments: validationComments.value }
    )
    
    toast.success('Réunion validée avec succès')
    closeValidationModal()
    emit('meeting-updated', response.data.meeting)
    
    // Recharger la page pour afficher les changements
    window.location.reload()
  } catch (error: any) {
    toast.error(
      error.response?.data?.message || 
      'Une erreur est survenue lors de la validation'
    )
  }
}

// Fermer la modal de validation
const closeValidationModal = () => {
  showValidationModal.value = false
  validationComments.value = ''
}
</script> 