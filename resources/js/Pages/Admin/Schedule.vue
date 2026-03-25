<script setup lang="ts">
import { computed, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import FullCalendar from "@fullcalendar/vue3";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import type { CalendarOptions, EventInput, EventClickArg } from "@fullcalendar/core";
import Modal from "@/Components/Modal.vue";

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
    start_time: string;
    end_time: string;
    client_name?: string;
    district_id?: number;
    notes?: string | null;
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

// ---------------------------------------------------------------------------
// Color palette — one hue per paseador, stable across re-renders.
// Each entry: [background, border, text] for the "available" state.
// The "booked" state reuses the same hue but with lower opacity + dashed border.
// ---------------------------------------------------------------------------
const PALETTE: [string, string, string][] = [
    ["#dbeafe", "#3b82f6", "#1e40af"], // blue
    ["#fce7f3", "#ec4899", "#9d174d"], // pink
    ["#ede9fe", "#8b5cf6", "#5b21b6"], // violet
    ["#fef9c3", "#eab308", "#854d0e"], // yellow
    ["#cffafe", "#06b6d4", "#155e75"], // cyan
    ["#ffedd5", "#f97316", "#9a3412"], // orange
    ["#f0fdf4", "#22c55e", "#14532d"], // green
    ["#fdf4ff", "#d946ef", "#86198f"], // fuchsia
    ["#e0f2fe", "#0ea5e9", "#0c4a6e"], // sky
    ["#fef2f2", "#ef4444", "#991b1b"], // red
    ["#f0fdfa", "#14b8a6", "#134e4a"], // teal
    ["#fff7ed", "#fb923c", "#7c2d12"], // amber
];

/**
 * Returns a stable [bg, border, text] color tuple for a given paseador id.
 * Uses the position of the paseador in the sorted list so the assignment
 * is consistent regardless of the order they come from the server.
 */
const paseadorColorMap = computed<Map<number, [string, string, string]>>(() => {
    const map = new Map<number, [string, string, string]>();
    const sorted = [...props.paseadores].sort((a, b) => a.id - b.id);
    sorted.forEach((p, i) => {
        map.set(p.id, PALETTE[i % PALETTE.length]);
    });
    return map;
});

// Map Spanish day names to JS day-of-week numbers (0 = Sunday)
const dayNameToNumber: Record<string, number> = {
    Domingo: 0,
    Lunes: 1,
    Martes: 2,
    Miércoles: 3,
    Jueves: 4,
    Viernes: 5,
    Sábado: 6,
};

/**
 * FullCalendar needs real ISO dates. We anchor to the current week so that
 * the "timeGridWeek" view shows the correct column for each day name.
 */
const getDateForDayName = (dayName: string): string => {
    const today = new Date();
    const currentDay = today.getDay(); // 0 = Sunday
    const target = dayNameToNumber[dayName] ?? 0;
    const diff = target - currentDay;
    const date = new Date(today);
    date.setDate(today.getDate() + diff);
    return date.toISOString().slice(0, 10); // YYYY-MM-DD
};

// ---------------------------------------------------------------------------
// Merge availabilities del mismo paseador que coincidan en día y horario
// en un solo evento. Así un paseador con 08:00-10:00 en Santa Anita Y Surco
// aparece como un único bloque con ambos distritos listados.
// ---------------------------------------------------------------------------
interface MergedSlot {
    paseadorId: number;
    paseadorName: string;
    day: string;
    start: string;
    end: string;
    availabilityIds: number[];
    districts: District[];
    reservations: Reservation[]; // reservas dentro de este slot
}

const mergedSlots = computed<MergedSlot[]>(() => {
    const slots: MergedSlot[] = [];

    for (const paseador of props.paseadores) {
        const groups = new Map<string, Availability[]>();
        for (const av of paseador.availabilities) {
            const key = `${av.day_name}|${av.start_time}|${av.end_time}`;
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key)!.push(av);
        }

        const jsDayToString: Record<number, string> = {
            0: "Domingo", 1: "Lunes", 2: "Martes", 3: "Miércoles",
            4: "Jueves", 5: "Viernes", 6: "Sábado",
        };

        for (const [key, avs] of groups) {
            const [day, slotStart, slotEnd] = key.split("|");
            const districtMap = new Map<number, District>();
            for (const av of avs) {
                for (const d of av.districts) districtMap.set(d.id, d);
            }

            // Reservas de este paseador que caen dentro del horario de este slot
            const slotReservations = paseador.reservations.filter((r) => {
                const d = new Date(r.date);
                return (
                    jsDayToString[d.getDay()] === day &&
                    r.start_time.slice(0, 5) >= slotStart &&
                    r.end_time.slice(0, 5) <= slotEnd
                );
            });

            slots.push({
                paseadorId: paseador.id,
                paseadorName: paseador.name,
                day,
                start: slotStart,
                end: slotEnd,
                availabilityIds: avs.map((av) => av.id),
                districts: [...districtMap.values()],
                reservations: slotReservations,
            });
        }
    }

    return slots;
});

