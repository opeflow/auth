<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdministradorSeeder extends Seeder
{
    public function run(): void
    {
        $email = mb_strtolower(trim((string) config('painel.email')));
        $senha = (string) config('painel.senha');

        if ($email === '' || $senha === '') {
            throw new RuntimeException('Configure ADMIN_EMAIL e ADMIN_PASSWORD antes de criar o administrador.');
        }

        $agora = now();
        $existente = DB::table('administradores')->where('email', $email)->first();
        DB::table('administradores')->updateOrInsert(
            ['email' => $email],
            [
                'nome' => (string) config('painel.nome', 'Administrador'),
                'senha_hash' => Hash::make($senha),
                'updated_at' => $agora,
                'created_at' => $existente?->created_at ?? $agora,
            ],
        );
    }
}
