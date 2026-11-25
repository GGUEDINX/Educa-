<?php

namespace App\Controllers;

use App\Models\Curso;
use App\Models\Aula;
use App\Models\ProgressoAula;

class AulaController
{
    /** Percentual mínimo assistido do vídeo (0-100) para liberar a conclusão. */
    private const PERCENTUAL_MINIMO_VIDEO = 85;

    private Curso $cursoModel;
    private Aula $aulaModel;
    private ProgressoAula $progressoModel;

    public function __construct()
    {
        $this->cursoModel = new Curso();
        $this->aulaModel = new Aula();
        $this->progressoModel = new ProgressoAula();
    }

    /** Extrai o ID do vídeo de uma URL do YouTube (embed, youtu.be ou watch?v=). Retorna null se não for YouTube. */
    private function extrairYoutubeId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        if (preg_match('~youtube\.com/embed/([a-zA-Z0-9_-]{6,})~', $url, $m)) return $m[1];
        if (preg_match('~youtu\.be/([a-zA-Z0-9_-]{6,})~', $url, $m)) return $m[1];
        if (preg_match('~[?&]v=([a-zA-Z0-9_-]{6,})~', $url, $m)) return $m[1];
        return null;
    }

    /**
     * Calcula se a aula pode ser concluída agora: precisa do tempo mínimo (pedagogia)
     * E, se for vídeo do YouTube, do percentual mínimo realmente assistido (via IFrame API).
     */
    private function avaliarLiberacao(array $aula, bool $jaAssistida): array
    {
        $duracaoSegundos = (int)($aula['duracao_estimada_minutos'] ?? 3) * 60;
        $youtubeId = $this->extrairYoutubeId($aula['url_video'] ?? null);

        if ($jaAssistida) {
            return ['pode_concluir' => true, 'segundos_restantes' => 0, 'duracao_segundos' => $duracaoSegundos,
                    'youtube_id' => $youtubeId, 'progresso_video' => 100];
        }

        $chaveTempo = 'aula_iniciada_' . $aula['id'];
        if (!isset($_SESSION[$chaveTempo])) {
            $_SESSION[$chaveTempo] = time();
        }
        $tempoDecorrido = time() - $_SESSION[$chaveTempo];
        $segundosRestantes = max(0, $duracaoSegundos - $tempoDecorrido);
        $tempoOk = $segundosRestantes <= 0;

        $chaveProgresso = 'aula_progresso_video_' . $aula['id'];
        $progressoVideo = $_SESSION[$chaveProgresso] ?? 0;
        $videoOk = $youtubeId === null || $progressoVideo >= self::PERCENTUAL_MINIMO_VIDEO;

        return [
            'pode_concluir' => $tempoOk && $videoOk,
            'segundos_restantes' => $segundosRestantes,
            'duracao_segundos' => $duracaoSegundos,
            'youtube_id' => $youtubeId,
            'progresso_video' => $progressoVideo,
        ];
    }

    /**
     * Monta os dados da página de aula: curso, lista de aulas (com o que está travado),
     * a aula ativa e se ela já pode ser concluída.
     */
    public function montarPagina(int $cursoId, int $aulaAtivaId, int $usuarioId): array
    {
        try {
            $curso = $this->cursoModel->buscarPorId($cursoId);
            $aulas = $this->aulaModel->porCurso($cursoId);
            $idsAssistidas = $this->progressoModel->idsAssistidasNoCurso($usuarioId, $cursoId);

            if (empty($aulas)) {
                return ['curso' => $curso, 'aulas' => [], 'aula_atual' => null, 'ids_assistidas' => [],
                        'todas_assistidas' => true, 'pode_concluir' => false, 'segundos_restantes' => 0, 'erro' => null];
            }

            // Trava sequencial: só pode acessar a próxima aula depois de concluir a anterior.
            // (usa a POSIÇÃO na lista ordenada, não o número "ordem", pra não quebrar se houver duplicidade)
            $indiceLiberado = 0; // primeira aula sempre liberada
            foreach ($aulas as $i => $a) {
                if (in_array($a['id'], $idsAssistidas)) {
                    $indiceLiberado = $i + 1;
                } else {
                    break;
                }
            }
            $indiceLiberado = min($indiceLiberado, count($aulas) - 1);
            $idAulaLiberada = $aulas[$indiceLiberado]['id'];

            // Descobre qual aula deve ser exibida
            $aulaAtual = null;
            if ($aulaAtivaId > 0) {
                foreach ($aulas as $a) {
                    if ($a['id'] === $aulaAtivaId) {
                        // Só permite abrir se já estiver assistida OU for a próxima liberada
                        if (in_array($a['id'], $idsAssistidas) || $a['id'] === $idAulaLiberada) {
                            $aulaAtual = $a;
                        }
                        break;
                    }
                }
            }
            if (!$aulaAtual) {
                $aulaAtual = $aulas[$indiceLiberado];
            }

            $jaAssistida = in_array($aulaAtual['id'], $idsAssistidas);
            $liberacao = $this->avaliarLiberacao($aulaAtual, $jaAssistida);

            // Marca no array quais aulas estão travadas, pra exibição
            foreach ($aulas as $i => &$a) {
                $a['travada'] = $i > $indiceLiberado;
            }
            unset($a);

            return [
                'curso' => $curso,
                'aulas' => $aulas,
                'aula_atual' => $aulaAtual,
                'ids_assistidas' => $idsAssistidas,
                'todas_assistidas' => count($idsAssistidas) >= count($aulas),
                'ja_assistida' => $jaAssistida,
                'pode_concluir' => $liberacao['pode_concluir'],
                'segundos_restantes' => $liberacao['segundos_restantes'],
                'duracao_segundos' => $liberacao['duracao_segundos'],
                'youtube_id' => $liberacao['youtube_id'],
                'progresso_video' => $liberacao['progresso_video'],
                'erro' => null,
            ];
        } catch (\PDOException $e) {
            return ['curso' => null, 'aulas' => [], 'aula_atual' => null, 'ids_assistidas' => [], 'todas_assistidas' => true,
                     'ja_assistida' => false, 'pode_concluir' => false, 'segundos_restantes' => 0, 'duracao_segundos' => 0,
                     'youtube_id' => null, 'progresso_video' => 0, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Recebe do player do YouTube (via JS) o quanto já foi assistido de verdade.
     * Guardado na sessão (o cliente não consegue forjar o valor guardado no servidor).
     * Retorna se, com esse novo progresso, a aula já pode ser concluída.
     */
    public function registrarProgressoVideo(int $cursoId, int $aulaId, float $percentual): bool
    {
        $aula = $this->aulaModel->buscarPorId($aulaId);
        if (!$aula || (int)$aula['curso_id'] !== $cursoId) {
            return false;
        }

        $percentual = max(0, min(100, $percentual));
        $chaveProgresso = 'aula_progresso_video_' . $aulaId;
        $_SESSION[$chaveProgresso] = max($_SESSION[$chaveProgresso] ?? 0, $percentual);

        $liberacao = $this->avaliarLiberacao($aula, false);
        return $liberacao['pode_concluir'];
    }

    /**
     * Confirma a conclusão da aula. Revalida no servidor (nunca confia só no botão do navegador):
     * o tempo mínimo E o percentual real assistido do vídeo precisam ter sido cumpridos de verdade.
     */
    public function concluirAula(int $cursoId, int $aulaId, int $usuarioId): bool
    {
        $aula = $this->aulaModel->buscarPorId($aulaId);
        if (!$aula || (int)$aula['curso_id'] !== $cursoId) {
            return false;
        }

        $chaveSessao = 'aula_iniciada_' . $aulaId;
        if (!isset($_SESSION[$chaveSessao])) {
            return false; // nunca "abriu" essa aula nesta sessão - não tem como ter concluído
        }

        $liberacao = $this->avaliarLiberacao($aula, false);
        if (!$liberacao['pode_concluir']) {
            return false; // tentou concluir sem cumprir o tempo mínimo e/ou o vídeo assistido
        }

        $this->progressoModel->marcarAssistida($usuarioId, $aulaId);
        unset($_SESSION[$chaveSessao], $_SESSION['aula_progresso_video_' . $aulaId]);
        return true;
    }
}
