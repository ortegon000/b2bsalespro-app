# Plan del CRM (dominio `Crm`)

Documento vivo para continuar el trabajo en otras conversaciones o con otros LLM. Léelo completo antes de tocar código y **actualiza la sección "Estado" al terminar cada fase**.

Última actualización: 2026-10-03 · rama `feat/crm` · commits `034181b`, `5ffa3c3`, `e73256f`.

---

## 1. Contexto y objetivo

El repo es una app Laravel 13 (PHP 8.5, Livewire 4, Flux UI free, Pest, Tailwind) que aloja varios productos separados por dominio en `app/Domain/`:

| Dominio | Estado |
|---|---|
| `ObjecionCero` | En producción/beta. **No mezclar** código del CRM aquí. |
| `Crm` | Este plan. |
| `Lms`, `Shared` | Vacíos, reservados. |

El CRM es de uso interno del equipo de ventas (no multi-tenant). Convive con Objeción Cero compartiendo solo la tabla `users` y el login. Algunos empleados de clientes tendrán acceso a Objeción Cero, por eso **un `User` no implica acceso al CRM** (ver sección 4).

### El dolor que resuelve

Seguimiento comercial básico y, sobre todo, **automatizar los correos** posteriores a un curso. Hoy se hace con n8n + Google Sheets + scripts JS + plantillas de Brevo, más el envío manual del newsletter desde la interfaz de Brevo.

### Flujo comercial real

1. Llega el lead (formulario, WhatsApp, landing o sitio web).
2. Se atiende a quien contactó (llamada, mensaje, Zoom).
3. Se hace un diagnóstico (encuesta en vivo online + sesiones privadas con algunos vendedores).
4. Se envía el diagnóstico y la propuesta económica con un temario a medida.
5. Si acepta: curso presencial u online, por las horas/días convenidos.
6. Tras el curso, los correos de **todos los empleados que lo tomaron** se agregan a la lista del newsletter y, un día específico (ej. el lunes siguiente), empieza la **secuencia de 30 días** de correos de refuerzo.

Los 30 correos son distintos entre sí pero **iguales para todos los clientes** (por ahora).

---

## 2. Decisiones tomadas (no re-litigar sin motivo)

- **La tarjeta del kanban es la empresa**, no la persona. Las personas son contactos de la empresa.
- **Tablas y código en inglés**, prefijo `crm_` en tablas. Textos de interfaz en español.
- **Correo único por contacto** (`crm_contacts.email`, único global). Hoy cada trabajador pertenece a una sola empresa.
- **Brevo**: SMTP y API. n8n hoy solo usa la API. Se enviará por la **API con `templateId` + params**, y se importarán las plantillas al inicio; la edición básica/HTML dentro del CRM llega después.
- **Zona horaria del negocio: `America/Mexico_City`**, en `config/crm.php` (`crm.timezone`). Las fechas se guardan en UTC y se muestran/programan en esa zona. **No se cambió `app.timezone`** (sigue UTC) para no afectar Objeción Cero.
- **Hora de envío de la secuencia: 12:00 pm** en esa zona.
- **El refuerzo de 30 días es opt-in por curso**, no obligatorio para todos los clientes. Quienes ya van a medias en n8n **siguen en n8n** por ahora (no migrar en caliente).
- **Comodines** (destinatarios extra que reciben todos los correos, p. ej. un gerente): fuera de v1, se trabajan en detalle más adelante.
- **Orden dentro de una columna del kanban**: lo define la antigüedad en la etapa (`stage_changed_at` desc), no una posición manual.
- **Todos los miembros ven y editan todas las empresas.** Existen los roles `admin` y `seller`, pero aún no cambian permisos.
- **Sin dependencias nuevas** sin aprobación (regla del proyecto).

---

## 3. Estado

