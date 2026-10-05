# Plan del CRM (dominio `Crm`)

Documento vivo para continuar el trabajo en otras conversaciones o con otros LLM. Léelo completo antes de tocar código y **actualiza las secciones "Estado" y "Lo que ya existe" al terminar cada fase**.

Última actualización: 2026-10-05 (6A: infraestructura de correos Blade lista; faltan los correos 2–30) · rama `feat/crm` (nada subido al remoto por decisión del usuario).

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
- **Los correos de la secuencia serán vistas Blade del repo, no plantillas de Brevo** (decisión del usuario, 2026-10-05): no hace falta un editor dentro del CRM, se edita el Blade. Se envían por la **API de Brevo con `htmlContent`** (no por SMTP) para seguir recibiendo el `message-id` y con él las métricas. **Transición**: los días que ya tienen vista salen como HTML propio; los que no, siguen saliendo por su `brevo_template_id`. Cuando los 30 tengan vista se retira la plantilla de Brevo (ver 6A).
- **Respuestas del usuario para 6A (2026-10-05)**: los 30 correos comparten **siempre el mismo diseño** y solo cambia el texto; **la única imagen es el logo** (se descartó la foto de Pexels del día 1; no hay imagen por correo); remitente **`cursos@b2bsalespro.mx`** (ya verificado en Brevo, nombre «B2B Sales Pro»); el usuario **pasará el contenido de los correos 2–30**.
- **Descartado**: editor de plantillas dentro del CRM y orden manual de tarjetas dentro de una columna del kanban (hoy se ordenan por antigüedad en la etapa; «orden manual» = arrastrar una tarjeta arriba/abajo dentro de su columna para priorizarla, lo que exigiría guardar una posición por tarjeta; las etiquetas de tareas vencidas ya cumplen esa función). Se retoma solo si el usuario lo pide.
- **Zona horaria del negocio: `America/Mexico_City`** (`config('crm.timezone')`). Las fechas se guardan en UTC y se muestran/programan en esa zona. **No se cambió `app.timezone`** (sigue UTC) para no afectar Objeción Cero.
- **Hora de envío: 12:00 pm** en esa zona (`config('crm.send_hour')`).
- **El refuerzo de 30 días es opt-in por curso**, en una **acción separada**: primero se marca el curso como impartido y luego se activa el refuerzo eligiendo la fecha del día 1 (por defecto, el lunes siguiente). Quienes ya van a medias en n8n **siguen en n8n** por ahora (no migrar en caliente).
- **Inscripción tardía**: la persona se **alinea al grupo** (recibe el correo del día en que va el grupo). Los días ya pasados quedan `skipped` y existe la acción **«Enviar anteriores»** (por persona y para todo el grupo) que los reprograma en orden de día, **uno por minuto**.
- **Después de activar el refuerzo no se quitan inscritos**: se pausan. (Quitar solo es posible antes de activar.)
- **Una pausa no acumula envíos**: al reanudar, lo que venció durante la pausa queda `skipped` (se puede enviar con «Enviar anteriores»).
- **Editar una plantilla de la secuencia aplica también a los envíos ya programados** que aún no salen (el ID se lee al enviar).
- **Marcar un curso como impartido** mueve la empresa a la primera etapa ganada si seguía en una etapa abierta (una empresa perdida no se toca).
- **Bajas y rebotes son por correo**, no por curso: se guardan en `crm_contacts` (`unsubscribed_at`, `bounced_at`) y cancelan todos los envíos pendientes de ese contacto.
- **La lista de Brevo del newsletter se llama `newsletter`** (`CRM_BREVO_NEWSLETTER_LIST`, por defecto ese nombre). Se busca por nombre (sin importar mayúsculas) y **debe existir en Brevo**: el CRM no la crea.
- **Quién entra al newsletter**: contactos inscritos en un curso **impartido**, que no se hayan dado de baja ni rebotado, y que aún no estén sincronizados. Se suman al **marcar el curso como impartido**, al inscribir a alguien en un curso ya impartido, o a mano con «Sumar al newsletter» / «Sincronizar pendientes». Sin `BREVO_API_KEY` no se encola nada (quedan pendientes).
- **Las campañas se crean desde una plantilla de Brevo** (asunto y remitente salen de la plantilla) para toda la lista; se envían al momento o se programan (hora de `America/Mexico_City`, mínimo 10 minutos en el futuro). El envío siempre pide confirmación.
- **Las tareas vencen por día en la zona del negocio** (una tarea del día 9 sigue vigente hasta las 23:59 de México, aunque en UTC ya sea el 10).
- **Recordatorios**: tarjeta con «N tareas vencidas» en el pipeline, página «Tareas» (mías/todas) y un **correo diario** (lunes a viernes, 08:00 México) a cada persona del equipo con sus tareas de hoy o vencidas.
- **Las tasas se calculan sobre los correos enviados** y se avisa en pantalla que las aperturas pueden estar infladas por la protección de privacidad de Apple Mail: el **clic es la señal más confiable**.
- **Las métricas de la secuencia vienen del webhook de Brevo** (se enlazan por `message-id`, que se guarda al enviar); **las de las campañas se piden a la API** (se actualizan cada hora para campañas enviadas en los últimos 30 días, o a mano).
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
| 5a | Newsletter: sumar a quienes toman un curso a la lista `newsletter` de Brevo y enviar/programar campañas | ✅ **código y tests listos; falta configurar Brevo real (sección 6)** |
| 5b | Tareas y recordatorios por empresa (+ resumen diario por correo) y actividades con fecha pasada | ✅ |
| 5c | Métricas de entrega (entregado/abierto/clic) de la secuencia y de las campañas | ✅ **falta activar los eventos en el webhook de Brevo (sección 6)** |
| 6A | **Correos como Blade con personalización**: layout, vista previa, prueba a uno mismo, enlace de baja, envío por HTML | 🟡 **infraestructura y día 1 listos; faltan las vistas de los días 2–30 (las pasa el usuario)** |
| 6B | Piloto real: un curso de una persona con Brevo configurado | ⏳ |
| 6C | Portada del CRM (resumen de empresas, tareas, refuerzos y envíos) | ⏳ |
| 6D | Conectar las entradas de leads (formulario, landing, WhatsApp/n8n) con ejemplos | ⏳ |
| 6E | Migrar a quienes van a medias en n8n/Google Sheets | ⏳ |
| 6F | Reportes comerciales (origen, tiempo por etapa, pérdidas) | ⏳ |
| 6G | Revisión, PR hacia `master` y salida a producción | ⏳ |
| — | Backlog sin fecha (sección 5) | ⏳ |

