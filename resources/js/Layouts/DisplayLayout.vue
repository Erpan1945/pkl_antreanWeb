<script setup>
import { Head } from '@inertiajs/vue3';
import AppHeader from '@/Components/Header.vue';
import AppFooter from '@/Components/Footer.vue';

defineProps({
    title: String
});
</script>

<template>
    <Head :title="title" />

    <div class="h-screen w-screen bg-gray-50 flex flex-col font-sans select-none overflow-hidden">
        
        <AppHeader />

        <main class="flex-1 flex flex-col overflow-hidden relative bg-[#f8f9fa]">
            <slot />
        </main>

        <AppFooter />
        
    </div>
</template>