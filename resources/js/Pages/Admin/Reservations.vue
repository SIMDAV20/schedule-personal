<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
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

const props = defineProps<{
    reservations: Reservation[];
    filters: {
        search?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search || "");
const status = ref(props.filters.status || "");

// Simple debounce
let timeout: ReturnType<typeof setTimeout>;
const updateFilters = () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            route("admin.reservations"),
            { search: search.value, status: status.value },
            { preserveState: true, replace: true }
        );
    }, 300);
};

watch([search, status], () => {
    updateFilters();
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reservas (Admin)</h2>
        </template>
        <div class="max-w-6xl mx-auto p-6 space-y-6">
            
            <!-- Filtros -->
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between bg-white p-4 rounded-lg border shadow-sm">
                <div class="relative w-full md:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 text-lg">
                        🔍
                    </div>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por cliente..."
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                </div>

                <div class="w-full md:w-56">
                    <select
                        v-model="status"
                        class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                    >
                        <option value="">Todos los estados</option>
                        <option value="confirmed">Confirmado</option>
                        <option value="pending">Pendiente</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 border-b">
                        <tr>
                            <th class="px-6 py-3 font-medium uppercase tracking-wider text-xs text-gray-500">Fecha</th>
                            <th class="px-6 py-3 font-medium uppercase tracking-wider text-xs text-gray-500">Hora</th>
                            <th class="px-6 py-3 font-medium uppercase tracking-wider text-xs text-gray-500">Cliente</th>
                            <th class="px-6 py-3 font-medium uppercase tracking-wider text-xs text-gray-500">Paseador</th>
                            <th class="px-6 py-3 font-medium uppercase tracking-wider text-xs text-gray-500">Distrito</th>
                            <th class="px-6 py-3 font-medium uppercase tracking-wider text-xs text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-gray-700 bg-white">
                        <tr v-for="r in reservations" :key="r.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium">{{ r.date.slice(0, 10) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ r.start_time.slice(0,5) }} – {{ r.end_time.slice(0,5) }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-900">{{ r.client_name }}</td>
                            <td class="px-6 py-4">{{ r.paseador?.name || '—' }}</td>
                            <td class="px-6 py-4">{{ r.district?.name || '—' }}</td>
                            <td class="px-6 py-4">
                                <span v-if="r.status === 'confirmed'" class="px-2.5 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold border border-green-200">Confirmada</span>
                                <span v-else-if="r.status === 'pending'" class="px-2.5 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold border border-yellow-200">Pendiente</span>
                                <span v-else class="px-2.5 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold border border-red-200">{{ r.status }}</span>
                            </td>
                        </tr>
                        <tr v-if="reservations.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No hay reservas registradas con estos filtros.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
