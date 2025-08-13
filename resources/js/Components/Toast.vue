<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { CheckCircleIcon, XCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
    message: string;
    type?: 'success' | 'error' | 'warning' | 'info';
    duration?: number;
}>();

const visible = ref(true);

const closeToast = () => {
    visible.value = false;
};

onMounted(() => {
    setTimeout(() => {
        visible.value = false;
    }, props.duration || 5000);
});

const getToastConfig = (type: string) => {
    const configs = {
        success: {
            icon: CheckCircleIcon,
            bgColor: 'bg-gradient-to-r from-emerald-500 to-teal-500',
            textColor: 'text-white',
            iconColor: 'text-emerald-100',
            borderColor: 'border-emerald-200',
            shadowColor: 'shadow-emerald-500/25'
        },
        error: {
            icon: XCircleIcon,
            bgColor: 'bg-gradient-to-r from-red-500 to-pink-500',
            textColor: 'text-white',
            iconColor: 'text-red-100',
            borderColor: 'border-red-200',
            shadowColor: 'shadow-red-500/25'
        },
        warning: {
            icon: ExclamationTriangleIcon,
            bgColor: 'bg-gradient-to-r from-amber-500 to-orange-500',
            textColor: 'text-white',
            iconColor: 'text-amber-100',
            borderColor: 'border-amber-200',
            shadowColor: 'shadow-amber-500/25'
        },
        info: {
            icon: InformationCircleIcon,
            bgColor: 'bg-gradient-to-r from-blue-500 to-indigo-500',
            textColor: 'text-white',
            iconColor: 'text-blue-100',
            borderColor: 'border-blue-200',
            shadowColor: 'shadow-blue-500/25'
        }
    };
    
    return configs[type] || configs.info;
};

const toastConfig = getToastConfig(props.type || 'info');
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition-all"
        enter-from-class="translate-y-2 opacity-0 scale-95"
        enter-to-class="translate-y-0 opacity-100 scale-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-show="visible"
            class="relative overflow-hidden rounded-2xl p-4 shadow-xl border backdrop-blur-sm min-w-[320px] max-w-md"
            :class="[
                toastConfig.bgColor,
                toastConfig.textColor,
                toastConfig.borderColor,
                toastConfig.shadowColor
            ]"
        >
            <!-- Effet de brillance -->
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10"></div>
            
            <!-- Contenu du toast -->
            <div class="relative flex items-start">
                <div class="flex-shrink-0">
                    <component 
                        :is="toastConfig.icon" 
                        class="h-6 w-6" 
                        :class="toastConfig.iconColor"
                        aria-hidden="true" 
                    />
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">{{ message }}</p>
                </div>
                <div class="ml-4 flex flex-shrink-0">
                    <button
                        @click="closeToast"
                        class="inline-flex rounded-lg p-1.5 text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50"
                    >
                        <span class="sr-only">Fermer</span>
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
            
            <!-- Barre de progression -->
            <div class="absolute bottom-0 left-0 h-1 bg-white/20">
                <div 
                    class="h-full bg-white/40 transition-all duration-300 ease-linear"
                    :style="{ width: '100%' }"
                ></div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* Animation de la barre de progression */
@keyframes progress {
    from { width: 100%; }
    to { width: 0%; }
}

.progress-bar {
    animation: progress linear forwards;
    animation-duration: v-bind('(props.duration || 5000) + "ms"');
}
</style> 