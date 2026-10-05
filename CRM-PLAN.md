# Plan del CRM (dominio `Crm`)

Documento vivo para continuar el trabajo en otras conversaciones o con otros LLM. Léelo completo antes de tocar código y **actualiza las secciones "Estado" y "Lo que ya existe" al terminar cada fase**.

Última actualización: 2026-10-05 · rama `feat/crm` (nada subido al remoto por decisión del usuario).

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

Seguimiento comercial básico y, sobre todo, **automatizar los correos** posteriores a un curso. Antes se hacía con n8n + Google Sheets + scripts JS + plantillas de Brevo, más el envío manual del newsletter desde la interfaz de Brevo.

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
- **Brevo por API con `templateId` + params** (no SMTP con HTML propio). Las plantillas de Brevo siguen siendo la fuente del contenido; la edición básica/HTML dentro del CRM llega después.
- **Zona horaria del negocio: `America/Mexico_City`** (`config('crm.timezone')`). Las fechas se guardan en UTC y se muestran/programan en esa zona. **No se cambió `app.timezone`** (sigue UTC) para no afectar Objeción Cero.
- **Hora de envío: 12:00 pm** en esa zona (`config('crm.send_hour')`).
- **El refuerzo de 30 días es opt-in por curso**, en una **acción separada**: primero se marca el curso como impartido y luego se activa el refuerzo eligiendo la fecha del día 1 (por defecto, el lunes siguiente). Quienes ya van a medias en n8n **siguen en n8n** por ahora (no migrar en caliente).
- **Inscripción tardía**: la persona se **alinea al grupo** (recibe el correo del día en que va el grupo). Los días ya pasados quedan `skipped` y existe la acción **«Enviar anteriores»** (por persona y para todo el grupo) que los reprograma en orden de día, **uno por minuto**.
- **Después de activar el refuerzo no se quitan inscritos**: se pausan. (Quitar solo es posible antes de activar.)
- **Una pausa no acumula envíos**: al reanudar, lo que venció durante la pausa queda `skipped` (se puede enviar con «Enviar anteriores»).
- **Editar una plantilla de la secuencia aplica también a los envíos ya programados** que aún no salen (el ID se lee al enviar).
- **Marcar un curso como impartido** mueve la empresa a la primera etapa ganada si seguía en una etapa abierta (una empresa perdida no se toca).
- **Bajas y rebotes son por correo**, no por curso: se guardan en `crm_contacts` (`unsubscribed_at`, `bounced_at`) y cancelan todos los envíos pendientes de ese contacto.
- **Comodines** (destinatarios extra que reciben todos los correos): fuera de v1, se trabajan en detalle más adelante.
- **Orden dentro de una columna del kanban**: por antigüedad en la etapa (`stage_changed_at` desc), no manual.
- **Todos los miembros ven y editan todo.** Existen los roles `admin` y `seller`, pero aún no cambian permisos.
- **Sin dependencias nuevas** sin aprobación (regla del proyecto). Por eso no hay librería de Excel/CSV: el CSV se lee con `SplTempFileObject`.

---

## 3. Estado

| Fase | Contenido | Estado |
|---|---|---|
| 1 | Base del dominio, esquema, modelos, factories, acceso, seeders | ✅ |
| 2 | Kanban con arrastre, empresa, contactos, actividades, `POST /api/crm/leads` | ✅ |
| 3a | Importar contactos de **una empresa** desde CSV con plantilla | ✅ |
| 3b | Importar **empresas con sus contactos** en lote desde CSV con plantilla | ✅ |
| 4 | Refuerzo de 30 días: cursos, inscripciones, secuencia, envíos por Brevo, webhook, pantallas | ✅ **código y tests listos; falta configurar Brevo real (sección 6)** |
| 5 | Edición de plantillas en el CRM, lista/campañas del newsletter, tareas, métricas, vínculo con Objeción Cero / `Lms`, comodines, roles reales | ⏳ |

Historial de commits de la rama: `git log --oneline feat/crm` (convención `feat:` / `fix:` / `docs:` / `chore:` en español).

Suite al cerrar la Fase 3b: 181 tests (2 omitidos, preexistentes), Pint y PHPStan nivel 7 limpios.

---

## 4. Lo que ya existe