Historial de commits de la rama: `git log --oneline feat/crm` (convención `feat:` / `fix:` / `docs:` / `chore:` en español).

Suite al cerrar la infraestructura 6A: 274 tests (2 omitidos, preexistentes), Pint y PHPStan nivel 7 limpios.

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
| `crm_contacts` | `company_id` (null on delete), `user_id` único nullable, `name`, `email` (único), `phone`, `job_title`, `is_primary`, **`unsubscribed_at`, `bounced_at`, `newsletter_synced_at`** |
| `crm_activities` | `company_id`, `contact_id`, `user_id`, `type`, `body`, `occurred_at` |
| `crm_sequences` | `name` (hoy una: «Refuerzo de 30 días», creada por `CrmSeeder`) |
| `crm_sequence_steps` | `sequence_id`, `day` (1..30), `brevo_template_id` nullable; único `(sequence_id, day)` |
| `crm_courses` | `company_id`, `sequence_id`, `title`, `modality`, `hours`, `starts_on`, `ends_on`, `delivered_at`, `reinforcement_starts_on`, `reinforcement_activated_at` |
| `crm_contact_course` | pivote de inscripción: `contact_id`, `course_id` (único el par) |
| `crm_subscriptions` | `contact_id`, `course_id`, `status` (`active|paused|unsubscribed|bounced`); único el par |
| `crm_newsletter_campaigns` | `name`, `brevo_template_id`, `brevo_campaign_id`, `scheduled_for` (null = enviada al momento), `user_id`, **`stats`** (json: sent, delivered, opened, clicked, unsubscribed, bounced, complaints), **`stats_synced_at`** |
| `crm_tasks` | `company_id` (cascade), `assigned_to` → users (null on delete), `title`, `due_on` (date), `completed_at`; índice `(completed_at, due_on)` |
| `crm_sends` | `subscription_id`, `sequence_step_id`, `scheduled_for` (UTC), `status` (`pending|queued|sent|failed|skipped|cancelled`), `sent_at`, `brevo_message_id`, `error`; **único `(subscription_id, sequence_step_id)`**; índices `(status, scheduled_for)` y `brevo_message_id`; **`delivered_at`, `opened_at`, `clicked_at`** (primera vez), **`opens_count`, `clicks_count`** |

