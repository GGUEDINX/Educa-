<?php

namespace App\Controllers;

use App\Models\Curso;
use App\Models\QuizAlternativa;
use App\Models\ProgressoCurso;
use App\Models\ProgressoAula;
use App\Models\Certificado;
use App\Models\Usuario;

class QuizController
{
    private Curso $cursoModel;
    private QuizAlternativa $alternativaModel;
    private ProgressoCurso $progressoModel;
    private ProgressoAula $progressoAulaModel;
    private Certificado $certificadoModel;
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->cursoModel = new Curso();
        $this->alternativaModel = new QuizAlternativa();
        $this->progressoModel = new ProgressoCurso();
        $this->progressoAulaModel = new ProgressoAula();
        $this->certificadoModel = new Certificado();
        $this->usuarioModel = new Usuario();
    }

    public function buscarCurso(int $cursoId): ?array
    {
        return $this->cursoModel->buscarPorId($cursoId);
    }

    /** O funcionário só pode fazer o quiz depois de assistir todas as aulas do curso. */
    public function podeAcessarQuiz(int $usuarioId, int $cursoId): bool
    {
        return $this->progressoAulaModel->assistiuTodasAsAulas($usuarioId, $cursoId);
    }

    public function montarQuiz(int $cursoId): array
    {
        return $this->alternativaModel->montarQuizDoCurso($cursoId);
    }

    /**
     * Corrige as respostas enviadas. Se o usuário gabaritar, registra o progresso
     * e emite o certificado automaticamente.
     *
     * @return array{aprovado: bool, mensagem: string, acertos: int, total: int}
     */
    public function corrigirEEmitirCertificado(int $usuarioId, int $cursoId, array $curso, array $quizCompleto, array $respostasUsuario): array
    {
        $respostasCorretas = 0;
        $totalPerguntas = count($quizCompleto);

        foreach ($quizCompleto as $item) {
            $perguntaId = $item['pergunta']['id'];
            $alternativaEscolhida = isset($respostasUsuario[$perguntaId]) ? (int)$respostasUsuario[$perguntaId] : 0;

            foreach ($item['alternativas'] as $alt) {
                if ($alt['id'] === $alternativaEscolhida && $alt['e_correta']) {
                    $respostasCorretas++;
                }
            }
        }

        if ($respostasCorretas !== $totalPerguntas) {
            return [
                'aprovado' => false,
                'mensagem' => "Poxa, quase lá! Você acertou $respostasCorretas de $totalPerguntas perguntas. Tente responder novamente!",
                'acertos' => $respostasCorretas,
                'total' => $totalPerguntas,
            ];
        }

        // Verifica ANTES de marcar, para não creditar pontos de novo se o funcionário refizer o quiz
        $jaEstavaConcluido = false;
        try {
            $jaEstavaConcluido = $this->progressoModel->estaConcluido($usuarioId, $cursoId);
        } catch (\PDOException $e) {
            // segue o fluxo normalmente
        }

        // Aprovado: registra progresso e gera o certificado
        $this->progressoModel->marcarConcluido($usuarioId, $cursoId);

        // Credita os pontos de recompensa do curso (só na primeira conclusão)
        try {
            if (!$jaEstavaConcluido) {
                $pontosDoCurso = (int)($curso['pontos_recompensa'] ?? 0);
                if ($pontosDoCurso > 0) {
                    $this->usuarioModel->creditarPontos($usuarioId, $pontosDoCurso);
                }
            }
        } catch (\PDOException $e) {
            // Coluna/tabela de pontos ainda não existe (migração não rodada) - ignora
        }

        $codigoValidacao = "EDUCA-" . str_pad((string)$usuarioId, 3, '0', STR_PAD_LEFT) . "-"
            . str_pad((string)$cursoId, 3, '0', STR_PAD_LEFT) . "-" . strtoupper(substr(md5(uniqid()), 0, 6));

        $this->certificadoModel->criar([
            'usuario_id' => $usuarioId,
            'curso_nome' => $curso['titulo'],
            'carga_horaria' => $curso['carga_horaria'],
            'data_conclusao' => date('Y-m-d'),
            'codigo_validacao' => $codigoValidacao,
        ]);

        return [
            'aprovado' => true,
            'mensagem' => "Parabéns! Você gabaritou o quiz e foi aprovado no treinamento. Seu certificado já está disponível!",
            'acertos' => $respostasCorretas,
            'total' => $totalPerguntas,
        ];
    }
}
