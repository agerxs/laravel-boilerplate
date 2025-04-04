<template>
  <div class="photo-capture">
    <canvas ref="canvas" class="hidden"></canvas>
    
    <div v-if="!photoTaken" class="camera-container">
      <video ref="video" autoplay playsinline class="camera-preview rounded-lg shadow-lg"></video>
      <button 
        @click="capturePhoto" 
        class="capture-button absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-white rounded-full p-4 shadow-lg hover:bg-gray-100"
        :disabled="!hasGeolocation"
      >
        <CameraIcon class="h-8 w-8 text-primary-600" />
      </button>
      <div v-if="!hasGeolocation" class="absolute top-4 left-1/2 transform -translate-x-1/2 bg-red-100 text-red-800 px-4 py-2 rounded-md text-sm">
        Veuillez activer la géolocalisation
      </div>
    </div>
    
    <div v-else class="preview-container">
      <img :src="photoPreview" class="photo-preview rounded-lg shadow-lg" />
      <div class="mt-4 flex justify-center space-x-4">
        <button 
          @click="retakePhoto" 
          class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200"
        >
          Reprendre la photo
        </button>
        <button 
          @click="confirmPhoto" 
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center space-x-2"
          :disabled="!hasGeolocation"
        >
          <span>Confirmer la photo</span>
          <span v-if="!hasGeolocation" class="text-xs bg-red-500 text-white px-2 py-1 rounded-full">
            GPS requis
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { CameraIcon } from '@heroicons/vue/24/outline'

const emit = defineEmits(['photo-captured'])

const video = ref(null)
const canvas = ref(null)
const stream = ref(null)
const photoTaken = ref(false)
const hasGeolocation = ref(false)
const currentPosition = ref(null)
const photoPreview = ref('')

// Démarrer la caméra
const startCamera = async () => {
  try {
    stream.value = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment' },
      audio: false
    })
    if (video.value) {
      video.value.srcObject = stream.value
    }
  } catch (error) {
    console.error('Erreur lors de l\'accès à la caméra:', error)
  }
}

// Obtenir la géolocalisation
const getGeolocation = () => {
  if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        currentPosition.value = position
        hasGeolocation.value = true
      },
      (error) => {
        console.error('Erreur de géolocalisation:', error)
        hasGeolocation.value = false
      }
    )
  }
}

// Prendre la photo
const capturePhoto = () => {
  console.log('Tentative de capture de photo...')
  if (!canvas.value || !video.value) {
    console.error('Canvas ou vidéo non initialisés:', { canvas: !!canvas.value, video: !!video.value })
    return
  }
  
  try {
    const context = canvas.value.getContext('2d')
    canvas.value.width = video.value.videoWidth
    canvas.value.height = video.value.videoHeight
    context.drawImage(video.value, 0, 0, canvas.value.width, canvas.value.height)
    
    // Convertir le canvas en URL pour l'aperçu
    photoPreview.value = canvas.value.toDataURL('image/jpeg')
    photoTaken.value = true
    console.log('Photo capturée avec succès')
  } catch (error) {
    console.error('Erreur lors de la capture de la photo:', error)
  }
}

// Reprendre la photo
const retakePhoto = () => {
  photoTaken.value = false
  photoPreview.value = ''
}

// Confirmer la photo
const confirmPhoto = () => {
  console.log('Tentative de confirmation de la photo...')
  if (!hasGeolocation.value) {
    console.error('La géolocalisation n\'est pas disponible')
    return
  }
  
  if (!canvas.value || !currentPosition.value) {
    console.error('Canvas ou position non disponibles pour la confirmation:', {
      canvas: !!canvas.value,
      position: !!currentPosition.value
    })
    return
  }
  
  // Convertir le canvas en blob
  canvas.value.toBlob((blob) => {
    console.log('Blob créé avec succès')
    const photoData = {
      photo: blob,
      location: {
        latitude: currentPosition.value.coords.latitude,
        longitude: currentPosition.value.coords.longitude
      },
      timestamp: new Date().toISOString()
    }
    console.log('Émission de l\'événement photo-captured avec les données:', photoData)
    emit('photo-captured', photoData)
  }, 'image/jpeg')
}

// Nettoyage
const cleanup = () => {
  if (stream.value) {
    stream.value.getTracks().forEach(track => track.stop())
  }
}

onMounted(() => {
  console.log('Composant monté, initialisation...')
  startCamera()
  getGeolocation()
})

onUnmounted(() => {
  cleanup()
})
</script>

<style scoped>
.photo-capture {
  position: relative;
  width: 100%;
  max-width: 640px;
  margin: 0 auto;
}

.camera-container,
.preview-container {
  position: relative;
  width: 100%;
  aspect-ratio: 4/3;
}

.camera-preview,
.photo-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.capture-button {
  transition: all 0.2s;
}

.capture-button:hover {
  transform: translate(-50%, -2px);
}

.capture-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style> 