# Onboarding de la beta gratuita (25-50 usuarios)

Contexto: Objeción Cero se está regalando a un grupo controlado de vendedores/managers
para recibir feedback antes de cobrar (ver [`objecion-cero-beta-todo.md`](objecion-cero-beta-todo.md) para el
plan completo por fases).

## El registro público está cerrado a propósito

`Features::registration()` está comentado en `config/fortify.php`. Esto es intencional:
en esta etapa no queremos que cualquiera que encuentre la URL cree una cuenta y use el
producto gratis sin límite — queremos controlar exactamente quién entra, para poder
darles seguimiento y medir contra la lista real de invitados.

Efecto: la ruta `/register` ya no existe (404) y el link "Sign up" desaparece del login
automáticamente (`resources/views/pages/auth/login.blade.php` ya lo condiciona con
`@if (Route::has('register'))`, igual que ya hacía `welcome.blade.php`).

Cuando termine la beta y decidan un modelo de cobro, esto se revierte fácil:
descomentar la línea en `config/fortify.php` (o reemplazarla por un flujo de invitación
con token, si en ese momento se justifica la inversión).

## Cómo dar de alta a un usuario de la beta

No hay UI de invitación (deliberado — ver la sesión de /llm-council sobre pago único:
construir eso ahora sería trabajo prematuro para 25-50 personas). El flujo es manual:

1. Entrar a la consola de la app:
   ```bash
   lerd console tinker
   ```
2. Crear el usuario con el email del invitado:
   ```php
   $user = \App\Models\User::create([
       'name' => 'Nombre del invitado',
       'email' => 'correo@invitado.com',
       'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
       'email_verified_at' => now(),
   ]);
   ```
   La contraseña es aleatoria y desechable a propósito: el invitado nunca la usa,
   entra directo por el link de "Olvidé mi contraseña".
3. Enviarle al invitado el link de login (`https://<dominio>/login`) y decirle que use
   "¿Olvidaste tu contraseña?" con su correo para poner la suya. Eso reutiliza el flujo
   de reset de Fortify que ya existe, sin construir nada nuevo.
   - En local, los correos de reset se ven en Mailpit (servicio ya declarado en
     `.lerd.yaml`).
   - En producción, confirmar que el `MAIL_*` de `.env` esté configurado antes de
     invitar al primer lote.

## Buenas prácticas para el lanzamiento (resumen — detalle completo en [`objecion-cero-beta-todo.md`](objecion-cero-beta-todo.md))

- Invitar primero a 10-12 personas, no las 25-50 de golpe.
- Avisarles la fecha de corte del acceso gratuito.
- Guardar la lista de invitados (nombre, email, fecha de invitación) en algún lugar
  fuera del código — es la base para las encuestas de check-in y salida.
