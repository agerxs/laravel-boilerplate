<template>
  <Head title="Modifier un taux de paiement" />

  <AppLayout title="Modifier un taux de paiement">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Modifier un taux de paiement
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <InputLabel value="Utilisateur" />
                <div class="mt-1 text-gray-700">
                  {{ paymentRate.user.name }} ({{ paymentRate.user.email }})
                </div>
              </div>

              <div>
                <InputLabel value="Rôle" />
                <div class="mt-1 text-gray-700">
                  {{ paymentRate.role }}
                </div>
              </div>

              <div>
                <InputLabel for="meeting_rate" value="Taux par réunion (FCFA)" />
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
                Mettre à jour
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
  paymentRate: Object
})

const form = useForm({
  meeting_rate: props.paymentRate.meeting_rate,
  notes: props.paymentRate.notes || '',
  is_active: props.paymentRate.is_active
})

const submit = () => {
  form.put(route('payment-rates.update', props.paymentRate.id))
}
</script> 