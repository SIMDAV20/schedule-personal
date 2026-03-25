<script setup lang="ts">
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

interface District {
    id: number;
    name: string;
    slug: string;
}

interface Availability {
    id: number;
    day_name: string;
    start_time: string;
    end_time: string;
    districts: District[];
}

interface Reservation {
    id: number;
    date: string;
}

interface Paseador {
    id: number;
    name: string;
    availabilities: Availability[];
    reservations: Reservation[];
}

interface Props {
    paseadores: Paseador[];
    districts: District[];
    selectedDistrict: number | null;
    days: string[];
}

const props = defineProps<Props>();

const view = ref<"grid" | "list">("grid");

// 6 → 23
const hours = Array.from({ length: 18 }, (_, i) => i + 6);

const pad = (n: number) => String(n).padStart(2, "0");
const label = (h: number) => `${pad(h)}:00`;

/** Paseadores that have a slot covering the given hour on the given day */
const availableAt = (hour: number, day: string): Paseador[] => {
    return props.paseadores.filter((p) =>
        p.availabilities.some((a) => {
            if (a.day_name !== day) return false;
            const start = parseInt(a.start_time.slice(0, 2), 10);
            const end = parseInt(a.end_time.slice(0, 2), 10);
            return hour >= start && hour < end;
        }),
    );
};

/** Whether the paseador has any reservation this week on the given day */
const isBooked = (paseador: Paseador, day: string): boolean => {
    const jsDayToString: Record<number, string> = {
        0: "Domingo",
        1: "Lunes",
        2: "Martes",
        3: "Miércoles",
        4: "Jueves",
        5: "Viernes",
        6: "Sábado",
    };
    return (
        paseador.reservations?.some(
            (r) => jsDayToString[new Date(r.date).getDay()] === day,
        ) ?? false
    );
};

const filterDistrict = (id: string) => {
    router.get(
        "/admin/schedule",
        { district_id: id || "" },
        { preserveState: true },
    );
};

// Keep the list-view helper unchanged
const slots = (paseador: Paseador, day: string) =>
    paseador.availabilities.filter((a) => a.day_name === day);
</script>

<template>
    <div class="max-w-6xl mx-auto p-6 space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-xl font-semibold">Schedule overview</h1>

            <div class="flex items-center gap-3">
                <!-- District filter -->
                <select
                    :value="selectedDistrict"
                    @change="filterDistrict($event.target.value)"
                    class="border rounded px-3 py-1.5 text-sm"
                >
                    <option value="">All districts</option>
                    <option v-for="d in districts" :key="d.id" :value="d.id">
                        {{ d.name }}
                    </option>
                </select>

                <!-- View toggle -->
                <div class="flex gap-1">
                    <button
                        @click="view = 'grid'"
                        :class="
                            view === 'grid'
                                ? 'bg-indigo-600 text-white'
                                : 'border text-gray-600'
                        "
                        class="px-3 py-1.5 rounded text-sm"
                    >
                        Grid
                    </button>
                    <button
                        @click="view = 'list'"
                        :class="
                            view === 'list'
                                ? 'bg-indigo-600 text-white'
                                : 'border text-gray-600'
                        "
                        class="px-3 py-1.5 rounded text-sm"
                    >
                        List
                    </button>
                </div>
            </div>
        </div>

        <!-- GRID VIEW: rows = 24 hours, columns = days -->
        <div v-if="view === 'grid'" class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th
                            class="text-left p-2 border-b font-medium text-gray-400 w-16 text-xs uppercase tracking-wide"
                        >
                            Hora
                        </th>
                        <th
                            v-for="(day, index) in days"
                            :key="`${day}-${index}`"
                            class="p-2 border-b font-medium text-gray-500 text-center text-xs uppercase tracking-wide min-w-[110px]"
                        >
                            {{ day }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="hour in hours"
                        :key="hour"
                        class="hover:bg-gray-50/60"
                    >
                        <!-- Hour label -->
                        <td
                            class="p-2 border-b text-xs font-mono text-gray-400 whitespace-nowrap align-middle w-16"
                        >
                            {{ label(hour) }}
                        </td>

                        <!-- One cell per day -->
                        <td
                            v-for="day in days"
                            :key="day"
                            class="p-2 border-b align-middle"
                        >
                            <div
                                v-if="availableAt(hour, day).length"
                                class="flex flex-wrap gap-1"
                            >
                                <span
                                    v-for="p in availableAt(hour, day)"
                                    :key="p.id"
                                    :class="
                                        isBooked(p, day)
                                            ? 'bg-orange-100 text-orange-700 ring-1 ring-orange-200'
                                            : 'bg-green-100 text-green-800 ring-1 ring-green-200'
                                    "
                                    class="text-xs rounded px-2 py-0.5 font-medium whitespace-nowrap"
                                >
                                    {{ p.name }}
                                </span>
                            </div>
                            <span v-else class="text-gray-200 text-xs select-none">—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- LIST VIEW -->
        <div v-else class="space-y-3">
            <div
                v-for="p in paseadores"
                :key="p.id"
                class="border rounded-lg p-4"
            >
                <h2 class="font-semibold mb-3">{{ p.name }}</h2>
                <div class="grid grid-cols-7 gap-2">
                    <div v-for="(day, index) in days" :key="`${day}-${index}-second`">
                        <div
                            class="text-xs text-gray-400 font-medium uppercase mb-1"
                        >
                            {{ day }}
                        </div>
                        <div v-if="slots(p, day).length" class="space-y-1">
                            <div
                                v-for="slot in slots(p, day)"
                                :key="slot.id"
                                class="text-xs bg-indigo-50 text-indigo-800 rounded p-1.5"
                            >
                                <div class="font-medium">
                                    {{ slot.start_time.slice(0, 5) }}–{{
                                        slot.end_time.slice(0, 5)
                                    }}
                                </div>
                                <div
                                    class="text-indigo-400 mt-0.5 leading-tight"
                                >
                                    {{
                                        slot.districts
                                            .map((d) => d.name)
                                            .join(" · ")
                                    }}
                                </div>
                            </div>
                        </div>
                        <span v-else class="text-xs text-gray-200">—</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex gap-4 text-xs text-gray-500 pt-2">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-green-200 inline-block"></span>
                Available
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-orange-200 inline-block"></span>
                Has reservation this week
            </span>
        </div>
    </div>
</template>
