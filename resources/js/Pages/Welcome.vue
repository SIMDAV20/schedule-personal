<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion: string;
    phpVersion: string;
}>();

</script>

<template>
    <Head title="Welcome" />
    
    <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
        simon
    </div>

</template>
