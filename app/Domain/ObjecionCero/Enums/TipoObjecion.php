<?php

namespace App\Domain\ObjecionCero\Enums;

enum TipoObjecion: string
{
    case Real = 'real';
    case Duda = 'duda';
    case Excusa = 'excusa';

    public function label(): string
    {
        return match ($this) {
            self::Real => 'Objeción real',
            self::Duda => 'Duda',
            self::Excusa => 'Excusa',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Real => 'Real',
            self::Duda => 'Duda',
            self::Excusa => 'Excusa',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Real => 'Da datos concretos y pide detalles. Quiere comprar, algo específico se lo impide.',
            self::Duda => 'Pregunta "¿y si…?", pide garantías o compara. Teme equivocarse.',
            self::Excusa => 'Es vago, evita agendar, habla en futuro indefinido. No quiere decirte que no.',
        };
    }

    public function accion(): string
    {
        return match ($this) {
            self::Real => 'Resuélvela y cierra.',
            self::Duda => 'Da certeza, no argumentos nuevos.',
            self::Excusa => 'No la resuelvas: descubre la objeción real detrás.',
        };
    }
}
