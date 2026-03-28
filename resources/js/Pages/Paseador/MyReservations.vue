<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

interface District {
    id: number;
    name: string;
}

interface Reservation {
    id: number;
    date: string;
    start_time: string;
    end_time: string;
    client_name: string;
    status: 'pending' | 'confirmed' | 'cancelled';
    status_name: string;
    district?: District;
    notes?: string;
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
            route("reservations"),
            { search: search.value, status: status.value },
            { preserveState: true, replace: true }
        );
    }, 300);
};

watch([search, status], () => {
    updateFilters();
});

const getStatusClass = (status: string) => {
    switch (status) {
        case "confirmed":
            return "bg-green-100 text-green-800 border-green-200";
        case "pending":
            return "bg-yellow-100 text-yellow-800 border-yellow-200";
        case "cancelled":
            return "bg-red-100 text-red-800 border-red-200";
        default:
            return "bg-gray-100 text-gray-800 border-gray-200";
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mis Reservas
            </h2>
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

            <!-- Tabla -->
            <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Hora
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Cliente
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Distrito
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Notas
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="res in reservations" :key="res.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                {{ res.date.slice(0, 10) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ res.start_time.slice(0, 5) }} – {{ res.end_time.slice(0, 5) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ res.client_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ res.district?.name || '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span 
                                    class="px-2.5 py-1 rounded-full text-xs font-semibold border"
                                    :class="getStatusClass(res.status)"
                                >
                                    {{ res.status_name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" :title="res.notes">
                                {{ res.notes || '—' }}
                            </td>
                        </tr>
                        <tr v-if="reservations.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-3xl">📅</span>
                                    <p>No se encontraron reservas con los filtros seleccionados.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>