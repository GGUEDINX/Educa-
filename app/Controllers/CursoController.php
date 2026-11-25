<?php

namespace App\Controllers;

use App\Models\Curso;

class CursoController
{
    private Curso $cursoModel;

    public function __construct()
    {
        $this->cursoModel = new Curso();
    }

    public function listarParaUsuario(int $usuarioId): array
    {
        try {
            return $this->cursoModel->todosComProgresso($usuarioId);
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function recentesParaUsuario(int $usuarioId, int $limite = 3): array
    {
        try {
            return $this->cursoModel->recentesComProgresso($usuarioId, $limite);
        } catch (\PDOException $e) {
            return [];
        }
    }
}
