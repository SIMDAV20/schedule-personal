<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

interface District {
    id: number;
    name: string;
}

interface Availability {
    id: number;
    day: number;
    start_time: string;
    end_time: string;
    districts: District[];
}

const props = defineProps<{
    availabilities: Availability[];
    districts: District[];
}>();

const days = [
    { id: 1, name: "Lunes" },
    { id: 2, name: "Martes" },
    { id: 3, name: "Miércoles" },
    { id: 4, name: "Jueves" },
    { id: 5, name: "Viernes" },
    { id: 6, name: "Sábado" },
    { id: 7, name: "Domingo" },
];

const form = useForm({
    day: 1,
    start_time: "",
    end_time: "",
    district_ids: [] as number[],
});

const submit = () =>
    form.post("/availability", { onSuccess: () => form.reset() });
const remove = (id: number) => useForm({}).delete(`/availability/${id}`);

const availabilitiesByDay = computed(() => {
    const map: Record<string, Availability[]> = {};
    days.forEach((d) => {
        map[String(d.id)] = [];
    });
    props.availabilities.forEach((a) => {
        const dayStr = String(a.day);
        if (map[dayStr]) {
            map[dayStr].push(a);
        } else {
            map[dayStr] = [a];
        }
    });
    return map;
});
</script>

<template>
    <div class="max-w-2xl mx-auto p-6 space-y-6">
        <h1 class="text-xl font-semibold">My availability</h1>

        <form @submit.prevent="submit" class="space-y-3 border rounded-lg p-4">
            <div class="flex gap-3">
                <div>
                    <label class="text-xs text-gray-500">Day</label>
                    <select
                        v-model="form.day"
                        class="block border rounded px-3 py-2 capitalize text-sm"
                    >
                        <option v-for="d in days" :key="d.id" :value="d.id">
                            {{ d.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500">From</label>
                    <input
                        v-model="form.start_time"
                        type="time"
                        class="block border rounded px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="text-xs text-gray-500">To</label>
                    <input
                        v-model="form.end_time"
                        type="time"
                        class="block border rounded px-3 py-2 text-sm"
                    />
                </div>
            </div>

            <!-- District multi-select -->
            <div>
                <label class="text-xs text-gray-500 block mb-1"
                    >Districts covered</label
                >
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="d in districts"
                        :key="d.id"
                        class="flex items-center gap-1.5 text-sm cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            :value="d.id"
                            v-model="form.district_ids"
                        />
                        {{ d.name }}
                    </label>
                </div>
                <p
                    v-if="form.errors.district_ids"
                    class="text-red-500 text-xs mt-1"
                >
                    {{ form.errors.district_ids }}
                </p>
            </div>

            <button
                type="submit"
                class="bg-indigo-600 text-white px-4 py-2 rounded text-sm"
                :disabled="form.processing"
            >
                Add slot
            </button>
        </form>

        <!-- Slots grouped by day -->
        <div v-for="day in days" :key="day.id" class="flex gap-3">
            <span class="text-sm font-medium capitalize w-24 pt-1">{{
                day.name
            }}</span>
            <div class="flex flex-wrap gap-2">
                <div
                    v-for="slot in availabilitiesByDay[String(day.id)]"
                    :key="slot.id"
                    class="bg-indigo-50 text-indigo-900 text-xs rounded-lg px-3 py-1.5"
                >
                    <div class="font-medium">
                        {{ slot.start_time.slice(0, 5) }} –
                        {{ slot.end_time.slice(0, 5) }}
                    </div>
                    <div class="text-indigo-500 mt-0.5">
                        {{ slot.districts.map((d) => d.name).join(", ") }}
                    </div>
                    <button
                        @click="remove(slot.id)"
                        class="text-indigo-300 hover:text-red-400 mt-1 text-xs"
                    >
                        Remove
                    </button>
                </div>
                <span
                    v-if="availabilitiesByDay[String(day.id)].length === 0"
                    class="text-xs text-gray-300 pt-1"
                    >—</span
                >
            </div>
        </div>
    </div>
</template>