Etapas por defecto (`CrmSeeder`, editables en tabla): Nuevo → Atendido → Diagnóstico → Propuesta enviada → Aceptado → **Curso impartido** (won) / **Perdido** (lost).

### Código (`app/Domain/Crm/`)
- `Models/`: `TeamMember`, `Stage`, `Company`, `Contact`, `Activity`, `Sequence`, `SequenceStep`, `Course`, `Subscription`, `Send`, `NewsletterCampaign`, `Task` (con `#[UseFactory]`, `$fillable`, `casts()` y docblocks `@property`; factories en `database/factories/Crm/`). **Todo campo que se escriba con `update()`/`create()` debe estar en `$fillable`** (un olvido con `unsubscribed_at`/`bounced_at` casi deja a un contacto dado de baja recibiendo correos; hay tests).
- `Enums/`: `TeamRole`, `StageType`, `LeadSource`, `ActivityType`, `CourseModality`, `SubscriptionStatus`, `SendStatus` (todos con `label()` en español).
- `Actions/`: `MoveCompanyToStage`, `SaveContact`, `ImportContacts`, `ImportCompanies`, `RegisterLead`, `MarkCourseDelivered`, `EnrollContacts`, `ActivateReinforcement`, `ScheduleSubscription`, `PauseSubscription`, `ResumeSubscription`, `SendMissedEmails`, `RetryFailedSends`, `DispatchDueSends`, `RecordBrevoEvent`, `SyncNewsletter`, `CreateNewsletterCampaign`, `SendTaskDigest`, `RecordSendEvent`, `SyncCampaignStats`, `SendTestEmail`, `UnsubscribeContact`.
- `Services/`: `CsvReader` (lectura/escritura genérica de CSV: BOM, Windows-1252, `,` o `;`, alias de encabezados, 500 filas), `ContactCsv` y `CompanyCsv` (plantilla y clasificación por fila de cada importador), `SendMetrics` (resumen y tasas de `crm_sends`), `ReinforcementEmail` (render de los correos Blade), `BrevoClient` (con `Http`: envío por plantilla, lista del newsletter por nombre con caché de 1 h, alta/actualización de contactos y campañas; `BrevoException` con `retryable`).
- `Jobs/SendReinforcementEmail`, `Jobs/SyncContactToNewsletter`, `Exceptions/BrevoException`, `Notifications/TaskDigest`.
- Comandos (`app/Console/Commands`, programados en `routes/console.php`): `crm:dispatch-sends` **cada minuto** (`withoutOverlapping`), `crm:send-task-digest` **lunes a viernes 08:00 México** y `crm:sync-campaign-stats` **cada hora**.

### Rutas
- Web (`routes/crm.php`, prefijo `crm`, nombres `crm.*`): `pipeline`, `companies.{create,show,edit}`, `companies.import` y `companies.template` (importación de empresas), `companies.contacts.import` (acepta `?curso={id}`), `companies.courses.create`, `courses.{show,edit}`, `sequences.edit`, `sequences.preview`, `newsletter`, `tasks`, `contacts.template` (descarga).
- Públicas firmadas (en `routes/crm.php`, fuera del grupo con `can:access-crm`): `crm.unsubscribe.show` y `crm.unsubscribe.destroy` (prefijo `crm/baja`).
- API (`routes/crm-api.php`, prefijo `api/crm`, cargada desde `bootstrap/app.php` con `then:`): `POST leads` (`crm.leads.store`) y `POST brevo/webhook` (`crm.brevo.webhook`). Ambas protegidas por `EnsureValidCrmToken:<clave de config>` (Bearer; si el token no está configurado, rechaza todo).
- Páginas Livewire SFC en `resources/views/pages/crm/⚡*.blade.php`: `pipeline`, `company`, `company-form`, `companies-import`, `contacts-import`, `course`, `course-form`, `sequence`, `newsletter`, `tasks`.

