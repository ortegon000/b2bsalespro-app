@extends('emails.crm.reinforcement.layout')

@section('asunto', "Actividad {$dia} de {$total}: Define tu Norte")
@section('preheader', 'Tu primera misión: decidir cuánto dinero quieres ganar.')
@section('titulo', "Actividad {$dia} de {$total}")

@section('contenido')
    <p>¡Hola {{ $nombre }}!</p>
    <p>Bienvenido/a al <strong>día {{ $dia }} de {{ $total }}</strong> de tu programa de refuerzo de <strong>ventas B2B</strong>.</p>
    <p>Durante el próximo mes, te enviaremos una actividad diaria diseñada para transformar lo que aprendiste en el curso con el equipo de <strong>B2B Sales Pro</strong>. No necesitas enviarnos tus respuestas; estos ejercicios son 100% para ti y para tu crecimiento.</p>
    <p><strong>Tu compromiso:</strong> Dedicar 15-20 minutos intensos cada día (incluyendo fines de semana y festivos). Te aseguro que <strong>la inversión valdrá la pena</strong>.</p>

    <h3>🎯 La Misión de Hoy: Define tu Norte</h3>
    <p>Si estás en ventas, es porque sabes que aquí las posibilidades de ingresos son ilimitadas. Pero para llegar a la cima, primero necesitas saber qué montaña estás escalando.</p>
    <p><strong>Tu primera tarea es determinar cuánto dinero quieres ganar.</strong><br>No estamos hablando de "ganar más", sino de una cifra concreta. Sin este número, el resto del programa no tendrá brújula.</p>
    <p><strong>Instrucciones:</strong><br>Define tu meta de ingresos por comisiones (mensual o anual) siguiendo estos tres criterios:</p>
    <ol>
        <li><strong>DEFINIDA:</strong> Un número exacto (ej. "$50,000 MXN").</li>
        <li><strong>ESPECÍFICA:</strong> Que sepas exactamente de dónde vendrá.</li>
        <li><strong>RETADORA PERO ALCANZABLE:</strong> Que te obligue a estirarte, pero que sea realista para mantener la motivación.</li>
    </ol>
    <p>Tómate tu tiempo hoy. Escribe esa cifra en un lugar donde la veas a diario.</p>
    <p>¡A trabajar! Mañana utilizaremos ese número para construir tu plan.</p>
    @if ($siguiente)
        <p>Nos vemos en el correo #{{ $siguiente }}.</p>
    @endif
@endsection
