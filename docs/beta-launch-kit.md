# Kit de lanzamiento de la beta (Fase 1 de `TODO's.md`)

Contenido listo para usar en la Fase 1 ("Preparar la beta"). Lo que falta en cada punto
requiere una decisión o un dato que solo el usuario tiene (fecha real, lista de contactos
reales) — se marca explícitamente.

## Fecha de corte — propuesta

En vez de una fecha de calendario fija para todos (penaliza a quien entra en la segunda
oleada, ver Fase 2), se propone una ventana **rodante de 30 días por usuario**, contada
desde que cada quien activa su cuenta (pone su contraseña por primera vez). Es fácil de
comunicar ("gratis tus primeros 30 días") y no requiere coordinar quién entró cuándo.

Pendiente de decidir: **la fecha en la que arranca la Fase 2** (primera oleada de 10-12).
Todo lo demás de este documento no depende de esa fecha.

## Mensaje de invitación (borrador)

Pensado para WhatsApp o email, tono directo, un solo scroll:

> Hola [nombre] — te quiero regalar acceso a algo que armé: **Objeción Cero**, un manual
> de cierre para ventas B2B. Son 30 objeciones reales con la respuesta ya escrita
> (qué significa lo que te dice el cliente, qué nunca responder, el diálogo palabra por
> palabra y el cierre), más 120 preguntas, 100 frases y scripts de WhatsApp listos para
> copiar.
>
> Te lo regalo gratis por 30 días porque quiero tu feedback honesto — qué te sirve, qué
> le falta, si de verdad te ahorra tiempo antes de una llamada. En algún momento va a
> tener un precio, pero tú entras gratis y sin compromiso.
>
> Así entras: [link de login]. Usa "¿Olvidaste tu contraseña?" con tu correo
> ([correo del invitado]) para poner la tuya — así de simple.
>
> Si tienes 10 minutos en las próximas dos semanas, te voy a pedir una llamada corta para
> que me cuentes qué tal. Gracias de antemano.

Ajustar `[nombre]`, `[link de login]` y `[correo del invitado]` por invitado. Ver
[`docs/beta-onboarding.md`](beta-onboarding.md) para el alta manual de cada cuenta.

Pendiente: **la lista real de invitados** (nombre + correo + si es vendedor individual o
manager) — sale de la red directa del usuario, no se puede generar.

## Las 3 encuestas cortas

Formato sugerido: Google Forms o Typeform, 5-7 preguntas, menos de 3 minutos.

### 1 — Entrada (al aceptar la invitación)

1. ¿Cuál es tu rol? (Vendedor individual / Manager de ventas / Otro)
2. ¿Cuántas llamadas o reuniones de venta tienes en una semana típica? (rango)
3. ¿Cuál es la objeción que más te cuesta manejar hoy?
4. ¿Cómo resuelves objeciones actualmente? (Improviso / Notas propias / Coaching de mi
   manager / Otra herramienta — ¿cuál?)
5. ¿Qué esperarías obtener de Objeción Cero en las próximas semanas?

### 2 — Check-in día 7-10

1. ¿Cuántas veces usaste Objeción Cero esta semana? (0 / 1-2 / 3-5 / 6+)
2. ¿En qué momento la usaste? (Antes de una llamada / Durante / Por WhatsApp con el
   cliente / No la he usado)
3. ¿Qué sección te ha sido más útil? (Banco de fichas / Preguntas / Frases / Cierres /
   WhatsApp / Checklists / Plantilla)
4. ¿Hubo alguna objeción real que enfrentaste y que NO estaba en el banco de fichas?
   ¿Cuál?
5. Del 1 al 5, ¿qué tan fácil fue encontrar lo que buscabas cuando lo necesitabas?
6. ¿Qué le falta o qué cambiarías?

### 3 — Salida día 21-30

1. En las últimas dos semanas, ¿seguiste usando Objeción Cero? (Sí, seguido / Sí, poco /
   Dejé de usarla — ¿por qué?)
2. ¿Sientes que te ayudó a cerrar o avanzar alguna venta concreta? Cuéntanos brevemente.
3. **Pregunta de precio directa:** si hoy tuvieras que pagar por seguir usando Objeción
   Cero, ¿pagarías...? (No pagaría / Sí, hasta $19/mes / Sí, hasta $39/mes / Sí, hasta
   $59/mes o más)
4. Del 0 al 10, ¿qué tan probable es que recomiendes Objeción Cero a un colega vendedor?
5. ¿Conoces a alguien a quien le sirva? (nombre + contacto, opcional — esto es la señal
   de referido orgánico de la Fase 4)
6. ¿Algo más que quieras decirnos?

La pregunta 3 de la encuesta de salida y la pregunta 5 son los dos datos que la Fase 4
usa directamente ("cuántos dijeron sí pagaría $X" y "referidos orgánicos").

## Lo que ya quedó construido (Fase 0, ítems 5 y 6)

- Botón "💬 Feedback" visible en todas las páginas de Objeción Cero — guarda el mensaje
  en la tabla `feedback` junto con la página desde la que se envió.
- Registro de visitas por sección y por ficha en la tabla `content_views`.
- Comando `php artisan objecion-cero:usage-report [--days=7]` — corre en la consola
  (`lerd console objecion-cero:usage-report`) y da: % de usuarios activos en la ventana
  elegida, visitas por sección, fichas más consultadas y conteo de feedback recibido. Es
  la base para "revisar semanalmente los logs de uso" (Fase 3) y para medir retención en
  semana 3-4 (Fase 4, con `--days=28` o similar).
