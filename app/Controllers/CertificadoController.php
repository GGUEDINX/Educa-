<?php

namespace App\Controllers;

use App\Models\Certificado;

class CertificadoController
{
    private Certificado $certificadoModel;

    public function __construct()
    {
        $this->certificadoModel = new Certificado();
    }

    public function listarDoUsuario(int $usuarioId): array
    {
        try {
            return $this->certificadoModel->porUsuario($usuarioId);
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function buscarParaEmissao(int $id, int $usuarioId): ?array
    {
        return $this->certificadoModel->buscarPorIdEUsuario($id, $usuarioId);
    }
}
