<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({ districts: Array });

const form = useForm({ name: "" });
const submit = () =>
    form.post("/admin/districts", { onSuccess: () => form.reset() });
const remove = (id) => useForm({}).delete(`/admin/districts/${id}`);
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Distritos
            </h2>
        </template>

        <div class="max-w-lg mx-auto p-6 bg-white mt-4">
            <form @submit.prevent="submit" class="flex gap-2 mb-4">
                <input
                    v-model="form.name"
                    placeholder="Nombre del distrito"
                    class="flex-1 border rounded px-3 py-2 text-sm"
                />
                <button
                    type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded text-sm"
                >
                    Agregar
                </button>
            </form>

            <ul class="divide-y border rounded-lg">
                <li
                    v-for="d in districts"
                    :key="d.id"
                    class="flex items-center justify-between px-4 py-3"
                >
                    <div>
                        <span class="text-sm font-medium">{{ d.name }}</span>
                        <span class="text-xs text-gray-400 ml-2"
                            >{{ d.availabilities_count }} disponilidades</span
                        >
                    </div>
                    <button
                        @click="remove(d.id)"
                        class="text-xs text-red-400 hover:text-red-600"
                    >
                        Remove
                    </button>
                </li>
                <li
                    v-if="!districts.length"
                    class="px-4 py-3 text-sm text-gray-400"
                >
                    No districts yet.
                </li>
            </ul>
        </div>
    </AuthenticatedLayout>
</template>
