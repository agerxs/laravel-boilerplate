<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    title?: string;
    subtitle?: string;
    variant?: 'default' | 'gradient' | 'elevated' | 'bordered';
    color?: 'blue' | 'green' | 'purple' | 'orange' | 'slate' | 'emerald' | 'indigo';
    size?: 'sm' | 'md' | 'lg';
    hover?: boolean;
    clickable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    color: 'slate',
    size: 'md',
    hover: true,
    clickable: false
});

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

const cardClasses = computed(() => {
    const baseClasses = 'relative overflow-hidden transition-all duration-300';
    
    const sizeClasses = {
        sm: 'p-4',
        md: 'p-6',
        lg: 'p-8'
    };
    
    const variantClasses = {
        default: 'bg-white border border-slate-200/50 shadow-lg',
        gradient: `bg-gradient-to-br from-${props.color}-500 to-${props.color === 'slate' ? 'slate-600' : props.color}-600 text-white`,
        elevated: 'bg-white border border-slate-200/50 shadow-xl',
        bordered: 'bg-white border-2 border-slate-200'
    };
    
    const hoverClasses = props.hover ? 'hover:shadow-2xl hover:scale-105' : '';
    const clickableClasses = props.clickable ? 'cursor-pointer active:scale-95' : '';
    
    return [
        baseClasses,
        sizeClasses[props.size],
        variantClasses[props.variant],
        hoverClasses,
        clickableClasses
    ].join(' ');
});

const titleClasses = computed(() => {
    if (props.variant === 'gradient') {
        return 'text-white';
    }
    return 'text-slate-800';
});

const subtitleClasses = computed(() => {
    if (props.variant === 'gradient') {
        return 'text-slate-100';
    }
    return 'text-slate-600';
});

const handleClick = (event: MouseEvent) => {
    if (props.clickable) {
        emit('click', event);
    }
};
</script>

<template>
    <div 
        :class="cardClasses"
        @click="handleClick"
    >
        <!-- Effet de brillance -->
        <div 
            v-if="variant === 'gradient'"
            class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10"
        ></div>
        
        <!-- Contenu principal -->
        <div class="relative">
            <slot name="header">
                <div v-if="title || subtitle" class="mb-4">
                    <h3 v-if="title" :class="['text-lg font-semibold', titleClasses]">
                        {{ title }}
                    </h3>
                    <p v-if="subtitle" :class="['text-sm mt-1', subtitleClasses]">
                        {{ subtitle }}
                    </p>
                </div>
            </slot>
            
            <!-- Contenu par défaut -->
            <slot />
        </div>
        
        <!-- Effet de brillance au survol -->
        <div 
            v-if="hover && variant === 'gradient'"
            class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
        ></div>
    </div>
</template>

<style scoped>
/* Animations personnalisées */
.card-enter-active,
.card-leave-active {
    transition: all 0.3s ease;
}

.card-enter-from,
.card-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}

/* Effet de brillance */
@keyframes shine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.shine-effect {
    animation: shine 2s infinite;
}
</style> 