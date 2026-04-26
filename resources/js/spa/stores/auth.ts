import { defineStore } from 'pinia';
import { api } from '@/spa/http/apiClient';

export interface User {
    id: number;
    name: string;
    username: string;
    email: string;
    avatar: string | null;
    bio: string | null;
    locale: string;
    onboarded: boolean;
}

interface BootstrapPayload {
    user: User | null;
    token: string | null;
    locale: string;
    api_base: string;
    social_auth_urls: { google: string; apple: string };
}

export const useAuthStore = defineStore('spa-auth', {
    state: () => ({
        user: null as User | null,
        token: null as string | null,
        apiBase: '' as string,
        socialAuthUrls: { google: '', apple: '' },
    }),
    actions: {
        async bootstrap(): Promise<BootstrapPayload> {
            const data = await api.get<BootstrapPayload>('/api/spa/bootstrap');
            this.user = data.user;
            this.token = data.token;
            this.apiBase = data.api_base;
            this.socialAuthUrls = data.social_auth_urls;
            return data;
        },
        async login(email: string, password: string): Promise<{ redirect_to: string }> {
            const data = await api.post<{ user: User; token: string; redirect_to: string }>(
                '/api/spa/auth/login',
                { email, password },
            );
            this.user = data.user;
            this.token = data.token;
            return { redirect_to: data.redirect_to };
        },
        async register(payload: {
            name: string;
            username: string;
            email: string;
            password: string;
            terms_accepted: boolean;
        }): Promise<{ redirect_to: string }> {
            const data = await api.post<{ user: User; token: string; redirect_to: string }>(
                '/api/spa/auth/register',
                payload,
            );
            this.user = data.user;
            this.token = data.token;
            return { redirect_to: data.redirect_to };
        },
        async logout(): Promise<void> {
            try {
                await api.post('/api/spa/auth/logout');
            } finally {
                this.clear();
            }
        },
        clear(): void {
            this.user = null;
            this.token = null;
        },
    },
});
