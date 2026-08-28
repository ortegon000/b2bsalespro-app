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
4. [ ] Revisar volumen/calidad de contenido actual (~30 fichas, 15 cierres, etc.) — decidir
   si es suficiente para sentirse "completo" en la beta o si vale la pena sumar fichas antes
   de lanzar.
5. [ ] Agregar un botón/enlace de "dejar feedback" visible dentro de la app (no solo un
   formulario externo que nadie recuerda visitar).
6. [ ] Agregar instrumentación mínima de uso: log de qué fichas/secciones se visitan y
   cuántas veces (hoy solo existe `checklist_progress`).

## Fase 1 — Preparar la beta

- [ ] Definir la fecha de corte explícita del regalo (ej. "gratis por 30 días").
- [ ] Redactar el mensaje de invitación (qué es, qué se espera del usuario, que después
  habrá un precio).
- [ ] Armar la lista de candidatos desde la red directa (no anuncios fríos).
- [ ] Segmentar la lista: vendedores individuales **y** algunos managers de venta.
- [ ] Separar en dos grupos: 8-12 para entrevista personal, resto para señal de
  comportamiento + encuesta corta.
- [ ] Preparar las 3 encuestas cortas: entrada, check-in día 7-10, salida día 21-30 (con
  pregunta directa de precio a un número concreto).

## Fase 2 — Lanzamiento por oleadas

- [ ] Invitar primero a 10-12 personas (no las 50 de golpe).
- [ ] Correr 1-2 semanas, revisar uso real + feedback temprano, corregir lo obvio.
- [ ] Abrir el resto (35-40) una vez corregidos los problemas evidentes.

## Fase 3 — Durante la beta (cadencia de feedback)

- [ ] Enviar encuesta de entrada al aceptar la invitación.
- [ ] Hacer las 8-12 entrevistas personales entre semana 1 y 2.
- [ ] Enviar check-in a los 7-10 días a todo el grupo.
- [ ] Revisar semanalmente los logs de uso.
- [ ] Enviar encuesta de salida a los 21-30 días con la pregunta de precio concreta.

## Fase 4 — Criterio para pasar a versión de pago

- [ ] Medir % de usuarios activos en semana 3-4 (retención real).
- [ ] Contar cuántos dijeron explícitamente "sí pagaría $X" en la encuesta de salida.
- [ ] Contar referidos orgánicos como señal de boca a boca.
- [ ] Con esos tres datos, decidir precio de lanzamiento (referencia: $39-49 con el mínimo
  indispensable, según sesión de pago único del consejo).

## Notas de verificación (no asumir, revisar código)

- El 2FA/passkeys **no son obligatorios** — son features opcionales de Fortify
  (`config/fortify.php`), y las rutas de Objeción Cero solo exigen `auth`, no `verified`.
  No hay nada que "quitar" ahí.
- La búsqueda ya existía en banco-fichas antes de este plan; faltaba en el resto del
  catálogo (resuelto en el ítem 1 de la Fase 0).
