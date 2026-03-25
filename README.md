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