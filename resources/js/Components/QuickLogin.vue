<template>
    <div class="fixed bottom-4 right-4 z-50">
        <div class="bg-white rounded-lg shadow-lg p-4">
            <h3 class="text-lg font-medium mb-2">Connexion rapide</h3>
            <div class="space-y-2">
                <button
                    v-for="(user, index) in quickUsers"
                    :key="index"
                    @click="loginAs(user)"
                    class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                >
                    {{ user.name }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const quickUsers = ref([
    {
        name: 'Trésorier',
        email: 'tresorier@example.com',
        password: 'password'
    },
    {
        name: 'Président',
        email: 'president@example.com',
        password: 'password'
    },
    {
        name: 'Secrétaire',
        email: 'secretaire@example.com',
        password: 'password'
    }
]);

const loginAs = (user) => {
    router.post(route('login'), {
        email: user.email,
        password: user.password,
        remember: true
    });
};
</script> 