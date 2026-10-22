<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el nombre de usuario (CU01 permite iniciar sesión con usuario o correo).
 * El usuario es único; los usuarios que ya existían lo reciben a partir de su correo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('usuario', 50)->nullable()->unique()->after('name');
        });

        $usados = [];
        $filas = DB::table('users')->whereNull('usuario')->orderBy('id')->get(['id', 'email']);

        foreach ($filas as $fila) {
            $local = strstr($fila->email, '@', true) ?: $fila->email;
            $base = substr(preg_replace('/[^a-z0-9._-]/', '', mb_strtolower($local)), 0, 40);
            $base = $base !== '' ? $base : 'usuario';

            $nombre = $base;
            $n = 1;
            while (in_array($nombre, $usados, true) || DB::table('users')->where('usuario', $nombre)->exists()) {
                $n++;
                $nombre = $base . $n;
            }

            $usados[] = $nombre;
            DB::table('users')->where('id', $fila->id)->update(['usuario' => $nombre]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['usuario']);
            $table->dropColumn('usuario');
        });
    }
};