### Flujo de la Fase 4 (cómo funciona)
1. En la ficha de la empresa → «Nuevo curso» (elige la secuencia). Inscribir contactos: checkboxes de los contactos de la empresa, o «Importar CSV e inscribir» (reutiliza el importador).
2. «Marcar impartido» (opcional, mueve la empresa a la etapa ganada).
3. La secuencia (`/crm/sequences/{id}`, enlace «Secuencia de refuerzo» en el pipeline) necesita **30 IDs de plantilla de Brevo** (hay una caja para pegar la lista, uno por línea). Sin plantillas completas no se puede activar.
4. «Activar refuerzo» + fecha del día 1: se crea una `Subscription` por inscrito que pueda recibir correo y 30 `Send` a las 12:00 `America/Mexico_City`. Si el inicio es hoy y ya pasó la hora, esos días quedan `skipped`.
5. Cada minuto, `crm:dispatch-sends` reclama atómicamente (`pending → queued`) hasta 200 envíos vencidos de suscripciones activas y encola `SendReinforcementEmail`. El job manda la plantilla por la API de Brevo con 3 intentos (backoff 60 s / 300 s); errores 4xx → `failed` sin reintentar; 429/5xx/red → reintenta. Si el contacto se dio de baja o la suscripción se pausó, cancela el envío.
6. *(Solo para los días que todavía no tienen vista; los días con vista usan las variables de Blade de la sección «Correos de la secuencia».)* Parámetros que recibe cada plantilla de Brevo (`{{ params.X }}`): `NOMBRE` (primer nombre), `NOMBRE_COMPLETO`, `EMPRESA`, `CURSO`, `DIA`. **Confirmar con las plantillas reales; si usan otros nombres, cambiar solo `SendReinforcementEmail::handle()`.**
7. Webhook de Brevo: `unsubscribed` y `spam` → baja; `hard_bounce`, `blocked` e `invalid_email` → rebote; el resto se ignora. Marca el contacto, pone sus suscripciones en `unsubscribed|bounced` y cancela sus envíos `pending|queued|skipped`.
8. La ficha del curso muestra por persona: estado, `enviados/30`, omitidos y fallidos, con **Pausar/Reanudar**, **Enviar anteriores**, **Reintentar** y, para todo el grupo, **Enviar anteriores a todos**.

### Correos de la secuencia (Blade)
- **Vistas** en `resources/views/emails/crm/reinforcement/`: `layout.blade.php` (armazón del diseño original: banda `#020617` con el logo, contenedor de 600 px, título `rgb(255,101,103)`, pie con enlace de baja; logo en `config('crm.email_logo_url')`) y una vista por día `dia-NN.blade.php` (hoy solo `dia-01`). Cada día extiende el layout y define `@section('asunto', …)`, `titulo`, `preheader` (opcional) y `contenido` (HTML simple: `<p>`, `<h3>`, `<ol>`, `<strong>`; los estilos los pone el layout).
- **Variables** en las vistas: `$nombre`, `$nombreCompleto`, `$empresa`, `$curso`, `$dia`, `$total`, `$siguiente` (null el último día), `$bajaUrl`. Todo se escapa con `{{ }}`.
- **`ReinforcementEmail`** (`Services/`): `exists($day)`, `availableDays()`, `render($day, $data)` → `{subject, html}` (el asunto sale del `<title>` del layout), `sampleData()`, `dataFor(Subscription, $total)`, `unsubscribeUrl(Subscription)`.
- **Envío** (`SendReinforcementEmail`): si el día tiene vista → `BrevoClient::sendHtml()` (`POST /smtp/email` con `sender` de `config('crm.brevo.sender_*')`, `subject`, `htmlContent`, `tags` `crm-refuerzo` y `dia-N`, y encabezados `List-Unsubscribe` + `List-Unsubscribe-Post: List-Unsubscribe=One-Click`); si no, `sendTemplate()` con el `brevo_template_id`; si no hay ni una ni otra, el envío queda `failed` con un mensaje claro. Las métricas siguen igual (se guarda el `messageId`).
- **`Sequence::isReady()`**: todos los días tienen vista **o** plantilla de Brevo.
- **Pantalla de la secuencia** (`/crm/sequences/{id}`): por día, si tiene «Correo propio», «Plantilla de Brevo» o «Sin correo», su asunto, **Vista previa** (`crm.sequences.preview`, HTML con datos de ejemplo, abre en otra pestaña), **Enviarme prueba** (`SendTestEmail`: manda el día al correo de quien lo pide, asunto con «[PRUEBA]», sin crear envíos) y el ID de plantilla (solo para días sin vista) más las métricas por día.
- **Baja con un clic**: rutas públicas **firmadas** `crm.unsubscribe.show` (GET: página de confirmación, **no da de baja**) y `crm.unsubscribe.destroy` (POST: da de baja; sin CSRF —`crm/baja/*` está excluida en `bootstrap/app.php`— porque la protege la firma). `UnsubscribeContact` (acción compartida con el webhook de Brevo) marca `unsubscribed_at`, pone las suscripciones en `unsubscribed` y cancela los envíos pendientes; es idempotente.

