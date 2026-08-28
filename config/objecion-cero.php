<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Prefijo de rutas
    |--------------------------------------------------------------------------
    |
    | Segmento de URL bajo el que se sirve el manual de Objeción Cero
    | (p. ej. "objecion-cero" para https://tu-dominio/objecion-cero).
    | No es el nombre final del producto, así que se configura por entorno.
    |
    */

    'route_prefix' => env('OBJECION_CERO_ROUTE_PREFIX', 'objecion-cero'),

];
