<template>
    <AppLayout :title="locality.name">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ locality.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Détails de la localité</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Informations générales</h4>
                            <dl class="mt-2 space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Code</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ locality.code }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ locality.description || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ locality.type.name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Parent</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ locality.parent?.name || '-' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Sous-localités</h4>
                            <div class="mt-2">
                                <div v-if="locality.children.length === 0" class="text-sm text-gray-500">
                                    Aucune sous-localité
                                </div>
                                <ul v-else class="divide-y divide-gray-200">
                                    <li v-for="child in locality.children" :key="child.id" class="py-2">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ child.name }}</p>
                                                <p class="text-sm text-gray-500">{{ child.type.name }}</p>
                                            </div>
                                            <Link :href="route('localities.show', child.id)" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                                Voir
                                            </Link>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    locality: {
        type: Object,
        required: true
    }
});
</script> 