| Fase | Contenido | Estado |
|---|---|---|
| 1 | Base del dominio, esquema, modelos, factories, acceso, pipeline de solo lectura, seeders | ✅ `034181b` |
| 2 | Kanban con arrastre, alta/edición de empresa, contactos, actividades, `POST /api/crm/leads` | ✅ `5ffa3c3` |
| 3a | Importar contactos de **una empresa** desde CSV con plantilla descargable | ✅ `e73256f` |
| 3b | Importar **empresas** (+ contactos) en lote desde CSV/Excel | ⏳ pendiente |
| 4 | Refuerzo de 30 días por Brevo (cursos, inscripciones, secuencias, envíos, webhooks) | ⏳ **siguiente, es el dolor principal** |
| 5 | Edición de plantillas en el CRM, sincronizar lista y campañas del newsletter, tareas/recordatorios, vínculo con Objeción Cero / `Lms`, comodines | ⏳ |

Suite al cerrar 3a: 91 tests (2 omitidos, preexistentes), Pint y PHPStan nivel 7 limpios.

---

## 4. Lo que ya existe

### Acceso
- Tabla `crm_team_members` (`user_id` único + `role`: `admin|seller`). Gate **`access-crm`** definido en `AppServiceProvider`.
- Todas las rutas web del CRM usan `['auth', 'verified', 'can:access-crm']`; sin membresía → **403**. El enlace «CRM» del sidebar usa `@can('access-crm')`.
- `can:` es middleware persistente de Livewire, así que las acciones de los componentes también quedan protegidas (`Livewire::test` **no** lo ejecuta: probar el acceso por HTTP).
- `CrmSeeder` hace admins del CRM a los usuarios `is_admin`.

### Esquema (`database/migrations/2026_10_03_2025*`)
| Tabla | Campos clave |
|---|---|
| `crm_team_members` | `user_id` (único), `role` |
| `crm_stages` | `name`, `slug` (único), `type` (`open|won|lost`), `position` |
| `crm_companies` | `name`, `industry`, `website`, `notes`, `stage_id` (restrict), `owner_id` → users (null on delete), `source`, `lost_reason`, `stage_changed_at` |
| `crm_contacts` | `company_id` (null on delete), `user_id` único nullable → users, `name`, `email` (único), `phone`, `job_title`, `is_primary` |
| `crm_activities` | `company_id` (cascade), `contact_id`, `user_id` (autor), `type`, `body`, `occurred_at`; índice `(company_id, occurred_at)` |

Etapas por defecto (`CrmSeeder`, editables en tabla): Nuevo → Atendido → Diagnóstico → Propuesta enviada → Aceptado → **Curso impartido** (won) / **Perdido** (lost).

### Código (`app/Domain/Crm/`)
- `Models/`: `TeamMember`, `Stage`, `Company`, `Contact`, `Activity` (con `#[UseFactory]`, `$fillable`, `casts()` y docblocks `@property`; los factories viven en `database/factories/Crm/`).
- `Enums/`: `TeamRole`, `StageType`, `LeadSource` (form, whatsapp, landing, website, import, manual), `ActivityType` (call, message, zoom, diagnosis, proposal, note). Todos con `label()` en español.
- `Actions/`: `MoveCompanyToStage`, `SaveContact` (garantiza un solo contacto principal y adjunta a la empresa), `ImportContacts`, `RegisterLead`.
- `Services/ContactCsv`: genera la plantilla y lee/clasifica el CSV.

### Rutas
- Web (`routes/crm.php`, prefijo `crm`, nombres `crm.*`): `pipeline`, `companies.create`, `companies.show`, `companies.edit`, `companies.contacts.import`, `contacts.template` (descarga).
- API (`routes/crm-api.php`, cargada desde `bootstrap/app.php` con `then:`): `POST /api/crm/leads` (`crm.leads.store`).
- Páginas Livewire SFC en `resources/views/pages/crm/⚡*.blade.php` (`pipeline`, `company`, `company-form`, `contacts-import`).

### Entrada de leads (`POST /api/crm/leads`)
- Header `Authorization: Bearer <CRM_INTAKE_TOKEN>` (`config('crm.intake_token')`; vacío = rechaza todo). Límite 60/min.
- Campos: `source` (solo `form|whatsapp|landing|website`), `name`, `email` (obligatorios), `company`, `phone`, `job_title`, `message`.
- Crea empresa en la primera etapa abierta + contacto principal + el mensaje como actividad (201). Si el email ya existe no duplica: agrega la actividad y responde 200 con `duplicate: true`. Sin `company` usa el nombre de la persona.

