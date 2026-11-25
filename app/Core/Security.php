<?php

namespace App\Core;

/**
 * Funções centrais de segurança usadas em todo o sistema:
 * - Sessão configurada de forma seções (cookie httponly, samesite)
 * - Proteção CSRF nos formulários
 * - Hash de senha (bcrypt) com compatibilidade para senhas antigas em texto puro
 * - Cabeçalhos de segurança HTTP
 */
class Security
{
    /**
     * Inicia a sessão com configurações seguras de cookie.
     * Deve ser chamado ANTES de qualquer session_start() no fluxo da página.
     */
    public static function iniciarSessaoSegura(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $ehHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);

        session_set_cookie_params([
            'lifetime' => 0,          // expira ao fechar o navegador
            'path' => '/',
            'domain' => '',
            'secure' => $ehHttps,     // só envia o cookie em HTTPS, quando disponível
            'httponly' => true,       // JavaScript não consegue ler o cookie de sessão
            'samesite' => 'Lax',      // mitiga ataques CSRF básicos
        ]);

        session_start();

        // Timeout por inatividade: 30 minutos
        $tempoLimite = 1800;
        if (isset($_SESSION['ultima_atividade']) && (time() - $_SESSION['ultima_atividade'] > $tempoLimite)) {
            $_SESSION = [];
            session_destroy();
            session_start();
        }
        $_SESSION['ultima_atividade'] = time();
    }

    /** Cabeçalhos HTTP que reduzem a superfície de ataque comum (clickjacking, MIME sniffing, etc.) */
    public static function enviarCabecalhosSeguranca(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    // ===================== CSRF =====================

    /** Gera (ou reaproveita) o token CSRF da sessão atual e retorna ele. */
    public static function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Imprime um campo <input hidden> pronto para colocar dentro de qualquer <form>. */
    public static function campoCsrf(): string
    {
        $token = htmlspecialchars(self::tokenCsrf(), ENT_QUOTES);
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"$token\">";
    }

    /** Valida o token recebido no POST. Encerra a requisição se for inválido. */
    public static function validarCsrf(): void
    {
        $tokenEnviado = $_POST['csrf_token'] ?? '';
        $tokenSessao = $_SESSION['csrf_token'] ?? '';

        if (empty($tokenEnviado) || empty($tokenSessao) || !hash_equals($tokenSessao, $tokenEnviado)) {
            http_response_code(403);
            die('Sessão expirada ou requisição inválida. Volte e tente novamente.');
        }
    }

    // ===================== SENHAS =====================

    public static function hashSenha(string $senha): string
    {
        return password_hash($senha, PASSWORD_BCRYPT);
    }

    /**
     * Verifica a senha. Compatível com contas antigas que ainda guardam
     * a senha em texto puro no banco (formato pré-atualização de segurança).
     */
    public static function verificarSenha(string $senhaDigitada, string $hashOuTextoPuro): bool
    {
        $pareceHash = (bool) preg_match('/^\$2[abxy]\$/', $hashOuTextoPuro);

        if ($pareceHash) {
            return password_verify($senhaDigitada, $hashOuTextoPuro);
        }

        // Conta antiga (senha em texto puro): compara direto.
        // O AuthController re-hasheia automaticamente após um login bem-sucedido.
        return hash_equals($hashOuTextoPuro, $senhaDigitada);
    }

    /** Garante que uma cor é um hexadecimal válido (#rrggbb) antes de ir para HTML/CSS. Evita XSS via valores salvos no banco. */
    public static function corHexValida(?string $cor, string $padrao = '#3b82f6'): string
    {
        return ($cor && preg_match('/^#[0-9a-fA-F]{6}$/', $cor)) ? $cor : $padrao;
    }
}
