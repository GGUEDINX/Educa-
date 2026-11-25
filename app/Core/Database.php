<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Conexão única (Singleton) com o banco de dados.
 * Substitui o antigo includes/conexao.php.
 */
class Database
{
    private static ?PDO $instancia = null;

    private function __construct()
    {
        // Construtor privado: ninguém pode fazer "new Database()"
    }

    public static function getConexao(): PDO
    {
        if (self::$instancia === null) {
            $host = 'localhost';
            $db   = 'plataforma_treinamento';
            $user = 'root';
            $pass = ''; // Altere se o seu MySQL tiver senha
            $sgbd = 'mysql';

            try {
                self::$instancia = new PDO("$sgbd:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
                self::$instancia->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instancia->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log('[Database::getConexao] ' . $e->getMessage());
                die("Não foi possível conectar ao banco de dados. Verifique se o MySQL está rodando e tente novamente.");
            }
        }

        return self::$instancia;
    }
}
