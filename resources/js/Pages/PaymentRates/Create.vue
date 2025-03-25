<template>
  <Head title="Ajouter un taux de paiement" />

  <AppLayout title="Ajouter un taux de paiement">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Ajouter un taux de paiement
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <InputLabel for="user_id" value="Utilisateur" />
                <select
                  id="user_id"
                  v-model="form.user_id"
                  class="mt-1 block w-full rounded-md border-gray-300"
                  required
                >
                  <option value="">Sélectionner un utilisateur</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }} ({{ user.email }})
                  </option>
                </select>
                <InputError :message="form.errors.user_id" class="mt-2" />
              </div>

              <div>
                <InputLabel for="role" value="Rôle *" class="required" />
                <select
                  id="role"
                  v-model="form.role"
                  class="mt-1 block w-full rounded-md border-gray-300"
                  required
                >
                  <option value="">Sélectionner un rôle</option>
                  <option value="prefet">Préfet</option>
                  <option value="sous_prefet">Sous-préfet</option>
                  <option value="secretaire">Secrétaire</option>
                  <option value="representant">Représentant</option>
                </select>
                <InputError :message="form.errors.role" class="mt-2" />
              </div>

              <div>
                <InputLabel for="meeting_rate" value="Taux par réunion (FCFA) *" class="required" />
                <TextInput
                  id="meeting_rate"
                  v-model="form.meeting_rate"
                  type="number"
                  min="0"
                  step="0.01"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.meeting_rate" class="mt-2" />
              </div>

              <div>
                <InputLabel for="is_active" value="Statut" />
                <div class="mt-2">
                  <label class="inline-flex items-center">
                    <input
                      id="is_active"
                      v-model="form.is_active"
                      type="checkbox"
                      class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    />
                    <span class="ml-2 text-sm text-gray-600">Actif</span>
                  </label>
                </div>
                <InputError :message="form.errors.is_active" class="mt-2" />
              </div>
            </div>

            <div class="mb-6">
              <InputLabel for="notes" value="Notes" />
              <textarea
                id="notes"
                v-model="form.notes"
                class="mt-1 block w-full rounded-md border-gray-300"
                rows="3"
              ></textarea>
              <InputError :message="form.errors.notes" class="mt-2" />
            </div>

            <div class="flex justify-end">
              <Link
                :href="route('payment-rates.index')"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm font-medium mr-2"
              >
                Annuler
              </Link>
              <button
                type="submit"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium"
                :disabled="form.processing"
              >
                Enregistrer
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
  users: Array
})

const form = useForm({
  user_id: '',
  role: '',
  meeting_rate: '',
  notes: '',
  is_active: true
})

const submit = () => {
  form.post(route('payment-rates.store'))
}
</script> 