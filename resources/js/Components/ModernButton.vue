<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'ghost';
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    disabled?: boolean;
    loading?: boolean;
    fullWidth?: boolean;
    rounded?: boolean;
    icon?: any;
    iconPosition?: 'left' | 'right';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    disabled: false,
    loading: false,
    fullWidth: false,
    rounded: false,
    iconPosition: 'left'
});

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

const buttonClasses = computed(() => {
    const baseClasses = 'inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    
    const sizeClasses = {
        xs: 'px-2.5 py-1.5 text-xs',
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-3 text-base',
        xl: 'px-8 py-4 text-lg'
    };
    
    const variantClasses = {
        primary: 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700 focus:ring-blue-500 shadow-lg hover:shadow-xl',
        secondary: 'bg-gradient-to-r from-slate-600 to-slate-700 text-white hover:from-slate-700 hover:to-slate-800 focus:ring-slate-500 shadow-lg hover:shadow-xl',
        success: 'bg-gradient-to-r from-emerald-600 to-green-600 text-white hover:from-emerald-700 hover:to-green-700 focus:ring-emerald-500 shadow-lg hover:shadow-xl',
        danger: 'bg-gradient-to-r from-red-600 to-pink-600 text-white hover:from-red-700 hover:to-pink-700 focus:ring-red-500 shadow-lg hover:shadow-xl',
        warning: 'bg-gradient-to-r from-amber-600 to-orange-600 text-white hover:from-amber-700 hover:to-orange-700 focus:ring-amber-500 shadow-lg hover:shadow-xl',
        info: 'bg-gradient-to-r from-cyan-600 to-blue-600 text-white hover:from-cyan-700 hover:to-blue-700 focus:ring-cyan-500 shadow-lg hover:shadow-xl',
        ghost: 'bg-transparent text-slate-700 hover:bg-slate-100 focus:ring-slate-500 border border-slate-300 hover:border-slate-400'
    };
    
    const widthClasses = props.fullWidth ? 'w-full' : '';
    const roundedClasses = props.rounded ? 'rounded-full' : 'rounded-xl';
    
    return [
        baseClasses,
        sizeClasses[props.size],
        variantClasses[props.variant],
        widthClasses,
        roundedClasses
    ].join(' ');
});

const iconClasses = computed(() => {
    const sizeClasses = {
        xs: 'h-3 w-3',
        sm: 'h-4 w-4',
        md: 'h-4 w-4',
        lg: 'h-5 w-5',
        xl: 'h-6 w-6'
    };
    
    return sizeClasses[props.size];
});

const handleClick = (event: MouseEvent) => {
    if (!props.disabled && !props.loading) {
        emit('click', event);
    }
};
</script>

<template>
    <button
        :class="buttonClasses"
        :disabled="disabled || loading"
        @click="handleClick"
    >
        <!-- Icône de chargement -->
        <svg
            v-if="loading"
            class="animate-spin -ml-1 mr-2"
            :class="iconClasses"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            ></circle>
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
        </svg>
        
        <!-- Icône personnalisée -->
        <component
            v-else-if="icon && iconPosition === 'left'"
            :is="icon"
            :class="['mr-2', iconClasses]"
            aria-hidden="true"
        />
        
        <!-- Contenu du bouton -->
        <span>
            <slot />
        </span>
        
        <!-- Icône à droite -->
        <component
            v-if="icon && iconPosition === 'right'"
            :is="icon"
            :class="['ml-2', iconClasses]"
            aria-hidden="true"
        />
    </button>
</template>

<style scoped>
/* Animations personnalisées */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Effet de brillance au survol */
button:hover::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

button:hover::before {
    left: 100%;
}
</style> 