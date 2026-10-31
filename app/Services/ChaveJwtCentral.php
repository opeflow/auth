<?php

namespace App\Services;

class ChaveJwtCentral
{
    public static function obter(): ?string
    {
        $configurada = (string) config('app.key');
        $chave = str_starts_with($configurada, 'base64:')
            ? base64_decode(substr($configurada, 7), true)
            : $configurada;

        if (!is_string($chave) || strlen($chave) < 32) {
            return null;
        }

        return hash('sha256', $chave, true);
    }
}
