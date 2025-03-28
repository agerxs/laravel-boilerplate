<template>
  <div class="attendance-photo-uploader">
    <div v-if="photo" class="photo-preview">
      <img :src="photo.thumbnail_url" :alt="'Photo de présence'" class="preview-image">
      <div class="photo-info">
        <p>Taille originale: {{ formatFileSize(photo.original_size) }}</p>
        <p>Taille compressée: {{ formatFileSize(photo.compressed_size) }}</p>
        <p>Prise le: {{ formatDate(photo.taken_at) }}</p>
      </div>
      <button @click="deletePhoto" class="delete-btn">
        Supprimer la photo
      </button>
    </div>

    <div v-else class="upload-area">
      <input
        type="file"
        ref="fileInput"
        @change="handleFileSelect"
        accept="image/jpeg,image/png"
        class="hidden"
      >
      <button @click="triggerFileInput" class="upload-btn">
        Prendre une photo
      </button>
      <p class="upload-info">
        Format: JPG ou PNG<br>
        Taille max: 10MB<br>
        Dimensions min: 800x600px
      </p>
    </div>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import axios from 'axios'

export default {
  name: 'AttendancePhotoUploader',
  props: {
    attendeeId: {
      type: Number,
      required: true
    },
    initialPhoto: {
      type: Object,
      default: null
    }
  },
  setup(props) {
    const photo = ref(props.initialPhoto)
    const error = ref('')
    const fileInput = ref(null)

    const formatFileSize = (bytes) => {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleString('fr-FR')
    }

    const triggerFileInput = () => {
      fileInput.value.click()
    }

    const handleFileSelect = async (event) => {
      const file = event.target.files[0]
      if (!file) return

      // Validation de la taille
      if (file.size > 10 * 1024 * 1024) {
        error.value = 'La photo ne doit pas dépasser 10MB'
        return
      }

      // Validation des dimensions
      const img = new Image()
      img.src = URL.createObjectURL(file)
      await new Promise((resolve) => {
        img.onload = resolve
      })
      if (img.width < 800 || img.height < 600) {
        error.value = 'La photo doit faire au moins 800x600 pixels'
        return
      }

      // Upload de la photo
      const formData = new FormData()
      formData.append('photo', file)

      try {
        const response = await axios.post(`/attendees/${props.attendeeId}/photo`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        photo.value = response.data
        error.value = ''
      } catch (err) {
        error.value = err.response?.data?.message || 'Erreur lors de l\'upload de la photo'
      }
    }

    const deletePhoto = async () => {
      if (!confirm('Êtes-vous sûr de vouloir supprimer cette photo ?')) return

      try {
        await axios.delete(`/attendance-photos/${photo.value.id}`)
        photo.value = null
        error.value = ''
      } catch (err) {
        error.value = err.response?.data?.message || 'Erreur lors de la suppression de la photo'
      }
    }

    return {
      photo,
      error,
      fileInput,
      formatFileSize,
      formatDate,
      triggerFileInput,
      handleFileSelect,
      deletePhoto
    }
  }
}
</script>

<style scoped>
.attendance-photo-uploader {
  @apply p-4 bg-white rounded-lg shadow;
}

.photo-preview {
  @apply space-y-4;
}

.preview-image {
  @apply w-full h-64 object-cover rounded-lg;
}

.photo-info {
  @apply text-sm text-gray-600 space-y-1;
}

.upload-area {
  @apply border-2 border-dashed border-gray-300 rounded-lg p-6 text-center;
}

.upload-btn {
  @apply bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors;
}

.upload-info {
  @apply mt-2 text-sm text-gray-500;
}

.error-message {
  @apply mt-2 text-sm text-red-500;
}

.delete-btn {
  @apply bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors;
}
</style> 