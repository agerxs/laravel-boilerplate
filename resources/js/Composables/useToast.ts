import { ref } from 'vue';

interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error';
}

const toasts = ref<Toast[]>([]);
let nextId = 0;

export function useToast() {
    const addToast = (message: string, type: 'success' | 'error') => {
        const id = nextId++;
        toasts.value.push({ id, message, type });
        setTimeout(() => {
            toasts.value = toasts.value.filter(t => t.id !== id);
        }, 3000);
    };

    return {
        toasts,
        success: (message: string) => addToast(message, 'success'),
        error: (message: string) => addToast(message, 'error')
    };
} 