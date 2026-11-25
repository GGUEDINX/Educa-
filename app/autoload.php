<?php
/**
 * Autoloader simples (PSR-4 "na mão", sem depender do Composer).
 * Mapeia o namespace App\... para os arquivos dentro de /app.
 *
 * Ex: App\Models\Usuario  ->  /app/Models/Usuario.php
 */
spl_autoload_register(function ($classe) {
    $prefixo = 'App\\';
    $diretorioBase = __DIR__ . '/';

    if (strncmp($prefixo, $classe, strlen($prefixo)) !== 0) {
        return; // não é uma classe do nosso namespace, ignora
    }

    $classeRelativa = substr($classe, strlen($prefixo));
    $arquivo = $diretorioBase . str_replace('\\', '/', $classeRelativa) . '.php';

    if (file_exists($arquivo)) {
        require $arquivo;
    }
});