### Acceso
- Tabla `crm_team_members` (`user_id` único + `role`: `admin|seller`). Gate **`access-crm`** definido en `AppServiceProvider`.
- Todas las rutas web del CRM usan `['auth', 'verified', 'can:access-crm']`; sin membresía → **403**. El enlace «CRM» del sidebar usa `@can('access-crm')`.
- `can:` es middleware persistente de Livewire, así que las acciones de los componentes también quedan protegidas (`Livewire::test` **no** lo ejecuta: probar el acceso por HTTP).
- `CrmSeeder` hace admins del CRM a los usuarios `is_admin`.

### Esquema
| Tabla | Campos clave |
|---|---|
| `crm_team_members` | `user_id` (único), `role` |
| `crm_stages` | `name`, `slug` (único), `type` (`open|won|lost`), `position` |
| `crm_companies` | `name`, `industry`, `website`, `notes`, `stage_id`, `owner_id`, `source`, `lost_reason`, `stage_changed_at` |
| `crm_contacts` | `company_id` (null on delete), `user_id` único nullable, `name`, `email` (único), `phone`, `job_title`, `is_primary`, **`unsubscribed_at`, `bounced_at`** |
| `crm_activities` | `company_id`, `contact_id`, `user_id`, `type`, `body`, `occurred_at` |
| `crm_sequences` | `name` (hoy una: «Refuerzo de 30 días», creada por `CrmSeeder`) |
| `crm_sequence_steps` | `sequence_id`, `day` (1..30), `brevo_template_id` nullable; único `(sequence_id, day)` |
| `crm_courses` | `company_id`, `sequence_id`, `title`, `modality`, `hours`, `starts_on`, `ends_on`, `delivered_at`, `reinforcement_starts_on`, `reinforcement_activated_at` |
| `crm_contact_course` | pivote de inscripción: `contact_id`, `course_id` (único el par) |
| `crm_subscriptions` | `contact_id`, `course_id`, `status` (`active|paused|unsubscribed|bounced`); único el par |
| `crm_sends` | `subscription_id`, `sequence_step_id`, `scheduled_for` (UTC), `status` (`pending|queued|sent|failed|skipped|cancelled`), `sent_at`, `brevo_message_id`, `error`; **único `(subscription_id, sequence_step_id)`**; índice `(status, scheduled_for)` |

Etapas por defecto (`CrmSeeder`, editables en tabla): Nuevo → Atendido → Diagnóstico → Propuesta enviada → Aceptado → **Curso impartido** (won) / **Perdido** (lost).

### Código (`app/Domain/Crm/`)
- `Models/`: `TeamMember`, `Stage`, `Company`, `Contact`, `Activity`, `Sequence`, `SequenceStep`, `Course`, `Subscription`, `Send` (con `#[UseFactory]`, `$fillable`, `casts()` y docblocks `@property`; factories en `database/factories/Crm/`). **Todo campo que se escriba con `update()`/`create()` debe estar en `$fillable`** (un olvido con `unsubscribed_at`/`bounced_at` casi deja a un contacto dado de baja recibiendo correos; hay tests).
- `Enums/`: `TeamRole`, `StageType`, `LeadSource`, `ActivityType`, `CourseModality`, `SubscriptionStatus`, `SendStatus` (todos con `label()` en español).
- `Actions/`: `MoveCompanyToStage`, `SaveContact`, `ImportContacts`, `ImportCompanies`, `RegisterLead`, `MarkCourseDelivered`, `EnrollContacts`, `ActivateReinforcement`, `ScheduleSubscription`, `PauseSubscription`, `ResumeSubscription`, `SendMissedEmails`, `RetryFailedSends`, `DispatchDueSends`, `RecordBrevoEvent`.
- `Services/`: `CsvReader` (lectura/escritura genérica de CSV: BOM, Windows-1252, `,` o `;`, alias de encabezados, 500 filas), `ContactCsv` y `CompanyCsv` (plantilla y clasificación por fila de cada importador), `BrevoClient` (envío por plantilla con `Http`; `BrevoException` con `retryable`).
- `Jobs/SendReinforcementEmail`, `Exceptions/BrevoException`.
- Comando `php artisan crm:dispatch-sends` (`app/Console/Commands`), programado **cada minuto** con `withoutOverlapping` en `routes/console.php`.

