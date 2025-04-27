<script setup lang="ts">
import { ref, computed } from 'vue';
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { LogOut, Settings } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
import axios from 'axios';
// Załóżmy, że masz store i importujesz go:
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
        await axios.post('/api/v1/logout');
        // authStore.logout(); // Aktualizuj stan globalny
        router.push({ name: 'login' });
    } catch (error) {
        console.error("Błąd podczas wylogowywania:", error);
        alert('Wystąpił błąd podczas wylogowywania.');
    } finally {
        isLoggingOut.value = false;
    }
}

// const isLoggedIn = computed(() => authStore.isLoggedIn);
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
                    Settings
                </router-link>
            </DropdownMenuItem>
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        <DropdownMenuItem :as-child="true">
            <button @click="handleLogout" class="block w-full text-left" :disabled="isLoggingOut">
                <LogOut class="mr-2 h-4 w-4 inline" />
                {{ isLoggingOut ? 'Logging out...' : 'Log out' }}
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