### Newsletter (`/crm/newsletter`)
- Muestra cuántos contactos están en la lista (`newsletter_synced_at`) y cuántos están **pendientes**, con «Sincronizar pendientes» (también «Sumar al newsletter» en la ficha de un curso impartido).
- `SyncContactToNewsletter` hace `POST /contacts` en Brevo (crea o actualiza, `listIds`, atributos `FIRSTNAME`/`LASTNAME`) y marca `newsletter_synced_at`. Reintenta 3 veces los errores temporales; un error permanente (clave inválida, lista inexistente) deja al contacto pendiente.
- «Enviar una campaña»: nombre + ID de plantilla de Brevo (+ fecha opcional) → `POST /emailCampaigns` a la lista y, si no hay fecha, `sendNow`. Queda en el historial (`crm_newsletter_campaigns`).
- El webhook entiende también los eventos de campañas (`unsubscribe`, `hardBounce`) además de los transaccionales.

### Métricas
- **Secuencia (webhook transaccional de Brevo)**: `RecordSendEvent` ubica el envío por `message-id` (con o sin `<>`) y registra `delivered` (entrega), `unique_opened` (primera apertura, sin contarla), `opened` (cuenta cada apertura) y `click` (cuenta el clic e implica apertura). Usa `ts_event` del evento cuando viene. Los eventos de baja/rebote siguen yendo a `RecordBrevoEvent`; el controlador aplica ambos.
- **Ficha del curso**: bloque «Resultados del refuerzo» (enviados, entregados, abiertos y con clic, con %) y, por persona, «N abiertos · M clics».
- **Página de la secuencia**: bajo cada día, `N env. · X% abiertos · Y% clics` sumando **todos los cursos** (los 30 correos son los mismos para todos, así se ve cuáles funcionan).
- **Campañas**: `BrevoClient::campaignStats()` lee `GET /emailCampaigns/{id}?statistics=globalStats` (abiertos = vistas únicas, con clic = personas que hicieron clic, rebotes = duros + blandos). `/crm/newsletter` las muestra con sus % y un botón «Actualizar métricas».

### Tareas y actividades
- En la ficha de la empresa: sección «Tareas» (título, día, responsable —por defecto quien la crea—, completar/reabrir/eliminar) y las actividades aceptan «Cuándo ocurrió» (hora de México, no futura).
- Página `/crm/tasks`: Vencidas / Hoy / Próximas / Sin fecha, filtro Mías/Todas. Las tarjetas del pipeline muestran «N tareas vencidas».
- `crm:send-task-digest` avisa por correo (notificación `TaskDigest`, por cola) a cada persona del equipo con tareas de hoy o vencidas; no envía nada si no hay tareas, ni a tareas sin responsable.

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

## 5. Lo que falta / hoja de ruta

Orden recomendado: **6A → 6B → 6C**, luego D, E, F y G según prioridad. 6A destraba todo lo demás porque el usuario ya tiene el diseño y el texto del primer correo.

### 6A. Correos como Blade con personalización (EN CURSO: faltan los días 2–30)

**Hecho**: layout, `dia-01`, render, envío por HTML con baja con un clic, vista previa, prueba a uno mismo y transición con plantillas de Brevo (ver «Correos de la secuencia (Blade)» en la sección 4). El HTML original del día 1 está en `docs/crm-email-referencia-dia-01.html`.