### Rutas
- Web (`routes/crm.php`, prefijo `crm`, nombres `crm.*`): `pipeline`, `companies.{create,show,edit}`, `companies.import` y `companies.template` (importación de empresas), `companies.contacts.import` (acepta `?curso={id}`), `companies.courses.create`, `courses.{show,edit}`, `sequences.edit`, `contacts.template` (descarga).
- API (`routes/crm-api.php`, prefijo `api/crm`, cargada desde `bootstrap/app.php` con `then:`): `POST leads` (`crm.leads.store`) y `POST brevo/webhook` (`crm.brevo.webhook`). Ambas protegidas por `EnsureValidCrmToken:<clave de config>` (Bearer; si el token no está configurado, rechaza todo).
- Páginas Livewire SFC en `resources/views/pages/crm/⚡*.blade.php`: `pipeline`, `company`, `company-form`, `companies-import`, `contacts-import`, `course`, `course-form`, `sequence`.

### Flujo de la Fase 4 (cómo funciona)
1. En la ficha de la empresa → «Nuevo curso» (elige la secuencia). Inscribir contactos: checkboxes de los contactos de la empresa, o «Importar CSV e inscribir» (reutiliza el importador).
2. «Marcar impartido» (opcional, mueve la empresa a la etapa ganada).
3. La secuencia (`/crm/sequences/{id}`, enlace «Secuencia de refuerzo» en el pipeline) necesita **30 IDs de plantilla de Brevo** (hay una caja para pegar la lista, uno por línea). Sin plantillas completas no se puede activar.
4. «Activar refuerzo» + fecha del día 1: se crea una `Subscription` por inscrito que pueda recibir correo y 30 `Send` a las 12:00 `America/Mexico_City`. Si el inicio es hoy y ya pasó la hora, esos días quedan `skipped`.
5. Cada minuto, `crm:dispatch-sends` reclama atómicamente (`pending → queued`) hasta 200 envíos vencidos de suscripciones activas y encola `SendReinforcementEmail`. El job manda la plantilla por la API de Brevo con 3 intentos (backoff 60 s / 300 s); errores 4xx → `failed` sin reintentar; 429/5xx/red → reintenta. Si el contacto se dio de baja o la suscripción se pausó, cancela el envío.
6. Parámetros que recibe cada plantilla de Brevo (`{{ params.X }}`): `NOMBRE` (primer nombre), `NOMBRE_COMPLETO`, `EMPRESA`, `CURSO`, `DIA`. **Confirmar con las plantillas reales; si usan otros nombres, cambiar solo `SendReinforcementEmail::handle()`.**
7. Webhook de Brevo: `unsubscribed` y `spam` → baja; `hard_bounce`, `blocked` e `invalid_email` → rebote; el resto se ignora. Marca el contacto, pone sus suscripciones en `unsubscribed|bounced` y cancela sus envíos `pending|queued|skipped`.
8. La ficha del curso muestra por persona: estado, `enviados/30`, omitidos y fallidos, con **Pausar/Reanudar**, **Enviar anteriores**, **Reintentar** y, para todo el grupo, **Enviar anteriores a todos**.

### Entrada de leads (`POST /api/crm/leads`)
- Header `Authorization: Bearer <CRM_INTAKE_TOKEN>`. Límite 60/min.
- Campos: `source` (solo `form|whatsapp|landing|website`), `name`, `email` (obligatorios), `company`, `phone`, `job_title`, `message`.
- Crea empresa en la primera etapa abierta + contacto principal + el mensaje como actividad (201). Si el email ya existe no duplica: agrega la actividad y responde 200 con `duplicate: true`.

### Importación de contactos (CSV)
- Plantilla `plantilla-contactos.csv`: `nombre,email,telefono,puesto,principal` (UTF-8 con BOM).
- Acepta `,` o `;`, encabezados con acentos/sinónimos, Windows-1252, máx. 500 filas y 1 MB. Vista previa por fila (Nuevo / Actualizar / Error) antes de confirmar; al confirmar se vuelve a leer el archivo.
- Actualiza si el email existe en la misma empresa o sin empresa (celdas vacías conservan el valor); omite emails de otra empresa, repetidos o inválidos.