### Importación de contactos
- Plantilla `plantilla-contactos.csv`: `nombre,email,telefono,puesto,principal` (UTF-8 con BOM, abre bien en Excel).
- Acepta `,` o `;`, encabezados con acentos/sinónimos, Windows-1252, máx. 500 filas y 1 MB.
- Vista previa por fila antes de confirmar: **Nuevo / Actualizar / Error**. Actualiza si el email existe en la misma empresa o sin empresa (celdas vacías conservan el valor); omite emails de otra empresa, repetidos o inválidos. Al confirmar se vuelve a leer el archivo (no se confía en la vista previa).

### Datos de demostración
- `php artisan db:seed --class=CrmDemoSeeder`: 11 empresas en todas las etapas, ~21 contactos, actividades y 2 vendedores de demo. Idempotente, **no hace nada en producción** y no está en `DatabaseSeeder` a propósito.

---

## 5. Fase 4: refuerzo de 30 días (siguiente)

Objetivo: reemplazar n8n + Google Sheets + scripts para los cursos nuevos.

### Modelo propuesto
| Tabla | Campos clave |
|---|---|
| `crm_courses` | `company_id`, `title`, `modality` (presencial/online), `hours`, `starts_on`, `ends_on`, `reinforcement_enabled` (bool), `reinforcement_starts_on` (date) |
| `crm_course_enrollments` | `course_id`, `contact_id` (único por par) |
| `crm_sequences` | `name`, `provider_list_id` nullable |
| `crm_sequence_steps` | `sequence_id`, `day` (1..30), `brevo_template_id`, `subject`/`html` nullables (para edición futura) |
| `crm_subscriptions` | `contact_id`, `sequence_id`, `course_id`, `starts_on`, `status` (`active|paused|completed|unsubscribed|bounced`) |
| `crm_sends` | `subscription_id`, `step_id`, `scheduled_for` (UTC), `sent_at`, `status`, `brevo_message_id`, `error`; **único `(subscription_id, step_id)`** para no duplicar |

### Reglas
- Marcar a quién tomó el curso: a mano o importando CSV (reutilizar `ContactCsv`).
- Activar el refuerzo = elegir fecha de inicio (p. ej. el lunes siguiente); se generan 30 `crm_sends` a las **12:00 `America/Mexico_City`** convertidas a UTC.
- Un comando programado (`schedule`, cada pocos minutos, sin solaparse) despacha por cola los envíos vencidos. Cola actual: `database`.
- `BrevoClient` en `Services/` detrás de un contrato (`BrevoClient` o interfaz) para poder usar `Http::fake()`: envío transaccional por `templateId` + params, y alta/baja en lista.
- **Webhooks de Brevo** (rebote, baja, queja): marcan la suscripción y cancelan los envíos pendientes. Es obligatorio para cuidar la reputación del remitente.
- Pausar/reanudar y reintentar un envío fallido desde la interfaz; pantalla de estado de envíos.
- Importación inicial de las 30 plantillas: mapear día → `brevo_template_id` (CSV o pantalla simple).
- Credenciales (`BREVO_API_KEY`, remitente) por `.env` y `config/crm.php`/`config/services.php`; nunca en el repo.

### Preguntas abiertas para esta fase
1. ¿Qué remitente (nombre/correo) y qué IDs de plantilla (1..30) se usan?
2. ¿Qué parámetros reciben las plantillas hoy (nombre, empresa…)?
3. ¿Se activa el refuerzo al marcar el curso como impartido o es una acción separada?
4. ¿Qué pasa si se inscribe a alguien a mitad de la secuencia (empezar en el día 1 o alinearse al grupo)?

---

## 6. Fase 3b y 5 (resumen)

- **3b**: importar empresas con contactos desde CSV/Excel (mapeo de columnas, vista previa, deduplicación por nombre de empresa y email, procesado en cola con reporte). Requiere decidir si se admite `.xlsx` (hoy no hay librería instalada; pedir aprobación antes de agregar una dependencia).
- **5**: edición de plantillas en el CRM; sincronizar contactos a una lista de Brevo y automatizar campañas del newsletter; tareas/recordatorios por empresa; vínculo contacto ↔ `User` de Objeción Cero (ya existe `crm_contacts.user_id`); comodines; roles con permisos reales; registrar actividades con fecha pasada; orden manual dentro de columnas si se necesita.