**Falta**: crear `dia-02.blade.php` … `dia-30.blade.php` con el contenido que pase el usuario (texto o el HTML de Brevo de cada día).
- **Cómo convertir un día**: copiar `dia-01.blade.php`; cambiar `asunto` (patrón: `Actividad {$dia} de {$total}: <título>`), `titulo`, `preheader` y el `contenido`. Pasar el HTML de Brevo a HTML simple: quitar `style`/`class`/`<div>`/tablas, dejar `<p>`, `<h3>`, `<ol>`/`<ul>`, `<strong>`, `<a href>`; los `<p><br></p>` vacíos sobran (el layout ya separa párrafos). Reemplazar lo personal por variables (`¡Hola {{ $nombre }}!`, `día {{ $dia }} de {{ $total }}`) y el cierre por `@if ($siguiente) … #{{ $siguiente }} @endif`.
- **Reglas** que el test `every day with a view renders a complete email` ya exige a cada vista: asunto no vacío, sin `{{`/`@yield`/`@section` sin resolver, **una sola imagen** (el logo; sin imágenes de terceros como Pexels) y el nombre y el enlace de baja presentes.
- **Al terminar cada tanda**: revisar cada día con «Vista previa» y «Enviarme prueba» en `/crm/sequences/1`; ese día deja de usar su plantilla de Brevo en cuanto exista su vista.
- **Cuando existan los 30**: limpieza posterior (retirar `brevo_template_id`, la caja de pegado de IDs y el envío por `sendTemplate`), actualizar tests y esta sección.
- **Si algún día necesita algo distinto del diseño** (un botón, otra imagen), agregarlo como pieza opcional del layout en vez de HTML suelto en la vista.

**Pendiente de decidir con el usuario**: si el logo se queda en la CDN de Brevo o se aloja en el repo (`public/images/emails/`, requiere `APP_URL` público en producción).

### 6B. Piloto real
Con 6A listo y las claves en el `.env` (sección 6): un curso con **una sola persona (el propio usuario)**, fecha de inicio hoy o mañana; revisar llegada, variables, enlace de baja, métricas por el webhook y que no haya duplicados. Probar también una campaña del newsletter a una lista chica. Solo después, el primer grupo real.

### 6C. Portada del CRM
Hoy `/dashboard` (solo administradores) no muestra nada útil para el CRM. Una portada con: empresas por etapa (conteos), tareas vencidas y de hoy, refuerzos activos y cuántas personas van en cada uno, correos enviados hoy / fallidos por revisar / omitidos, contactos pendientes del newsletter y las últimas campañas. Sin dependencias nuevas (tarjetas Flux). Decidir si es la página de inicio de `/crm` o una ruta aparte.

### 6D. Conectar las entradas de leads
El endpoint `POST /api/crm/leads` ya existe y está probado. Falta dejar ejemplos listos y conectados: `fetch` para el formulario del sitio y la landing, nodo «HTTP Request» de n8n para WhatsApp (mapeando `source`), y cómo guardar `CRM_INTAKE_TOKEN` fuera del código del sitio (idealmente llamar desde el servidor, no desde el navegador). Considerar un campo trampa (honeypot) y UTM/origen más fino si hace falta.

### 6E. Migrar lo que va a medias en n8n / Google Sheets
Importador CSV de **suscripciones en curso** (columnas: email, curso, día en que va). Crea la suscripción y marca como ya enviados (sin reenviar) los días anteriores, dejando pendientes los siguientes. Hacerlo curso por curso, apagando antes el flujo equivalente en n8n para no duplicar.

### 6F. Reportes comerciales
Origen de leads que más convierte, tiempo promedio por etapa, empresas perdidas y por qué (`lost_reason`), tasa de propuesta → aceptado, rendimiento por responsable. Consultas sobre datos que ya existen (`stage_changed_at`, `source`, `lost_reason`); un historial de cambios de etapa (tabla nueva) permitiría medir tiempos reales.

### 6G. Revisión, PR y producción
Revisar el conjunto de la rama (`git log --oneline master..feat/crm`), abrir el PR hacia `master` (**el usuario pidió no subir ramas por ahora**: confirmar antes), y dejar en el servidor: variables, worker de cola supervisado, scheduler (cron), copias de seguridad de la BD y `APP_URL` público (para imágenes y enlaces de baja).

### Backlog sin fecha
- Alertas o resumen cuando suban los rebotes/bajas o falle una racha de envíos.
- Comodines (destinatarios extra que reciben todos los correos) y roles `admin`/`seller` con permisos reales (hoy todos ven y editan todo).
- Vínculo contacto ↔ `User` de Objeción Cero (ya existe `crm_contacts.user_id`).
- Recuperar envíos atascados en `queued` si un worker muere (hoy no hay barrido automático).
- Recordatorios por otros canales (WhatsApp, notificación en la app) si el correo diario no basta.

