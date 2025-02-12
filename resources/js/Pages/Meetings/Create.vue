<template>
  <AppLayout title="Nouvelle réunion">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Nouvelle réunion
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <form @submit.prevent="submit" class="p-6 space-y-6">
            <div>
              <InputLabel for="title" value="Titre" />
              <TextInput
                id="title"
                v-model="form.title"
                type="text"
                class="mt-1 block w-full"
                required
              />
              <InputError :message="form.errors.title" class="mt-2" />
            </div>

            <div>
              <InputLabel for="description" value="Description" />
              <TextArea
                id="description"
                v-model="form.description"
                class="mt-1 block w-full"
                rows="4"
              />
              <InputError :message="form.errors.description" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="start_datetime" value="Date et heure de début" />
                <TextInput
                  id="start_datetime"
                  v-model="form.start_datetime"
                  type="datetime-local"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.start_datetime" class="mt-2" />
              </div>

              <div>
                <InputLabel for="end_datetime" value="Date et heure de fin" />
                <TextInput
                  id="end_datetime"
                  v-model="form.end_datetime"
                  type="datetime-local"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.end_datetime" class="mt-2" />
              </div>
            </div>

            <div>
              <InputLabel for="location" value="Lieu" />
              <TextInput
                id="location"
                v-model="form.location"
                type="text"
                class="mt-1 block w-full"
              />
              <InputError :message="form.errors.location" class="mt-2" />
            </div>

            <div>
              <InputLabel for="participants" value="Participants" />
              <MultiSelect
                v-model="form.participants"
                :options="users"
                class="mt-1"
                :close-on-select="false"
                :clear-on-select="false"
                placeholder="Sélectionnez les participants"
                label="name"
                track-by="id"
                :multiple="true"
              />
              <InputError :message="form.errors.participants" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
              <PrimaryButton class="ml-4" :disabled="form.processing">
                Créer la réunion
              </PrimaryButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import MultiSelect from '@/Components/MultiSelect.vue'

interface User {
  id: number
  name: string
  email: string
}

interface Props {
  users: User[]
}

const props = defineProps<Props>()

const form = useForm({
  title: '',
  description: '',
  start_datetime: '',
  end_datetime: '',
  location: '',
  participants: [] as number[]
})

const submit = () => {
  form.post(route('meetings.store'), {
    onSuccess: () => {
      form.reset()
    }
  })
}
</script>

<style scoped>
.bg-primary-600 {
  background-color: rgb(79, 70, 229)
}
.hover\:bg-primary-700:hover {
  background-color: rgb(67, 56, 202)
}
.focus\:ring-primary-500:focus {
  --tw-ring-color: rgb(99, 102, 241)
}
</style> 