<?php
declare(strict_types=1);

/**
 * Duración máxima de la sesión en segundos (30 minutos).
 * Modificar a un valor bajo (ej: 10) para pruebas rápidas.
 */
define('DURACION_SESION', 1800);

/**
 * Carga las variables de entorno desde el archivo .env ubicado en el mismo directorio.
 * Solo carga las variables que aún no estén definidas en el entorno.
 *
 * @return void
 */
function cargarEnv(): void
{
    $ruta = __DIR__ . '/.env';
    if (!file_exists($ruta)) {
        return;
    }
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        if (!str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);
        if (!array_key_exists($clave, $_ENV)) {
            $_ENV[$clave] = $valor;
            putenv("{$clave}={$valor}");
        }
    }
}

/**
 * Retorna una instancia PDO configurada para conectarse a la base de datos MySQL.
 * Las credenciales se leen desde las variables de entorno.
 * Lanza una excepción si la conexión falla.
 *
 * @return PDO Instancia de la conexión PDO.
 * @throws \RuntimeException Si las credenciales no están definidas.
 * @throws \PDOException Si la conexión a la base de datos falla.
 */
function obtenerConexion(): PDO
{
    cargarEnv();

    $host = $_ENV['DB_HOST'] ?? null;
    $port = $_ENV['DB_PORT'] ?? '3306';
    $nombre = $_ENV['DB_NAME'] ?? null;
    $usuario = $_ENV['DB_USER'] ?? null;
    $contrasena = $_ENV['DB_PASS'] ?? null;

    if ($host === null || $nombre === null || $usuario === null || $contrasena === null) {
        throw new \RuntimeException('Faltan variables de entorno para la conexión a la base de datos.');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$nombre};charset=utf8mb4";

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    return new PDO($dsn, $usuario, $contrasena, $opciones);
}

/*
 * =============================================================================
 * SCRIPT DE CONFIGURACIÓN INICIAL — EJECUCIÓN MANUAL
 * =============================================================================
 * Copiar y ejecutar en el cliente MySQL o mediante un script CLI separado.
 *
 * -- Crear la base de datos
 * CREATE DATABASE IF NOT EXISTS lms_auth_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 * USE lms_auth_demo;
 *
 * -- Crear la tabla de usuarios
 * CREATE TABLE IF NOT EXISTS usuarios (
 *     id         INT          NOT NULL AUTO_INCREMENT,
 *     username   VARCHAR(50)  NOT NULL,
 *     email      VARCHAR(100) NOT NULL,
 *     password   VARCHAR(255) NOT NULL,
 *     rol        ENUM('superadmin','admin','usuario','colaborador') NOT NULL,
 *     created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *     PRIMARY KEY (id),
 *     UNIQUE KEY uq_username (username),
 *     UNIQUE KEY uq_email    (email)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 * =============================================================================
 */

// Semilla de usuarios de prueba.
// Ejecutar una sola vez desde CLI: php config.php
// Genera hashes bcrypt reales de 'admin123' e inserta los 4 usuarios.
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $pdo  = obtenerConexion();
    $hash = password_hash('admin123', PASSWORD_BCRYPT);

    $sql = 'INSERT INTO usuarios (username, email, password, rol) VALUES (:username, :email, :password, :rol)';
    $stmt = $pdo->prepare($sql);

    $usuarios = [
        ['username' => 'superadmin',  'email' => 'superadmin@lms.test',  'rol' => 'superadmin'],
        ['username' => 'admin',       'email' => 'admin@lms.test',       'rol' => 'admin'],
        ['username' => 'usuario',     'email' => 'usuario@lms.test',     'rol' => 'usuario'],
        ['username' => 'colaborador', 'email' => 'colaborador@lms.test', 'rol' => 'colaborador'],
    ];

    foreach ($usuarios as $u) {
        $stmt->execute([
            ':username' => $u['username'],
            ':email'    => $u['email'],
            ':password' => $hash,
            ':rol'      => $u['rol'],
        ]);
    }

    echo 'Usuarios de prueba insertados correctamente.' . PHP_EOL;
}
