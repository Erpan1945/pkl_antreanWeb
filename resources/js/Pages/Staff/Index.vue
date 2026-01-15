<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ counters: Array });
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100">
        <h1 class="text-3xl font-bold mb-8">Login Petugas</h1>
        <div class="grid grid-cols-1 gap-4">
            <Link 
                v-for="counter in counters" 
                :key="counter.id"
                :href="route('staff.dashboard', counter.id)"
                class="px-8 py-4 bg-white rounded-lg shadow hover:bg-blue-50 text-xl font-bold text-center border hover:border-blue-500"
            >
                Masuk sebagai {{ counter.name }}
            </Link>
        </div>
    </div>
</template>