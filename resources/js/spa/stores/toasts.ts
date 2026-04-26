import { defineStore } from 'pinia';

export type ToastVariant = 'success' | 'error' | 'info';

export interface Toast {
    id: number;
    message: string;
    variant: ToastVariant;
}

let nextId = 1;

export const useToastsStore = defineStore('spa-toasts', {
    state: () => ({
        toasts: [] as Toast[],
    }),
    actions: {
        push(message: string, variant: ToastVariant = 'info', durationMs = 4000): number {
            const id = nextId++;
            this.toasts.push({ id, message, variant });
            if (durationMs > 0) {
                setTimeout(() => this.dismiss(id), durationMs);
            }
            return id;
        },
        success(message: string, durationMs = 3000): number {
            return this.push(message, 'success', durationMs);
        },
        error(message: string, durationMs = 5000): number {
            return this.push(message, 'error', durationMs);
        },
        info(message: string, durationMs = 4000): number {
            return this.push(message, 'info', durationMs);
        },
        dismiss(id: number): void {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },
        clear(): void {
            this.toasts = [];
        },
    },
});