// Convert merged slots → FullCalendar EventInput array
const calendarEvents = computed<EventInput[]>(() => {
    return mergedSlots.value.map((slot) => {
        const date = getDateForDayName(slot.day);
        const [bg, border, text] =
            paseadorColorMap.value.get(slot.paseadorId) ?? PALETTE[0];
        const hasReservations = slot.reservations.length > 0;

        return {
            id: `${slot.paseadorId}-${slot.availabilityIds.join("-")}`,
            title: slot.paseadorName,
            start: `${date}T${slot.start}`,
            end: `${date}T${slot.end}`,
            backgroundColor: hasReservations ? `${bg}cc` : bg,
            borderColor: border,
            textColor: text,
            extendedProps: {
                paseadorId: slot.paseadorId,
                availabilityIds: slot.availabilityIds,
                districts: slot.districts,
                reservations: slot.reservations,
                colorBorder: border,
                accentColor: border,
                paseadorName: slot.paseadorName,
            },
        };
    });
});

const calendarOptions = computed<CalendarOptions>(() => ({
    plugins: [timeGridPlugin, interactionPlugin],
    initialView: "timeGridWeek",
    locale: "es",
    firstDay: 1,              // Week starts on Monday
    slotMinTime: "06:00:00",
    slotMaxTime: "23:00:00",
    slotDuration: "01:00:00",
    allDaySlot: false,
    headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "timeGridWeek,timeGridDay",
    },
    buttonText: {
        today: "Hoy",
        week: "Semana",
        day: "Día",
    },
    height: "auto",
    expandRows: true,
    nowIndicator: true,
    events: calendarEvents.value,
    eventCursor: "pointer",
    eventClick: openModal,
    eventDidMount(info) {
        // Tooltip nativo con distrito
        const districts = info.event.extendedProps.districts as string;
        if (districts) {
            info.el.setAttribute("title", districts);
        }
        // Dashed border for booked events
        if (info.event.extendedProps.booked) {
            info.el.style.borderStyle = "dashed";
            info.el.style.borderWidth = "2px";
        }
    },
}));

