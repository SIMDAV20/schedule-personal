<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

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
    days: Record<string, string>;
}>();

const form = useForm({
    day: Object.keys(props.days)[0] ?? "1",
    start_time: "",
    end_time: "",
    district_ids: [] as number[],
});

const submit = () =>
    form.post("/availability", { onSuccess: () => form.reset() });
const remove = (id: number) => useForm({}).delete(`/availability/${id}`);

const availabilitiesByDay = computed(() => {
    const map: Record<string, Availability[]> = {};
    Object.keys(props.days).forEach((key) => {
        map[key] = [];
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
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mi Horario
            </h2>
        </template>
        <div class="max-w-2xl mx-auto p-6 space-y-6">
            <h1 class="text-xl font-semibold">Mi disponibilidad</h1>

            <form
                @submit.prevent="submit"
                class="space-y-3 border rounded-lg p-4 bg-white"
            >
                <div class="flex gap-3">
                    <div>
                        <label class="text-xs text-gray-500">Día</label>
                        <select
                            v-model="form.day"
                            class="block border rounded px-3 py-2 capitalize text-sm"
                        >
                            <option
                                v-for="(name, id) in props.days"
                                :key="id"
                                :value="id"
                            >
                                {{ name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Desde</label>
                        <input
                            v-model="form.start_time"
                            type="time"
                            class="block border rounded px-3 py-2 text-sm"
                        />
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Hasta</label>
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
                        >Distritos cubiertos</label
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
                    Agregar horario
                </button>
            </form>

            <div class="bg-white px-4 py-6 rounded-lg space-y-3">
                <!-- Slots grouped by day -->
                <div
                    v-for="(name, id) in props.days"
                    :key="id"
                    class="flex gap-3"
                >
                    <span class="text-sm font-medium capitalize w-24 pt-1">{{
                        name
                    }}</span>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="slot in availabilitiesByDay[String(id)]"
                            :key="slot.id"
                            class="bg-indigo-50 text-indigo-900 text-xs rounded-lg px-3 py-1.5"
                        >
                            <div class="font-medium">
                                {{ slot.start_time.slice(0, 5) }} –
                                {{ slot.end_time.slice(0, 5) }}
                            </div>
                            <div class="text-indigo-500 mt-0.5">
                                {{
                                    slot.districts.map((d) => d.name).join(", ")
                                }}
                            </div>
                            <button
                                @click="remove(slot.id)"
                                class="text-indigo-300 hover:text-red-400 mt-1 text-xs"
                            >
                                Eliminar
                            </button>
                        </div>
                        <span
                            v-if="
                                !availabilitiesByDay[String(id)] ||
                                availabilitiesByDay[String(id)].length === 0
                            "
                            class="text-xs text-gray-300 pt-1"
                            >—</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
