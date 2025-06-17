<script setup lang="ts">
import { ref } from 'vue'; // Usunięto nieużywany import computed
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { LogOut, Settings } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const router = useRouter();

interface Props {
    user: User | null;
}
defineProps<Props>();

const isLoggingOut = ref(false);

async function handleLogout() {
    if (isLoggingOut.value) return;
    isLoggingOut.value = true;

    try {
        authStore.logout();

        router.push({ name: 'login' });

        if (navigator.sendBeacon) {
            const blob = new Blob([JSON.stringify({})], { type: 'application/json' });
            navigator.sendBeacon('/api/v1/logout', blob);
        } else {
            setTimeout(() => {
                axios.post('/api/v1/logout', {});
            }, 100);
        }
    } catch (error) {
        console.error("Błąd podczas wylogowywania:", error);
        router.push({ name: 'login' });
    } finally {
        isLoggingOut.value = false;
    }
}
</script>

<template>
    <template v-if="user">
        <DropdownMenuLabel class="p-0 font-normal">
            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                <UserInfo :user="user" :show-email="true" />
            </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuGroup>
            <DropdownMenuItem :as-child="true">
                <router-link :to="{ name: 'profile.settings' }" class="block w-full">
                    <Settings class="mr-2 h-4 w-4" />
                    Ustawienia
                </router-link>
            </DropdownMenuItem>
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        <DropdownMenuItem :as-child="true">
            <button @click="handleLogout" class="block w-full text-left" :disabled="isLoggingOut">
                <LogOut class="mr-2 h-4 w-4 inline" />
                {{ isLoggingOut ? 'Wylogowywanie...' : 'Wyloguj się' }}
            </button>
        </DropdownMenuItem>
    </template>
    <template v-else>
        <DropdownMenuItem :as-child="true">
            <router-link :to="{ name: 'login' }" class="block w-full">
                Log in
            </router-link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <router-link :to="{ name: 'register' }" class="block w-full">
                Register
            </router-link>
        </DropdownMenuItem>
    </template>
</template>