---

## 7. Convenciones y reglas de trabajo

Este repo tiene `CLAUDE.md` / `AGENTS.md` con las reglas de Laravel Boost; las más relevantes aquí:

- Seguir las convenciones del código vecino. Usar `php artisan make:*` (con `--no-interaction`) para migraciones, controladores, etc.
- **Pest**: toda la lógica nueva lleva test. Correr lo mínimo: `php artisan test --compact tests/Feature/Crm`. Los tests usan SQLite en memoria (`phpunit.xml`); `RefreshDatabase` ya aplica a `Feature`.
- **Pint** obligatorio al terminar: `vendor/bin/pint --dirty --format agent`.
- **PHPStan nivel 7**: `vendor/bin/phpstan analyse --memory-limit=1G --no-progress`. No usar `@phpstan-ignore` ni casts para silenciarlo: agregar `@property` a los modelos o tipar bien.
- Livewire 4: páginas como SFC `pages::crm.*` (archivos con prefijo ⚡). Usar `wire:key` en bucles. Validar y autorizar dentro de las acciones.
- Flux UI **free** (sin componentes Pro). UI en español, responsive: en móvil nada de scroll horizontal de página ni tablas cortadas (hubo varios fixes previos); preferir tarjetas apiladas en móvil.
- Preferir named routes y `route()`. Lógica de negocio en `Actions/`; no en las vistas.
- Migraciones inmutables una vez compartidas: cambios nuevos van en migraciones nuevas.
- No crear documentación extra sin que se pida (este archivo se pidió explícitamente).

### Trampas ya encontradas
- **`wire:sort` con tarjetas `<a>` no arrastra** (choca con el arrastre nativo de enlaces y `wire:navigate`). Las tarjetas son `div` y el enlace va solo en el nombre. El contenedor soltable ocupa toda la altura de la columna (si no, no se puede soltar en columnas vacías). Evitar `snap-x` en el tablero.
- En el handler de `wire:sort` con grupos llegan `(id, posición, group-id)`; solo importa el grupo destino.
- `Livewire::test` no aplica middleware de ruta: el acceso (`can:access-crm`) se prueba con peticiones HTTP.
- `db:seed` en producción pide confirmación interactiva; por eso el test del `CrmDemoSeeder` lo llama directo.
- Pint reordena imports: no conformarse con `sed`, usar Python/Edit y correr Pint.

---

## 8. Entorno local

- Corre con **LERD** (Podman), no con `artisan serve`/Sail/Herd. El sitio es **`http://b2bsalespro-app.test`** (**sin TLS**; con `https://` falla el certificado). Config en `.lerd.yaml`.
- BD de desarrollo: MySQL en el contenedor `lerd-mysql` (`DB_HOST=lerd-mysql`), base `objecioncero`. Correo local: Mailpit.
- Migrar y sembrar el CRM: `php artisan migrate && php artisan db:seed --class=CrmSeeder` (y opcionalmente `CrmDemoSeeder`).
- Para ver la app autenticada sin pedir credenciales, usar el navegador del usuario (Claude in Chrome, sesión ya abierta). El navegador integrado del escritorio solo abre la URL `http://`.
- `.lerd.yaml` aparece modificado (worker de Vite que agrega LERD): **no es parte del CRM, no lo incluyas en los commits**.
- Variable nueva: `CRM_INTAKE_TOKEN` (ya en `.env.example`, vacía). Debe definirse en `.env` para que funcione la entrada de leads.

---

## 9. Cómo continuar

1. `git checkout feat/crm`, leer este archivo y correr `php artisan test --compact tests/Feature/Crm`.
2. Elegir la siguiente fase de la tabla de la sección 3 (recomendada: **Fase 4**) y resolver sus preguntas abiertas con el usuario.
3. Implementar con tests, Pint y PHPStan; verificar en el navegador cuando haya cambios de interfaz.
4. Actualizar la sección **3 (Estado)** y **4 (Lo que ya existe)** de este archivo, y hacer commit con mensaje `feat:` / `fix:` en español.
