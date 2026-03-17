<?php
declare(strict_types=1);

/**
 * Inicia la sesión si no está activa para poder destruirla correctamente.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Limpia todas las variables de sesión y destruye la sesión del usuario.
 * Redirige siempre a login.php tras el cierre de sesión.
 *
 * @return void
 */
function cerrarSesion(): void
{
    // Vaciar el arreglo de variables de sesión
    $_SESSION = [];

    // Eliminar la cookie de sesión del navegador
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destruir la sesión en el servidor
    session_destroy();
}

cerrarSesion();

// Redirigir a login; si viene de un expirado por JS, preservar el parámetro
$expirado = isset($_GET['expired']) ? '?expired=1' : '';
header('Location: login.php' . $expirado);
exit;
