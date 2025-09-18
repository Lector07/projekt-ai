declare module '@inertiajs/vue3' {
    import { Component, Plugin } from 'vue';
    export function createInertiaApp(options: any): Promise<any>;
}

declare module '@inertiajs/vue3/server' {
    export default function createServer(callback: (page: any) => Promise<any>): void;
}

declare module 'ziggy-js' {
    export function route(name: string, params?: any, absolute?: boolean, config?: any): string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar?: string;
  email_verified_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface BreadcrumbItem {
  title: string;
  url?: string;
}