### Importación de empresas (CSV)
- Botón «Importar empresas» en el pipeline (`/crm/companies/import`). Plantilla `plantilla-empresas.csv`: `empresa,giro,sitio_web,etapa,responsable,origen,notas,contacto,email,telefono,puesto,principal`. Solo `empresa` es obligatoria.
- **Una fila = una empresa + uno de sus contactos**; repetir el nombre de la empresa agrega más contactos. Los datos de la empresa se toman de la **primera celda no vacía** entre sus filas válidas.
- La empresa se busca por nombre **sin importar mayúsculas, acentos ni espacios extra**. Si existe, solo cambia lo que el archivo trae lleno; una etapa distinta la mueve (reinicia la antigüedad) y una etapa vacía no la mueve.
- Valores por defecto para empresas **nuevas**: etapa = primera etapa abierta, responsable = quien importa, origen = importación. `etapa` se resuelve por nombre o slug; `responsable` por **email** de alguien del equipo; `origen` por nombre o valor (formulario, WhatsApp, landing, sitio web, importación, manual).
- Contactos: mismas reglas que el importador de contactos (email de otra empresa, repetido en el archivo o inválido → error; sin empresa → se adjunta; misma empresa → se actualiza). Nombre y email van juntos.
- **Cada fila es atómica**: si falla algo (empresa o contacto) se omite completa; la empresa se crea con la siguiente fila válida de esa empresa. Vista previa con resumen y fila por fila; al confirmar se vuelve a leer el archivo. Todo en una transacción.
- Límite: 500 filas / 1 MB. No hay `.xlsx` (guardar como CSV desde Excel).

### Datos de demostración
- `php artisan db:seed --class=CrmDemoSeeder`: 11 empresas en todas las etapas, ~21 contactos, actividades, 2 vendedores y un **curso impartido con 3 inscritos y el refuerzo sin activar** (a propósito: no envía nada). Idempotente; **no hace nada en producción**; no está en `DatabaseSeeder`.

---

## 5. Lo que falta / siguientes pasos

1. **Poner en marcha Brevo (sección 6)** y probar con un curso real de 1 persona antes del primero grande.
2. **Fase 5**:
   - Editar plantillas dentro del CRM (asunto/HTML propios) en lugar de depender del `templateId`.
   - Sincronizar contactos a una lista de Brevo y automatizar el envío de campañas del newsletter (hoy manual en su interfaz).
   - Registrar entregado/abierto/clic desde el webhook (columnas en `crm_sends`) y mostrar métricas.
   - Tareas y recordatorios por empresa; registrar actividades con fecha pasada; orden manual en columnas.
   - Vínculo contacto ↔ `User` de Objeción Cero (ya existe `crm_contacts.user_id`).
   - Comodines (destinatarios extra) y roles `admin`/`seller` con permisos reales.
   - Recuperar envíos atascados en `queued` si un worker muere (hoy no hay barrido automático).
3. Pendiente de decidir con el usuario: remitente y nombres de parámetros de las plantillas reales (ver punto 6 de «Flujo de la Fase 4»).

---

## 6. Puesta en marcha (operación)

Nada de esto está configurado en el `.env` local del usuario todavía.

1. **Variables** (`.env`; plantillas vacías en `.env.example`):
   - `CRM_INTAKE_TOKEN`: token largo y aleatorio para `POST /api/crm/leads`.
   - `BREVO_API_KEY`: clave de la API de Brevo (sin ella los envíos fallan con «Falta configurar BREVO_API_KEY» y se pueden reintentar después).
   - `CRM_BREVO_WEBHOOK_TOKEN`: token largo y aleatorio para el webhook.
2. **Worker de cola**: `QUEUE_CONNECTION=database`, así que debe haber un `php artisan queue:work` corriendo (en local, `composer run dev` o equivalente; en producción, un proceso supervisado).
3. **Scheduler**: debe correr `php artisan schedule:work` (local) o el cron `* * * * * php artisan schedule:run` (producción); sin él no se encolan los envíos.
4. **Webhook en Brevo**: URL `https://<dominio>/api/crm/brevo/webhook` con encabezado `Authorization: Bearer <CRM_BREVO_WEBHOOK_TOKEN>` y los eventos hard bounce, blocked, spam, unsubscribed e invalid email (si la interfaz de Brevo no permite encabezados, crear el webhook por su API).
5. **Plantillas**: en `/crm/sequences/{id}` pegar los 30 IDs de plantilla, en orden de día.
6. **Primera prueba**: un curso con 1 inscrito (tu propio correo) y fecha de inicio hoy o mañana; revisar la llegada, los parámetros y que no haya duplicados.
7. Cuidado en local con `BREVO_API_KEY` real: no activar refuerzos de datos de demo (correos `*.test` rebotan y dañan la reputación del remitente).

