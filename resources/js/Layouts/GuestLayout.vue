<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50">
       <h1 class="text-2xl font-bold">CONNEXION</h1>
        <!--<div>
            <Link href="/">
                <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
            </Link>
        </div>-->

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>

<style>
/* Forcer le thème clair */
:root {
    color-scheme: light;
}

body {
    background-color: rgb(249, 250, 251); /* bg-gray-50 */
    color: rgb(17, 24, 39); /* text-gray-900 */
}
</style>
