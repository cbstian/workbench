<?php

namespace App\Enums;

/**
 * Color palette of the panel, chosen by each user in Ajustes → Apariencia.
 *
 * The value is rendered as <html data-palette="…"> and must match a palette
 * file in resources/css/theme/palettes/ (see index.css there for the contract
 * and the steps to add one). Independent from light/dark mode.
 */
enum ColorPalette: string
{
    case Roble = 'roble';
    case Oceano = 'oceano';
    case Bosque = 'bosque';

    /**
     * Palette used for guests and for users who never chose one.
     */
    public static function default(): self
    {
        return self::Roble;
    }

    /**
     * Spanish name shown in the palette selector.
     */
    public function label(): string
    {
        return match ($this) {
            self::Roble => 'Roble',
            self::Oceano => 'Océano',
            self::Bosque => 'Bosque',
        };
    }

    /**
     * Short Spanish description shown under the name.
     */
    public function description(): string
    {
        return match ($this) {
            self::Roble => 'Nogal cálido y neutros tibios.',
            self::Oceano => 'Azul pizarra y neutros fríos.',
            self::Bosque => 'Verde salvia y neutros suaves.',
        };
    }
}
