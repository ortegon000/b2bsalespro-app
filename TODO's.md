# Objeción Cero — Camino al "regalo" (beta gratuita)

Contexto: dos sesiones de /llm-council (2026-08-28) concluyeron que el producto no está listo
para venderse por suscripción (falta comprador manager + loop recurrente) ni por pago único
todavía (falta checkout, distribución, validación de WTP). Plan acordado con el usuario:

MVP (sin pago) → regalo a 25-50 usuarios → feedback estructurado → versión que sí se cobra.

Detalle completo del análisis en la memoria del proyecto (carpeta memory/ del usuario):
`project_objecion_cero_subscription_readiness.md` y `project_objecion_cero_one_time_purchase.md`.

## Fase 0 — Producto (MVP técnico, sin pago)

1. [x] Agregar búsqueda funcional a **cierres, frases, whatsapp** (mismo patrón
   `wire:model.live.debounce` que ya usa banco-fichas). *Nota: "plantilla" se excluyó a
   propósito — es un formulario personal secuencial de pocos pasos, no un catálogo para
   buscar; forzar un buscador ahí no aporta valor.*
2. [x] Verificar y ajustar responsive/mobile en las vistas de Objeción Cero.
3. [x] Confirmar que el registro/login no tenga fricción innecesaria para un usuario nuevo
   que llega por invitación. Resuelto cerrando el registro público (`/register` ahora 404,
   `Features::registration()` comentado en `config/fortify.php`) para que solo entre gente
   invitada durante la beta, y documentando el flujo de alta manual en
   [`docs/beta-onboarding.md`](docs/beta-onboarding.md).
4. [x] Revisar volumen/calidad de contenido actual (~30 fichas, 15 cierres, etc.) — decidir
   si es suficiente para sentirse "completo" en la beta o si vale la pena sumar fichas antes
   de lanzar. **Decisión: es suficiente, no bloquear el lanzamiento por esto.** Conteo real:
   30 fichas repartidas de forma pareja en 6 categorías (6/5/5/4/5/5), 15 cierres, 10 frases,
   25 scripts de WhatsApp, 120 preguntas (6 grupos), 3 checklists. Es un set inicial completo
   y variado, no un esqueleto vacío. Mejor usar el feedback real de la beta (ítem 5) y los
   logs de uso (ítem 6) para decidir *qué* fichas faltan en vez de adivinar de antemano.
5. [x] Agregar un botón/enlace de "dejar feedback" visible dentro de la app (no solo un
   formulario externo que nadie recuerda visitar). Implementado: botón flotante "💬 Feedback"
   en todas las páginas de Objeción Cero (componente Livewire `objecion-cero-feedback`),
   guarda el mensaje en la tabla `feedback` junto con la página de origen.
6. [x] Agregar instrumentación mínima de uso: log de qué fichas/secciones se visitan y
   cuántas veces (hoy solo existe `checklist_progress`). Implementado: tabla `content_views`
   (middleware `LogObjecionCeroView` registra cada visita de sección; `open()` en el banco
   de fichas registra cada ficha abierta) + comando `objecion-cero:usage-report` para revisar
   el resumen (visitas por sección, fichas más consultadas, % de usuarios activos, feedback
   recibido).

## Fase 1 — Preparar la beta

Borradores listos en [`docs/beta-launch-kit.md`](docs/beta-launch-kit.md): mensaje de
invitación, propuesta de fecha de corte y las 3 encuestas cortas ya redactadas
pregunta por pregunta. Lo que sigue marcado como pendiente necesita un dato o una
decisión que solo el usuario tiene — no se puede generar desde el código.

- [ ] Definir la fecha de corte explícita del regalo. Propuesta en el kit: ventana
  rodante de 30 días por usuario (no una fecha fija de calendario) — falta confirmarla.
- [x] Redactar el mensaje de invitación (qué es, qué se espera del usuario, que después
  habrá un precio). Borrador completo en el kit, solo falta rellenar nombre/correo por
  invitado.
- [ ] Armar la lista de candidatos desde la red directa (no anuncios fríos). *Requiere
  los contactos reales del usuario — no es generable.*
- [ ] Segmentar la lista: vendedores individuales **y** algunos managers de venta.
  *Depende de tener la lista del punto anterior.*
- [ ] Separar en dos grupos: 8-12 para entrevista personal, resto para señal de
  comportamiento + encuesta corta. *Depende de tener la lista.*
- [x] Preparar las 3 encuestas cortas: entrada, check-in día 7-10, salida día 21-30 (con
  pregunta directa de precio a un número concreto). Preguntas completas en el kit, listas
  para pasar a Google Forms/Typeform.

## Fase 2 — Lanzamiento por oleadas

Ejecución real (invitar gente, esperar semanas) — nada de esto es generable desde el
código, y enviar invitaciones reales requiere que el usuario lo confirme explícitamente.
Sigue pendiente en su totalidad hasta que arranque el lanzamiento real:

- [ ] Invitar primero a 10-12 personas (no las 50 de golpe). Alta manual documentada en
  [`docs/beta-onboarding.md`](docs/beta-onboarding.md).
- [ ] Correr 1-2 semanas, revisar uso real + feedback temprano, corregir lo obvio.
- [ ] Abrir el resto (35-40) una vez corregidos los problemas evidentes.

## Fase 3 — Durante la beta (cadencia de feedback)

Cadencia de seguimiento durante las semanas de beta — depende de que la Fase 2 ya haya
arrancado con usuarios reales. La herramienta para el único punto que sí es técnico
("revisar semanalmente los logs de uso") ya está construida: `objecion-cero:usage-report`.

- [ ] Enviar encuesta de entrada al aceptar la invitación (preguntas listas en el kit).
- [ ] Hacer las 8-12 entrevistas personales entre semana 1 y 2.
- [ ] Enviar check-in a los 7-10 días a todo el grupo (preguntas listas en el kit).
- [ ] Revisar semanalmente los logs de uso — correr
  `lerd console objecion-cero:usage-report` cada semana.
- [ ] Enviar encuesta de salida a los 21-30 días con la pregunta de precio concreta
  (preguntas listas en el kit).

## Fase 4 — Criterio para pasar a versión de pago

Requiere datos reales de una beta que todavía no corrió — no hay nada que medir aún.
Lo que sí queda listo es cómo se va a medir cuando llegue el momento:

- [ ] Medir % de usuarios activos en semana 3-4 (retención real). Correr
  `objecion-cero:usage-report --days=28` — ya calcula el %.
- [ ] Contar cuántos dijeron explícitamente "sí pagaría $X" en la encuesta de salida
  (pregunta 3 de la encuesta de salida en el kit).
- [ ] Contar referidos orgánicos como señal de boca a boca (pregunta 5 de la encuesta de
  salida en el kit).
- [ ] Con esos tres datos, decidir precio de lanzamiento (referencia: $39-49 con el mínimo
  indispensable, según sesión de pago único del consejo).

## Notas de verificación (no asumir, revisar código)

- El 2FA/passkeys **no son obligatorios** — son features opcionales de Fortify
  (`config/fortify.php`), y las rutas de Objeción Cero solo exigen `auth`, no `verified`.
  No hay nada que "quitar" ahí.
- La búsqueda ya existía en banco-fichas antes de este plan; faltaba en el resto del
  catálogo (resuelto en el ítem 1 de la Fase 0).
