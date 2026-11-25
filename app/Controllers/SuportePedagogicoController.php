<?php

namespace App\Controllers;

use App\Models\DuvidaCurso;
use App\Models\FeedbackCurso;
use App\Models\Curso;

class SuportePedagogicoController
{
    private DuvidaCurso $duvidaModel;
    private FeedbackCurso $feedbackModel;
    private Curso $cursoModel;

    public function __construct()
    {
        $this->duvidaModel = new DuvidaCurso();
        $this->feedbackModel = new FeedbackCurso();
        $this->cursoModel = new Curso();
    }

    public function listarCursos(): array
    {
        try {
            return $this->cursoModel->todos();
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function contarNaoLidas(int $usuarioId): int
    {
        return $this->duvidaModel->contarRespondidasNaoLidas($usuarioId);
    }

    /** @return array{mensagem: string, tipo: string} */
    public function enviarDuvida(int $usuarioId, int $cursoId, string $pergunta): array
    {
        if (empty($pergunta) || !$cursoId) {
            return ['mensagem' => 'Escolha o curso e escreva sua dúvida.', 'tipo' => 'error'];
        }
        try {
            $this->duvidaModel->criar($usuarioId, $cursoId, $pergunta);
            return ['mensagem' => 'Dúvida enviada! A equipe pedagógica vai te responder por aqui.', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Não foi possível enviar sua dúvida. É necessário rodar a migração do banco de dados.', 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function responderDuvida(int $id, int $respondidoPor, string $resposta): array
    {
        if (empty($resposta)) {
            return ['mensagem' => 'Escreva uma resposta antes de enviar.', 'tipo' => 'error'];
        }
        try {
            $this->duvidaModel->responder($id, $respondidoPor, $resposta);
            return ['mensagem' => 'Resposta enviada ao colaborador!', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Erro ao responder a dúvida.', 'tipo' => 'error'];
        }
    }

    /** @return array{mensagem: string, tipo: string} */
    public function enviarFeedback(int $usuarioId, int $cursoId, int $nota, string $comentario): array
    {
        if (!$cursoId || $nota < 1 || $nota > 5) {
            return ['mensagem' => 'Escolha o curso e dê uma nota de 1 a 5.', 'tipo' => 'error'];
        }
        try {
            $this->feedbackModel->criar($usuarioId, $cursoId, $nota, $comentario);
            return ['mensagem' => 'Obrigado pelo seu feedback!', 'tipo' => 'success'];
        } catch (\PDOException $e) {
            return ['mensagem' => 'Não foi possível enviar seu feedback. É necessário rodar a migração do banco de dados.', 'tipo' => 'error'];
        }
    }

    public function montarPainel(string $tipoUsuario, int $usuarioId, int $empresaId): array
    {
        $ehEquipeSuporte = in_array($tipoUsuario, ['admin_empresa', 'pedagogia'], true);
        $duvidasErro = false;
        $feedbackErro = false;

        try {
            $duvidas = $ehEquipeSuporte
                ? $this->duvidaModel->porEmpresa($empresaId)
                : $this->duvidaModel->porUsuario($usuarioId);
        } catch (\PDOException $e) {
            $duvidas = [];
            $duvidasErro = true;
        }

        // O funcionário viu a página: marca as respostas como lidas (apaga o "sininho")
        if (!$ehEquipeSuporte) {
            $this->duvidaModel->marcarTodasComoLidas($usuarioId);
        }

        try {
            $feedbacks = $ehEquipeSuporte ? $this->feedbackModel->porEmpresa($empresaId) : [];
            $mediasPorCurso = $ehEquipeSuporte ? $this->feedbackModel->mediaNotaPorCurso($empresaId) : [];
        } catch (\PDOException $e) {
            $feedbacks = [];
            $mediasPorCurso = [];
            $feedbackErro = true;
        }

        return [
            'duvidas' => $duvidas,
            'feedbacks' => $feedbacks,
            'medias_por_curso' => $mediasPorCurso,
            'erro_tabela' => $duvidasErro || $feedbackErro,
        ];
    }
}
