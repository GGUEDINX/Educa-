<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Models\Curso;
use App\Models\Certificado;
use App\Models\Ferramenta;
use App\Models\ProgressoCurso;
use App\Models\SolicitacaoCurso;

class DashboardEmpresaController
{
    private Usuario $usuarioModel;
    private Curso $cursoModel;
    private Certificado $certificadoModel;
    private Ferramenta $ferramentaModel;
    private ProgressoCurso $progressoModel;
    private SolicitacaoCurso $solicitacaoModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->cursoModel = new Curso();
        $this->certificadoModel = new Certificado();
        $this->ferramentaModel = new Ferramenta();
        $this->progressoModel = new ProgressoCurso();
        $this->solicitacaoModel = new SolicitacaoCurso();
    }

    /** Monta todos os dados (KPIs) usados na view do dashboard da empresa. */
    public function montarPainel(int $empresaId): array
    {
        $totalFuncionarios = $this->usuarioModel->contarFuncionariosPorEmpresa($empresaId);

        // Taxa de conclusão: usa cursos obrigatórios; se nenhum foi marcado, usa todos os cursos
        $totalCursosObrig = $this->cursoModel->contarObrigatorios();
        $apenasObrigatorios = $totalCursosObrig > 0;
        $totalCursosParaTaxa = $apenasObrigatorios ? $totalCursosObrig : $this->cursoModel->contarTodos();

        $taxaConclusao = $this->progressoModel->taxaConclusao(
            $empresaId,
            $totalFuncionarios,
            $totalCursosParaTaxa,
            $apenasObrigatorios
        );

        $ativosSemana = $this->usuarioModel->contarAtivosUltimaSemana($empresaId);
        $inativosSemana = $ativosSemana !== null ? max(0, $totalFuncionarios - $ativosSemana) : null;

        $cursosDestaque = $this->cursoModel->destaquesPorEmpresa($empresaId);
        $maxAcessosCurso = 1;
        foreach ($cursosDestaque as $cd) {
            if ($cd['total_acessos'] > $maxAcessosCurso) {
                $maxAcessosCurso = $cd['total_acessos'];
            }
        }

        return [
            'total_funcionarios'      => $totalFuncionarios,
            'taxa_conclusao'          => $taxaConclusao,
            'taxa_conclusao_label'    => $apenasObrigatorios ? 'cursos obrigatórios' : 'todos os cursos',
            'ativos_semana'           => $ativosSemana,
            'inativos_semana'         => $inativosSemana,
            'ranking_certificados'    => $this->certificadoModel->rankingPorEmpresa($empresaId),
            'total_ferramentas'       => $this->ferramentaModel->contarPorEmpresa($empresaId),
            'top_ferramentas'         => $this->ferramentaModel->topAcessadas($empresaId),
            'cursos_destaque'         => $cursosDestaque,
            'max_acessos_curso'       => $maxAcessosCurso,
            'vencimentos'             => $this->certificadoModel->vencimentosProximos($empresaId),
            'solicitacoes_pendentes'  => $this->solicitacaoModel->contarPendentes($empresaId),
        ];
    }
}
