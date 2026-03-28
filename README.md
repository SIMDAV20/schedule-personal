## Laravel Reverb (Real-time)

Se ha implementado **Laravel Reverb** para actualizar el calendario del administrador en tiempo real cuando un paseador agrega o elimina su disponibilidad.

### Pasos de Implementación:

1. **Evento de Transmisión:**
   - Se creó el evento `App\Events\AvailabilityUpdated`.
   - Implementa `ShouldBroadcastNow` para envío inmediato.
   - Transmite en el canal privado `admin.schedule`.

2. **Backend (Controlador):**
   - En `AvailabilityController`, se dispara el evento `AvailabilityUpdated::dispatch()` después de cada inserción, actualización o eliminación de registros.

3. **Canales (Autorización):**
   - En `routes/channels.php`, se definió el acceso al canal `admin.schedule`.
   - Solo los usuarios con el rol `admin` están autorizados para escuchar este canal.

4. **Frontend (Vue + Echo):**
   - En `Admin/Schedule.vue`, se usa `onMounted` para suscribirse al canal.
   - Al recibir el evento, el componente ejecuta `router.reload({ only: ['paseadores', 'pendingReservations'] })`.
   - Esto realiza una recarga parcial de los datos vía Inertia, actualizando el calendario sin refrescar toda la página.

### Comandos Reverb:

- **Iniciar:** `php artisan reverb:start`
- **Detener:** `php artisan reverb:stop`
- **Reiniciar:** `php artisan reverb:restart`

---

## Base de Datos (Notas)

### Tabla de Reservas (`reservations`)

$table->foreignId('paseador_id')->nullable()->change(); // permite reservas sin paseador
$table->string('status')->default('pending'); // pending | confirmed | cancelled
```

**Estados:**
- `pending` → sin paseador asignado
- `confirmed` → paseador asignado
- `cancelled` → cancelada (soft, no se elimina)

---

**Flujos:**
```
Flujo A — desde el calendario (paseador conocido)
  Click en evento disponible
  → Modal crear: fecha+hora pre-llenada, paseador fijo
  → Al guardar: status = confirmed

Flujo B — sin paseador
  Botón "Nueva reserva" en el header
  → Modal crear: admin llena todo manualmente
  → Al guardar: status = pending, paseador_id = null

Asignación posterior (Desde la tabla de reservas pendientes)
  → Muestra información de la reserva: fecha, hora, cliente
  → Primer Select: Paseadores Disponibles (sin cruce de horarios y con disponibilidad en el día/distrito)
  → Segundo Select: Todos los paseadores (para asignar de manera forzada si coordina externamente)
  → Al guardar: status = confirmed
```

🦮 Proyecto: Schedule Personal (Paseadores)
Sistema integral desarrollado para la gestión de disponibilidad y reservas de paseadores de perros, con soporte para múltiples distritos y actualizaciones en tiempo real.

🛠️ Stack Tecnológico
Backend: Laravel 12 (PHP 8.3+)
Frontend: Vue 3 (Composition API / Script Setup + TypeScript)
Comunicación: Inertia.js v2
Real-time: Laravel Reverb (WebSockets)
Estilos: Tailwind CSS v4
Base de Datos: MySQL
🏗️ Arquitectura y Entidades Core
Usuarios & Roles: Sistema basado en roles (admin y 

paseador
). Los administradores gestionan toda la red, mientras que los paseadores controlan su propia agenda.
Distritos: Entidades geográficas que delimitan las zonas de cobertura de cada paseador.
Disponibilidades (Availabilities): Configuración recurrente por día de la semana y rangos horarios. Un registro puede cubrir múltiples distritos simultáneamente.
Reservas (Reservations): Gestión de citas con estados (pending, confirmed, cancelled). Soportan asignación manual o automática.
🚀 Funcionalidades Clave
1. Gestión de Disponibilidad (Paseador)
Formulario intuitivo para que el paseador defina sus días y horas de trabajo.
Visualización de agenda agrupada por días de la semana.
Las disponibilidades y las reservas son independientes: eliminar una disponibilidad no cancela compromisos ya pactados.
2. Panel de Control & Calendario (Admin)
Vista Maestra: Calendario basado en FullCalendar que integra todas las ofertas de trabajo y reservas existentes.
Filtros Inteligentes: Capacidad de filtrar el calendario por distrito.
Asignación: Permite asignar paseadores a reservas "pendientes" verificando conflictos de horario o disponibilidad previa.
3. Sincronización en Tiempo Real
Laravel Reverb: Implementación de WebSockets para que el calendario admin se actualice instantáneamente cuando cualquier paseador añade o elimina horarios, sin necesidad de recargar la página.
🔄 Flujos de Reserva
Flujo A (Confirmado): Reserva directa desde un bloque de disponibilidad conocido del paseador.
Flujo B (Pendiente): Reserva libre creada por el administrador sin paseador asignado inicialmente.
Asignación: Proceso de emparejamiento manual de reservas pendientes con paseadores disponibles.
Este proyecto prioriza la experiencia de usuario (UX) mediante el uso de modales, alertas de integridad y una interfaz limpia y moderna.