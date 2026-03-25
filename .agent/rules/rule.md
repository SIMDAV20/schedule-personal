---
trigger: always_on
---

You are an expert in Laravel 12, Inertia.js, Vue 3, and TypeScript.

## Stack
- Backend: Laravel 12 (PHP 8.3+)
- Frontend: Vue 3 with <script setup> and TypeScript
- Bridge: Inertia.js v2
- Styling: Tailwind CSS v4
- DB: MySQL

## Laravel conventions
- Use Route Model Binding wherever possible
- Controllers are thin: validate, delegate to model/service, return Inertia response
- Validation always in FormRequest classes, never inline in controllers
- Policies for authorization, never manual auth checks in controllers
- Enums (PHP 8.1+) for fixed value sets like roles and days
- Always use $fillable on models, never $guarded
- Migrations must be reversible (implement down())
- Use withCount / with for eager loading, never lazy load in loops

## Inertia conventions
- Always type Inertia page props with TypeScript interfaces
- Use useForm() from @inertiajs/vue3 for all forms, never axios directly
- Use router.get() for filter/search navigation with preserveState: true
- Pass only the data the page needs — no over-fetching in controllers

## Vue + TypeScript conventions
- All components use <script setup lang="ts">
- Define props with defineProps<{}>() using TypeScript interfaces, never runtime declarations
- Use defineEmits<{}>() with TypeScript signatures
- Composables live in resources/js/composables/ and are prefixed with use
- No logic in templates — extract to computed or methods
- Never use any type — always be explicit

## Project domain rules
- Users have a role enum: 'admin' | 'paseador'
- Role middleware alias is 'role' — use as: Route::middleware('role:admin')
- Availabilities belong to a User and link to many Districts via availability_district pivot
- One availability row = one day + time range + N districts (never duplicate rows per district)
- Reservations belong to a paseador (User) and a District
- Admin schedule view supports filtering by district_id via query param
- Paseador schedule view shows their own availabilities grouped by day
- Reservations allow null `paseador_id` (reservas sin paseador).
- Reservation statuses: `pending` (sin paseador asignado), `confirmed` (paseador asignado), `cancelled` (cancelada soft, no se elimina).
- Flujo A (desde el calendario / paseador conocido): Click en evento disponible → Modal crear (fecha+hora pre-llenada, paseador fijo) → Al guardar: status = `confirmed`.
- Flujo B (sin paseador): Botón "Nueva reserva" en el header → Modal crear (admin llena todo manualmente) → Al guardar: status = `pending`, `paseador_id` = null.
- Asignación posterior: Tabla de pendientes → "Asignar paseador" → Select con TODOS los paseadores (sin filtro) → Advertencia visual si el paseador no tiene disponibilidad en esa hora → Al guardar: status = `confirmed`.

## File structure
- Pages live in resources/js/Pages/{Role}/{PageName}.vue
- Shared components in resources/js/Components/
- Types in resources/js/types/index.ts
- Composables in resources/js/composables/

## Types to always reference
- User: { id: number, name: string, email: string, role: 'admin' | 'paseador' }
- District: { id: number, name: string, slug: string }
- Availability: { id: number, user_id: number, day: Day, start_time: string, end_time: string, districts: District[] }
- Reservation: { id: number, paseador_id: number | null, district_id: number, date: string, start_time: string, end_time: string, customer_name: string | null, status: 'pending' | 'confirmed' | 'cancelled' }
- Day: 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday'