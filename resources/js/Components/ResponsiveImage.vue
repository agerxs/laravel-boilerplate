<script setup lang="ts">
import { ref, onMounted } from 'vue';

const props = defineProps<{
    src: string;
    alt: string;
    width?: number;
    height?: number;
}>();

const imgRef = ref<HTMLImageElement | null>(null);

onMounted(() => {
    if ('loading' in HTMLImageElement.prototype) {
        if (imgRef.value) {
            imgRef.value.loading = 'lazy';
        }
    }
});
</script>

<template>
    <img
        ref="imgRef"
        :src="src"
        :alt="alt"
        :width="width"
        :height="height"
        class="max-w-full h-auto"
        decoding="async"
    />
</template>

<style scoped>
img {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

img.loaded {
    opacity: 1;
}
</style> 