<template>
  <Head title="Gestion des APKs" />
  <AppLayout title="Gestion des APKs">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-lg shadow px-6 py-4 mb-4">
      <div>
        <h2 class="text-xl font-semibold text-gray-900 mb-1">Gestion des APKs</h2>
        <p class="text-sm text-gray-500">Gérez les versions de l'application mobile et proposez les mises à jour aux utilisateurs.</p>
      </div>
      <button @click="showModal = true" class="btn-apk-upload">Nouvelle version</button>
    </div>

    <!-- Modal formulaire upload -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
        <button @click="showModal = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <h3 class="text-lg font-semibold mb-4">Nouvelle version APK</h3>
        <form @submit.prevent="submit" class="flex flex-col gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Code version</label>
            <input v-model="form.version_code" type="number" placeholder="Code version" class="input-apk w-full" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom version</label>
            <input v-model="form.version_name" type="text" placeholder="Nom version" class="input-apk w-full" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fichier APK</label>
            <input @change="onFileChange" type="file" accept=".apk" class="input-apk-file w-full" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes de version</label>
            <input v-model="form.release_notes" type="text" placeholder="Notes de version" class="input-apk w-full" />
          </div>
          <div class="flex justify-end">
            <button type="submit" :disabled="form.processing" class="btn-apk-upload">Uploader</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">APK</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="v in versions" :key="v.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ v.version_code }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ v.version_name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ new Date(v.created_at).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' }) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ v.release_notes }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-700">
              <a :href="`/storage/${v.apk_file}`" target="_blank" class="underline">Télécharger</a>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button @click="destroy(v.id)" class="text-red-600 hover:text-red-900 action-button" title="Supprimer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
const props = defineProps({ versions: Array })

const showModal = ref(false)
const form = useForm({
  version_code: '',
  version_name: '',
  apk_file: null,
  release_notes: ''
})

function onFileChange(e) {
  form.apk_file = e.target.files[0]
}

function submit() {
  form.post(route('admin.app_versions.store'), {
    forceFormData: true,
    onSuccess: () => {
      form.reset()
      showModal.value = false
    }
  })
}

function destroy(id) {
  if (confirm('Supprimer cette version ?')) {
    router.delete(route('admin.app_versions.destroy', id))
  }
}
</script>

<style>
.input-apk, .input-apk-file {
  width: 100%;
  border-radius: 0.375rem;
  border: 1px solid #d1d5db;
  padding: 0.5rem 0.75rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: #fff;
}
.input-apk:focus, .input-apk-file:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 2px #6366f133;
}
.btn-apk-upload {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1.25rem;
  background-color: #6366f1;
  border-radius: 0.5rem;
  color: #fff;
  font-weight: 500;
  font-size: 0.95rem;
  transition: background 0.2s;
  border: none;
}
.btn-apk-upload:hover {
  background-color: #4338ca;
}
.action-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}
</style> 