---

## 7. Convenciones y reglas de trabajo

Este repo tiene `CLAUDE.md` / `AGENTS.md` con las reglas de Laravel Boost; las más relevantes aquí:

- Seguir las convenciones del código vecino. Usar `php artisan make:*` (con `--no-interaction`, y una opción por argumento) para migraciones, comandos, etc.
- **Pest**: toda la lógica nueva lleva test. Correr lo mínimo: `php artisan test --compact tests/Feature/Crm`. Los tests usan SQLite en memoria; `RefreshDatabase` ya aplica a `Feature`. Usar `Http::fake()` y `Queue::fake()` para Brevo/colas; `travelTo()` para fechas (no combinarlo con subidas de archivo de Livewire en el mismo test).
- **Pint** obligatorio al terminar: `vendor/bin/pint --dirty --format agent`.
- **PHPStan nivel 7**: `vendor/bin/phpstan analyse --memory-limit=1G --no-progress`. No usar `@phpstan-ignore` ni casts para silenciarlo: agregar `@property` a los modelos o tipar bien.
- Livewire 4: páginas como SFC `pages::crm.*` (archivos con prefijo ⚡). `wire:key` en bucles. Validar y autorizar dentro de las acciones.
- Flux UI **free** (sin componentes Pro). UI en español y responsive: en móvil sin scroll horizontal de página ni tablas cortadas; preferir tarjetas apiladas.
- Preferir named routes y `route()`. Lógica de negocio en `Actions/`; no en las vistas.
- Migraciones inmutables una vez compartidas: cambios nuevos van en migraciones nuevas.
- No crear documentación extra sin que se pida (este archivo se pidió explícitamente).

### Trampas ya encontradas
- **`wire:sort` con tarjetas `<a>` no arrastra**: las tarjetas son `div` y el enlace va solo en el nombre. El contenedor soltable debe ocupar toda la altura de la columna (si no, no se puede soltar en columnas vacías). Evitar `snap-x` en el tablero.
- En el handler de `wire:sort` con grupos llegan `(id, posición, group-id)`; solo importa el grupo destino.
- `Livewire::test` no aplica middleware de ruta: el acceso se prueba con peticiones HTTP.
- `db:seed` en producción pide confirmación interactiva; por eso el test del `CrmDemoSeeder` lo llama directo.
- Pint reordena imports: no conformarse con `sed` (en macOS falla con saltos de línea); usar Python/Edit y correr Pint.
- Al escribir fechas con Carbon en una zona distinta de UTC, convertir a `->utc()` antes de guardar: Eloquent formatea con la zona de la instancia, no con la de la app.
- Pulsar «Llenar los días» y «Guardar» en la página de secuencia con milisegundos de diferencia (automatización) puede perder el relleno por orden de peticiones; una persona no lo nota.

---

## 8. Entorno local

- Corre con **LERD** (Podman), no con `artisan serve`/Sail/Herd. El sitio es **`http://b2bsalespro-app.test`** (**sin TLS**; con `https://` falla el certificado). Config en `.lerd.yaml`.
- BD de desarrollo: MySQL en `lerd-mysql` (`DB_HOST=lerd-mysql`), base `objecioncero`. Correo local: Mailpit.
- Migrar y sembrar el CRM: `php artisan migrate && php artisan db:seed --class=CrmSeeder` (y opcionalmente `CrmDemoSeeder`).
- Para ver la app autenticada, usar el navegador del usuario (Claude in Chrome, sesión ya abierta en Brave). El navegador integrado del escritorio solo abre la URL `http://` y no tiene la sesión.
- Para probar flujos de UI con Livewire desde el navegador se puede llamar `Livewire.all().find(c => c.name === 'pages::crm.…').$wire.método()` desde la consola y comprobar el resultado en la BD.

---

## 9. Cómo continuar

1. `git checkout feat/crm`, leer este archivo y correr `php artisan test --compact tests/Feature/Crm`.
2. Elegir el siguiente punto de la sección 5 y resolver con el usuario lo que quede abierto.
3. Implementar con tests, Pint y PHPStan; verificar en el navegador cuando haya cambios de interfaz.
4. Actualizar las secciones **3 (Estado)**, **4 (Lo que ya existe)** y **5/6** de este archivo y hacer commit con mensaje `feat:` / `fix:` en español.
