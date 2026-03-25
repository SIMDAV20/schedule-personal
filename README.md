## Start Laravel Reverb

php artisan reverb:start

## Stop Laravel Reverb

php artisan reverb:stop

## Restart Laravel Reverb

php artisan reverb:restart


// reservations table
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