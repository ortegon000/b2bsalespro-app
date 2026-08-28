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

    public function dotColor(): string
    {
        return match ($this) {
            self::Real => 'oklch(0.55 0.11 155)',
            self::Duda => 'oklch(0.72 0.13 78)',
            self::Excusa => 'oklch(0.58 0.14 28)',
        };
    }

    public function textColor(): string
    {
        return match ($this) {
            self::Real => 'oklch(0.80 0.12 155)',
            self::Duda => 'oklch(0.82 0.13 82)',
            self::Excusa => 'oklch(0.72 0.15 30)',
        };
    }

    public function bgColor(): string
    {
        return match ($this) {
            self::Real => 'oklch(0.55 0.11 155 / .16)',
            self::Duda => 'oklch(0.72 0.13 78 / .16)',
            self::Excusa => 'oklch(0.58 0.14 28 / .18)',
        };
    }

    public function headGradient(): string
    {
        return match ($this) {
            self::Real => 'linear-gradient(160deg,#12241c,#0f1319)',
            self::Duda => 'linear-gradient(160deg,#241f10,#0f1319)',
            self::Excusa => 'linear-gradient(160deg,#241413,#0f1319)',
        };
    }
}
