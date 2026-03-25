<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface Reservation {
    id: number;
    date: string;
    start_time: string;
    end_time: string;
    client_name?: string;
    status: string;
    paseador?: { name: string };
    district?: { name: string };
}

defineProps<{
    reservations: Reservation[];
}>();
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reservas</h2>
        </template>
        <div class="max-w-6xl mx-auto p-6">
            <div class="bg-white rounded-lg border overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 border-b">
                        <tr>
                            <th class="px-4 py-3 font-medium">Fecha</th>
                            <th class="px-4 py-3 font-medium">Hora</th>
                            <th class="px-4 py-3 font-medium">Cliente</th>
                            <th class="px-4 py-3 font-medium">Paseador</th>
                            <th class="px-4 py-3 font-medium">Distrito</th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-gray-700">
                        <tr v-for="r in reservations" :key="r.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">{{ r.date.slice(0, 10) }}</td>
                            <td class="px-4 py-3 truncate">{{ r.start_time.slice(0,5) }} – {{ r.end_time.slice(0,5) }}</td>
                            <td class="px-4 py-3">{{ r.client_name }}</td>
                            <td class="px-4 py-3">{{ r.paseador?.name || '—' }}</td>
                            <td class="px-4 py-3">{{ r.district?.name || '—' }}</td>
                            <td class="px-4 py-3">
                                <span v-if="r.status === 'confirmed'" class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium border border-green-200">Confirmada</span>
                                <span v-else-if="r.status === 'pending'" class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium border border-yellow-200">Pendiente</span>
                                <span v-else class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium border border-gray-200">{{ r.status }}</span>
                            </td>
                        </tr>
                        <tr v-if="reservations.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No hay reservas registradas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>