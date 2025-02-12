<script setup lang="ts">
import { ref, onMounted } from 'vue';

const props = defineProps<{
    message: string;
    type?: 'success' | 'error';
    duration?: number;
}>();

const visible = ref(true);

onMounted(() => {
    setTimeout(() => {
        visible.value = false;
    }, props.duration || 3000);
});
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-show="visible"
            class="fixed bottom-4 right-4 z-50 rounded-lg p-4 shadow-lg"
            :class="{
                'bg-green-50 text-green-800': type === 'success',
                'bg-red-50 text-red-800': type === 'error'
            }"
        >
            {{ message }}
        </div>
    </Transition>
</template> 