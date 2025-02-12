<script setup lang="ts">
defineProps<{
    links: {
        url?: string;
        label: string;
        active: boolean;
    }[];
}>();
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap -mb-1">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="mr-1 mb-1 px-4 py-2 text-sm text-gray-500 border rounded"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-gray-50"
                :class="{
                    'bg-indigo-600 text-white hover:bg-indigo-700': link.active,
                    'text-gray-700': !link.active,
                }"
                v-html="link.label"
            />
        </template>
    </div>
</template>

<script lang="ts">
import { Link } from '@inertiajs/vue3';

export default {
    components: {
        Link,
    },
};
</script> 