---

## 6. Puesta en marcha (operación)

Nada de esto está configurado en el `.env` local del usuario todavía.

1. **Variables** (`.env`; plantillas vacías en `.env.example`):
   - `CRM_INTAKE_TOKEN`: token largo y aleatorio para `POST /api/crm/leads`.
   - `BREVO_API_KEY`: clave de la API de Brevo (sin ella los envíos fallan con «Falta configurar BREVO_API_KEY» y se pueden reintentar después).
   - `CRM_BREVO_WEBHOOK_TOKEN`: token largo y aleatorio para el webhook.
   - `CRM_BREVO_SENDER_NAME` y `CRM_BREVO_SENDER_EMAIL`: remitente de los correos con vista propia (por defecto «B2B Sales Pro» y `cursos@b2bsalespro.mx`, ya verificado en Brevo).
   - `CRM_EMAIL_LOGO_URL` (opcional): URL del logo de los correos.
   - `CRM_BREVO_NEWSLETTER_LIST`: nombre de la lista (por defecto `newsletter`). **La lista debe existir en Brevo** (Contactos → Listas).
2. **Worker de cola**: `QUEUE_CONNECTION=database`, así que debe haber un `php artisan queue:work` corriendo (en local, `composer run dev` o equivalente; en producción, un proceso supervisado).
3. **Scheduler**: debe correr `php artisan schedule:work` (local) o el cron `* * * * * php artisan schedule:run` (producción); sin él no se encolan los envíos ni sale el resumen diario de tareas.
4. **Webhook en Brevo**: URL `https://<dominio>/api/crm/brevo/webhook` con encabezado `Authorization: Bearer <CRM_BREVO_WEBHOOK_TOKEN>` y los eventos hard bounce, blocked, spam, unsubscribed e invalid email (bajas/rebotes) **más delivered, opened, unique opened y click (métricas)**; son transaccionales y, para las campañas, unsubscribe y hard bounce del webhook de marketing (si la interfaz de Brevo no permite encabezados, crear el webhook por su API).
5. **Contenido de la secuencia**: los días con vista salen con el Blade del repo; para los días que aún no la tienen, pegar su ID de plantilla de Brevo en `/crm/sequences/{id}`. **Enviarme prueba** de un día confirma que el remitente y la clave de Brevo funcionan antes de activar nada.
   **Correo del resumen de tareas**: usa el mailer de Laravel (`MAIL_*`); en producción apuntarlo al SMTP de Brevo u otro proveedor.
6. **Primera prueba**: con la clave real, abrir `/crm/newsletter` (debe mostrar la lista y los pendientes), sumar un contacto tuyo y mandar una campaña de prueba a una lista chica antes de usar la real.
7. **Primera prueba del refuerzo**: un curso con 1 inscrito (tu propio correo) y fecha de inicio hoy o mañana; revisar la llegada, los parámetros y que no haya duplicados.
8. Cuidado en local con `BREVO_API_KEY` real: no activar refuerzos ni sincronizar al newsletter datos de demo (correos `*.test` rebotan y dañan la reputación del remitente). El curso demo está impartido y con 3 inscritos: con una clave real, «Sumar al newsletter» los mandaría a Brevo.

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
- **Los enlaces de baja están firmados con el esquema y el host**: en local (sitio por `http://`) una URL generada con `APP_URL=https://…` da 403; en producción (todo `https`) no hay problema. Para probar en local, generarla con `URL::forceScheme('http')`.
- Un `abort(404)` dentro de una acción de Livewire se ve en `Livewire::test` como `->assertStatus(404)`, no como excepción.
- Los test de `ReinforcementEmailTest` usan el día 100 como «día sin vista»: no crear una vista `dia-100`.
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
2. Tomar el siguiente punto de la sección 5: **6A** (convertir los correos 2–30 a vistas Blade en cuanto el usuario pase su contenido; el flujo está en esa sección) y, con eso listo, **6B** (piloto real).
3. Implementar con tests, Pint y PHPStan; verificar en el navegador cuando haya cambios de interfaz.
4. Actualizar las secciones **3 (Estado)**, **4 (Lo que ya existe)** y **5/6** de este archivo y hacer commit con mensaje `feat:` / `fix:` en español.
