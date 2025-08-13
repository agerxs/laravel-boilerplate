<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

interface Props {
    href: string;
    icon: any;
    color: string;
    gradient: string;
    isActive?: boolean;
    size?: 'sm' | 'md' | 'lg';
    showLabel?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isActive: false,
    size: 'md',
    showLabel: true
});

const sizeClasses = computed(() => {
    const classes = {
        sm: 'px-3 py-2 text-xs',
        md: 'px-4 py-3 text-sm',
        lg: 'px-6 py-4 text-base'
    };
    return classes[props.size];
});

const iconSizeClasses = computed(() => {
    const classes = {
        sm: 'h-4 w-4',
        md: 'h-5 w-5',
        lg: 'h-6 w-6'
    };
    return classes[props.size];
});

const activeClasses = computed(() => {
    if (props.isActive) {
        return 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 border border-blue-200 shadow-md';
    }
    return 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 hover:shadow-md';
});

const iconColorClasses = computed(() => {
    if (props.isActive) {
        return 'text-blue-500';
    }
    return props.color;
});
</script>

<template>
    <Link
        :href="href"
        :class="[
            'group flex items-center font-medium rounded-xl transition-all duration-300 nav-link',
            sizeClasses,
            activeClasses
        ]"
    >
        <div class="relative mr-3 flex-shrink-0">
            <!-- Icône principale -->
            <component
                :is="icon"
                :class="[
                    iconSizeClasses,
                    'transition-all duration-200',
                    iconColorClasses
                ]"
                aria-hidden="true"
            />
            
            <!-- Effet de brillance au survol (seulement si pas actif) -->
            <div 
                v-if="!isActive"
                class="absolute inset-0 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition-all duration-300 rounded-full blur-sm scale-110"
                :class="gradient"
            ></div>
            
            <!-- Particules flottantes -->
            <div 
                v-if="!isActive"
                class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"
            >
                <div 
                    class="absolute w-1 h-1 bg-white/60 rounded-full animate-pulse"
                    :class="gradient.replace('from-', 'bg-').replace('to-', '')"
                    style="top: 20%; left: 20%; animation-delay: 0s;"
                ></div>
                <div 
                    class="absolute w-1 h-1 bg-white/60 rounded-full animate-pulse"
                    :class="gradient.replace('from-', 'bg-').replace('to-', '')"
                    style="top: 60%; right: 20%; animation-delay: 0.5s;"
                ></div>
            </div>
        </div>
        
        <!-- Label (optionnel) -->
        <span v-if="showLabel" class="font-medium">
            <slot />
        </span>
        
        <!-- Indicateur d'état actif -->
        <div 
            v-if="isActive"
            class="ml-auto w-2 h-2 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full animate-pulse"
        ></div>
    </Link>
</template>

<style scoped>
/* Animation de l'effet de brillance */
@keyframes icon-glow {
    0% {
        opacity: 0;
        transform: scale(0.8);
    }
    50% {
        opacity: 1;
        transform: scale(1.1);
    }
    100% {
        opacity: 0;
        transform: scale(1.2);
    }
}

/* Animation des particules */
@keyframes particle-float {
    0% {
        transform: translateY(0) scale(1);
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        transform: translateY(-10px) scale(0.8);
        opacity: 0;
    }
}

/* Effet de survol sur l'élément de navigation */
.nav-link:hover {
    transform: translateX(4px);
}

/* Animation de l'icône au survol */
.group:hover .relative .absolute {
    animation: icon-glow 0.6s ease-in-out;
}

/* Animation des particules */
.animate-pulse {
    animation: particle-float 2s infinite ease-in-out;
}

/* Transition fluide pour tous les éléments */
.nav-link * {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style> 