const filterDistrict = (id: string) => {
    router.get(
        "/admin/schedule",
        { district_id: id || "" },
        { preserveState: true },
    );
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const pad = (n: number) => String(n).padStart(2, "0");

/** Primera hora libre dentro del rango, saltando las ya reservadas */
const firstFreeHour = (slotStart: string, slotEnd: string, reservedHours: string[]): string => {
    let h = parseInt(slotStart.slice(0, 2));
    const endH = parseInt(slotEnd.slice(0, 2));
    while (h < endH) {
        const hStr = `${pad(h)}:00`;
        if (!reservedHours.includes(hStr)) return hStr;
        h++;
    }
    return slotStart;
};

/** Máximo de horas consecutivas libres a partir de una hora de inicio */
const maxConsecutiveFreeHours = (fromHour: string, slotEnd: string, reservedHours: string[]): number => {
    let h = parseInt(fromHour.slice(0, 2));
    const endH = parseInt(slotEnd.slice(0, 2));
    let count = 0;
    while (h < endH) {
        if (reservedHours.includes(`${pad(h)}:00`)) break;
        count++;
        h++;
    }
    return count;
};

// ---------------------------------------------------------------------------
// Modal 1 — Crear reserva (evento disponible)
// ---------------------------------------------------------------------------

interface CreateSlot {
    paseadorId: number;
    paseadorName: string;
    date: string;
    slotStart: string;        // inicio de la disponibilidad (rango completo)
    slotEnd: string;          // fin de la disponibilidad (rango completo)
    firstFree: string;        // primera hora libre (start_time calculado)
    maxHours: number;         // máximo de horas reservables consecutivas
    availableDistricts: District[];
    accentColor: string;
    reservedHours: string[];
}

const showCreateModal = ref(false);
const createSlot = ref<CreateSlot | null>(null);

const createForm = useForm({
    paseador_id: 0,
    date: "",
    start_time: "",
    end_time: "",   // calculado: start_time + hours
    district_id: "",
    client_name: "",
    notes: "",
    hours: 1,       // campo local para el selector
});

// ---------------------------------------------------------------------------
// Modal 2 — Ver detalle de reserva existente
// ---------------------------------------------------------------------------

interface ReservationDetail {
    id: number;
    paseadorName: string;
    date: string;
    startTime: string;
    endTime: string;
    clientName: string;
    districtName: string;
    notes: string | null;
    accentColor: string;
}

const showDetailModal = ref(false);
const reservationDetail = ref<ReservationDetail | null>(null);
const deleteProcessing = ref(false);

// ---------------------------------------------------------------------------
// eventClick — decide qué modal abrir
// ---------------------------------------------------------------------------

const openModal = (arg: EventClickArg) => {
    const { extendedProps } = arg.event;
    const paseador = props.paseadores.find(
        (p) => p.id === extendedProps.paseadorId,
    );
    if (!paseador) return;

    const start = arg.event.start!;
    const end = arg.event.end ?? arg.event.start!;
    const dateStr = start.toISOString().slice(0, 10);
    const slotStart = start.toTimeString().slice(0, 5);
    const slotEnd = end.toTimeString().slice(0, 5);
    const accentColor = paseadorColorMap.value.get(paseador.id)?.[1] ?? "#6366f1";

    const jsDayToString: Record<number, string> = {
        0: "Domingo", 1: "Lunes", 2: "Martes", 3: "Miércoles",
        4: "Jueves", 5: "Viernes", 6: "Sábado",
    };
    const dayName = jsDayToString[start.getDay()];
    const reservedHours = paseador.reservations
        .filter((r) => jsDayToString[new Date(r.date).getDay()] === dayName)
        .map((r) => r.start_time.slice(0, 5));

    const free = firstFreeHour(slotStart, slotEnd, reservedHours);
    const max = maxConsecutiveFreeHours(free, slotEnd, reservedHours);

    // Si no hay horas libres, no abrir modal de creación
    if (max === 0) return;

    const availableDistricts: District[] = extendedProps.districts ?? [];

    createSlot.value = {
        paseadorId: paseador.id,
        paseadorName: paseador.name,
        date: dateStr,
        slotStart,
        slotEnd,
        firstFree: free,
        maxHours: max,
        availableDistricts,
        accentColor,
        reservedHours,
    };

    createForm.reset();
    createForm.paseador_id = paseador.id;
    createForm.date = dateStr;
    createForm.start_time = free;
    createForm.hours = 1;
    createForm.end_time = `${pad(parseInt(free.slice(0, 2)) + 1)}:00`;
    createForm.district_id = availableDistricts.length === 1
        ? String(availableDistricts[0].id)
        : "";

    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createSlot.value = null;
    createForm.reset();
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    reservationDetail.value = null;
};

const submitReservation = () => {
    // Calcular end_time desde start_time + hours antes de enviar
    const startH = parseInt(createForm.start_time.slice(0, 2));
    createForm.end_time = `${pad(startH + createForm.hours)}:00`;

    createForm.post("/admin/reservations", {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
};

// Abrir modal de detalle desde chip de reserva (independiente de eventClick)
const openDetailModal = (reservation: Reservation, paseadorName: string, accentColor: string) => {
    const district = props.districts.find((d) => d.id === reservation.district_id);
    reservationDetail.value = {
        id: reservation.id,
        paseadorName,
        date: new Date(reservation.date).toISOString().slice(0, 10),
        startTime: reservation.start_time.slice(0, 5),
        endTime: reservation.end_time.slice(0, 5),
        clientName: reservation.client_name ?? "",
        districtName: district?.name ?? "",
        notes: reservation.notes ?? null,
        accentColor,
    };
    showDetailModal.value = true;
};

const deleteReservation = () => {
    if (!reservationDetail.value) return;
    deleteProcessing.value = true;
    router.delete(`/admin/reservations/${reservationDetail.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeDetailModal();
            deleteProcessing.value = false;
        },
        onError: () => { deleteProcessing.value = false; },
    });
};
</script>

<template>
    <div class="max-w-6xl mx-auto p-6 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-xl font-semibold">Schedule overview</h1>

            <!-- District filter -->
            <select
                :value="selectedDistrict"
                @change="filterDistrict(($event.target as HTMLSelectElement).value)"
                class="border rounded px-3 py-1.5 text-sm"
            >
                <option value="">All districts</option>
                <option v-for="d in districts" :key="d.id" :value="d.id">
                    {{ d.name }}
                </option>
            </select>
        </div>

        <!-- Calendar -->
        <div class="border rounded-lg overflow-hidden">
            <FullCalendar :options="calendarOptions">
                <!-- Custom event content: name + districts -->
                <template #eventContent="{ event }">
                    <div class="fc-event-inner px-1 py-0.5 leading-tight overflow-hidden h-full flex flex-col gap-0.5">
                        <!-- Nombre del paseador -->
                        <div class="font-semibold text-[11px] truncate">
                            {{ event.title }}
                        </div>
                        <!-- Chips de distritos -->
                        <div class="flex flex-wrap gap-0.5">
                            <span
                                v-for="d in (event.extendedProps.districts as District[])"
                                :key="d.id"
                                class="fc-district-chip truncate"
                                :style="{ borderColor: event.extendedProps.colorBorder }"
                            >
                                {{ d.name }}
                            </span>
                        </div>
                        <!-- Chips de reservas existentes -->
                        <div class="flex flex-wrap gap-0.5 mt-0.5">
                            <button
                                v-for="r in (event.extendedProps.reservations as Reservation[])"
                                :key="r.id"
                                type="button"
                                class="fc-reservation-chip"
                                @click.stop="openDetailModal(r, event.extendedProps.paseadorName, event.extendedProps.accentColor)"
                            >
                                📌 {{ r.start_time.slice(0,5) }}–{{ r.end_time.slice(0,5) }}
                            </button>
                        </div>
                    </div>
                </template>
            </FullCalendar>
        </div>

        <!-- Legend: one swatch per paseador + booked indicator -->
        <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-gray-500 pt-1">
            <span
                v-for="p in paseadores"
                :key="p.id"
                class="flex items-center gap-1.5"
            >
                <span
                    class="w-3 h-3 rounded inline-block border"
                    :style="{
                        backgroundColor: paseadorColorMap.get(p.id)?.[0],
                        borderColor: paseadorColorMap.get(p.id)?.[1],
                    }"
                ></span>
                {{ p.name }}
            </span>
            <span class="flex items-center gap-1.5 border-l pl-4 ml-2">
                <span class="w-3 h-3 rounded inline-block border-2 border-dashed border-gray-400 bg-gray-100"></span>
                Con reserva
            </span>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- Modal 1: Crear reserva                                       -->
    <!-- ============================================================ -->
    <Modal :show="showCreateModal" max-width="md" @close="closeCreateModal">
        <div v-if="createSlot" class="p-6">

            <!-- Header -->
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full shrink-0"
                        :style="{ backgroundColor: createSlot.accentColor }"></span>
                    <h2 class="text-base font-semibold text-gray-800">
                        Nueva reserva — {{ createSlot.paseadorName }}
                    </h2>
                </div>
                <button @click="closeCreateModal"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>

            <!-- Fecha (read-only) -->
            <div class="bg-gray-50 rounded-lg px-4 py-2.5 mb-5 text-sm text-gray-600 flex items-center gap-2">
                <span>📅</span>
                <span class="font-medium">{{ createSlot.date }}</span>
                <span class="text-gray-400 text-xs ml-1">
                    Disponibilidad: {{ createSlot.slotStart }} – {{ createSlot.slotEnd }}
                </span>
            </div>

            <!-- Fecha y hora de inicio (read-only) -->
            <div class="bg-gray-50 rounded-lg px-4 py-2.5 mb-5 text-sm text-gray-600 flex flex-wrap items-center gap-x-4 gap-y-1">
                <span>📅 <span class="font-medium">{{ createSlot.date }}</span></span>
                <span class="text-gray-400">|</span>
                <span>Disponibilidad: <span class="font-medium">{{ createSlot.slotStart }} – {{ createSlot.slotEnd }}</span></span>
                <span class="text-gray-400">|</span>
                <span>Inicio: <span class="font-medium">{{ createSlot.firstFree }}</span></span>
            </div>

            <form @submit.prevent="submitReservation" class="space-y-4">

                <!-- Número de horas -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Duración <span class="text-red-500">*</span>
                        <span class="text-gray-400 font-normal ml-1">(máx. {{ createSlot.maxHours }}h disponibles)</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center border rounded-lg overflow-hidden">
                            <button
                                type="button"
                                @click="createForm.hours = Math.max(1, createForm.hours - 1)"
                                class="px-3 py-2 text-gray-500 hover:bg-gray-100 text-lg leading-none font-medium"
                            >−</button>
                            <span class="px-4 py-2 text-sm font-semibold min-w-[3rem] text-center">
                                {{ createForm.hours }}h
                            </span>
                            <button
                                type="button"
                                @click="createForm.hours = Math.min(createSlot.maxHours, createForm.hours + 1)"
                                class="px-3 py-2 text-gray-500 hover:bg-gray-100 text-lg leading-none font-medium"
                            >+</button>
                        </div>
                        <!-- Preview del rango resultante -->
                        <span class="text-sm text-gray-500">
                            →
                            <span class="font-medium text-gray-800"
                                :style="{ color: createSlot.accentColor }">
                                {{ createSlot.firstFree }} – {{ pad(parseInt(createSlot.firstFree.slice(0,2)) + createForm.hours) }}:00
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Cliente -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Cliente / dueño del perro <span class="text-red-500">*</span>
                    </label>
                    <input v-model="createForm.client_name" type="text"
                        placeholder="Nombre completo"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        :class="{ 'border-red-400': createForm.errors.client_name }"
                        required />
                    <p v-if="createForm.errors.client_name" class="text-xs text-red-500 mt-1">
                        {{ createForm.errors.client_name }}
                    </p>
                </div>

                <!-- Distrito -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Distrito <span class="text-red-500">*</span>
                    </label>
                    <select v-model="createForm.district_id"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        :class="{ 'border-red-400': createForm.errors.district_id }"
                        required>
                        <option value="" disabled>Selecciona un distrito</option>
                        <option v-for="d in createSlot.availableDistricts" :key="d.id" :value="d.id">
                            {{ d.name }}
                        </option>
                    </select>
                    <p v-if="createForm.errors.district_id" class="text-xs text-red-500 mt-1">
                        {{ createForm.errors.district_id }}
                    </p>
                </div>

                <!-- Notas -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Notas adicionales
                    </label>
                    <textarea v-model="createForm.notes" rows="3"
                        placeholder="Instrucciones especiales, dirección exacta…"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none">
                    </textarea>
                </div>

                <!-- Acciones -->
                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" @click="closeCreateModal"
                        class="px-4 py-2 text-sm rounded-lg border text-gray-600 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        :disabled="createForm.processing"
                        class="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50">
                        {{ createForm.processing ? "Guardando…" : "Confirmar reserva" }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>

    <!-- ============================================================ -->
    <!-- Modal 2: Detalle de reserva existente                        -->
    <!-- ============================================================ -->
    <Modal :show="showDetailModal" max-width="sm" @close="closeDetailModal">
        <div v-if="reservationDetail" class="p-6">

            <!-- Header -->
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full shrink-0"
                        :style="{ backgroundColor: reservationDetail.accentColor }"></span>
                    <h2 class="text-base font-semibold text-gray-800">
                        Detalle de reserva
                    </h2>
                </div>
                <button @click="closeDetailModal"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            </div>

            <!-- Info -->
            <dl class="space-y-3 text-sm">
                <div class="flex gap-3">
                    <dt class="w-28 shrink-0 text-gray-400">Paseador</dt>
                    <dd class="font-medium text-gray-800">{{ reservationDetail.paseadorName }}</dd>
                </div>
                <div class="flex gap-3">
                    <dt class="w-28 shrink-0 text-gray-400">Cliente</dt>
                    <dd class="font-medium text-gray-800">{{ reservationDetail.clientName }}</dd>
                </div>
                <div class="flex gap-3">
                    <dt class="w-28 shrink-0 text-gray-400">Fecha</dt>
                    <dd class="text-gray-700">{{ reservationDetail.date }}</dd>
                </div>
                <div class="flex gap-3">
                    <dt class="w-28 shrink-0 text-gray-400">Hora</dt>
                    <dd class="text-gray-700">
                        {{ reservationDetail.startTime }} – {{ reservationDetail.endTime }}
                    </dd>
                </div>
                <div class="flex gap-3">
                    <dt class="w-28 shrink-0 text-gray-400">Distrito</dt>
                    <dd class="text-gray-700">{{ reservationDetail.districtName }}</dd>
                </div>
                <div v-if="reservationDetail.notes" class="flex gap-3">
                    <dt class="w-28 shrink-0 text-gray-400">Notas</dt>
                    <dd class="text-gray-700 whitespace-pre-wrap">{{ reservationDetail.notes }}</dd>
                </div>
            </dl>

            <!-- Acciones -->
            <div class="flex justify-between items-center mt-6 pt-4 border-t">
                <button
                    @click="deleteReservation"
                    :disabled="deleteProcessing"
                    class="px-4 py-2 text-sm rounded-lg border border-red-200 text-red-600 hover:bg-red-50 disabled:opacity-50 flex items-center gap-1.5"
                >
                    <span>🗑</span>
                    {{ deleteProcessing ? "Cancelando…" : "Cancelar reserva" }}
                </button>
                <button @click="closeDetailModal"
                    class="px-4 py-2 text-sm rounded-lg border text-gray-600 hover:bg-gray-50">
                    Cerrar
                </button>
            </div>
        </div>
    </Modal>
</template>

<style>
/* Thin scrollbar inside the calendar grid */
.fc-scroller {
    scrollbar-width: thin;
}
/* Tighten the header row height */
.fc .fc-col-header-cell-cushion {
    padding: 6px 4px;
    font-size: 0.75rem;
}
/* District chips inside events */
.fc-district-chip {
    display: inline-block;
    font-size: 9px;
    line-height: 1.4;
    padding: 0 4px;
    border-radius: 3px;
    border: 1px solid currentColor;
    opacity: 0.75;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
/* Reservation chips inside events — clickable */
.fc-reservation-chip {
    display: inline-flex;
    align-items: center;
    font-size: 9px;
    line-height: 1.4;
    padding: 1px 5px;
    border-radius: 3px;
    background: rgba(0,0,0,0.12);
    color: inherit;
    cursor: pointer;
    border: none;
    white-space: nowrap;
    transition: background 0.15s;
}
.fc-reservation-chip:hover {
    background: rgba(0,0,0,0.22);
}
</style>