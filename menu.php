<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Inicia la sesión PHP si aún no está activa.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Protege la ruta: verifica que exista una sesión autenticada y válida.
 * Si no hay sesión o la sesión expiró, destruye la sesión y redirige a login.php.
 *
 * @return void
 */
function protegerRuta(): void
{
    if (!isset($_SESSION['usuario_id'], $_SESSION['inicio_sesion'])) {
        header('Location: login.php');
        exit;
    }

    $tiempoTranscurrido = time() - (int) $_SESSION['inicio_sesion'];
    if ($tiempoTranscurrido >= DURACION_SESION) {
        // Sesión expirada: destruir y redirigir
        $_SESSION = [];
        session_destroy();
        header('Location: login.php?expired=1');
        exit;
    }
}

/**
 * Retorna los ítems del menú correspondientes al rol dado.
 * Cada ítem tiene: título, descripción breve y nombre del Heroicon a usar.
 *
 * @param string $rol Rol del usuario autenticado.
 * @return array<int, array{titulo: string, descripcion: string, icono: string}> Lista de ítems del menú.
 */
function obtenerMenuPorRol(string $rol): array
{
    $menus = [
        'superadmin' => [
            ['titulo' => 'Dashboard',                  'descripcion' => 'Resumen general del sistema.',             'icono' => 'chart-bar'],
            ['titulo' => 'Usuarios',                   'descripcion' => 'Gestión de cuentas y perfiles.',           'icono' => 'users'],
            ['titulo' => 'Cursos',                     'descripcion' => 'Administración del catálogo de cursos.',   'icono' => 'academic-cap'],
            ['titulo' => 'Reportes',                   'descripcion' => 'Informes y métricas de la plataforma.',    'icono' => 'document-chart-bar'],
            ['titulo' => 'Configuración del Sistema',  'descripcion' => 'Parámetros globales del LMS.',             'icono' => 'cog-6-tooth'],
            ['titulo' => 'Auditoría',                  'descripcion' => 'Registro de acciones y eventos.',          'icono' => 'shield-check'],
        ],
        'admin' => [
            ['titulo' => 'Dashboard',       'descripcion' => 'Resumen del estado de la plataforma.',      'icono' => 'chart-bar'],
            ['titulo' => 'Cursos',          'descripcion' => 'Administración de cursos asignados.',       'icono' => 'academic-cap'],
            ['titulo' => 'Inscripciones',   'descripcion' => 'Gestión de inscripciones de alumnos.',     'icono' => 'clipboard-document-list'],
            ['titulo' => 'Reportes',        'descripcion' => 'Informes de progreso y participación.',     'icono' => 'document-chart-bar'],
            ['titulo' => 'Configuración',   'descripcion' => 'Ajustes de la sección administrada.',       'icono' => 'adjustments-horizontal'],
        ],
        'usuario' => [
            ['titulo' => 'Mi Dashboard',      'descripcion' => 'Vista general de tu actividad.',          'icono' => 'chart-bar'],
            ['titulo' => 'Mis Cursos',        'descripcion' => 'Cursos en los que estás inscrito.',       'icono' => 'academic-cap'],
            ['titulo' => 'Mi Progreso',       'descripcion' => 'Seguimiento de tu avance académico.',     'icono' => 'chart-pie'],
            ['titulo' => 'Mis Certificados',  'descripcion' => 'Certificados obtenidos al completar.',    'icono' => 'trophy'],
        ],
        'colaborador' => [
            ['titulo' => 'Mis Asignaciones',  'descripcion' => 'Tareas y módulos asignados.',             'icono' => 'clipboard-document-check'],
            ['titulo' => 'Contenido',         'descripcion' => 'Material y recursos de los cursos.',      'icono' => 'book-open'],
            ['titulo' => 'Foro',              'descripcion' => 'Participa en los foros de discusión.',    'icono' => 'chat-bubble-left-right'],
            ['titulo' => 'Calendario',        'descripcion' => 'Eventos y fechas importantes.',           'icono' => 'calendar-days'],
        ],
    ];

    return $menus[$rol] ?? [];
}

/**
 * Retorna el texto de saludo personalizado según el rol y nombre de usuario.
 *
 * @param string $rol      Rol del usuario.
 * @param string $username Nombre de usuario autenticado.
 * @return string Saludo formateado.
 */
function obtenerSaludoPorRol(string $rol, string $username): string
{
    $saludos = [
        'superadmin'  => 'Bienvenido, Super Administrador ' . $username,
        'admin'       => 'Bienvenido, Administrador ' . $username,
        'usuario'     => 'Bienvenido, ' . $username,
        'colaborador' => 'Hola, ' . $username,
    ];

    return $saludos[$rol] ?? 'Bienvenido, ' . $username;
}

/**
 * Genera el SVG de un Heroicon (outline) según el nombre del ícono indicado.
 * Retorna solo el contenido interno del SVG (paths), sin la etiqueta <svg> envolvente.
 *
 * @param string $nombre Nombre del Heroicon.
 * @return string Contenido SVG interno (paths).
 */
function obtenerSvgHeroicon(string $nombre): string
{
    $iconos = [
        'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
        'academic-cap' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />',
        'document-chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />',
        'cog-6-tooth' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
        'shield-check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />',
        'clipboard-document-list' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />',
        'chart-pie' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />',
        'trophy' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />',
        'clipboard-document-check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />',
        'book-open' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />',
        'chat-bubble-left-right' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />',
        'calendar-days' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />',
        'adjustments-horizontal' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />',
    ];

    return $iconos[$nombre] ?? '';
}

// ---- Lógica principal --------------------------------------------------------

protegerRuta();

$usuarioId     = (int) $_SESSION['usuario_id'];
$username      = (string) $_SESSION['username'];
$rol           = (string) $_SESSION['rol'];
$inicioSesion  = (int) $_SESSION['inicio_sesion'];

$tiempoRestante = DURACION_SESION - (time() - $inicioSesion);
$tiempoRestante = max(0, $tiempoRestante);

$saludo      = obtenerSaludoPorRol($rol, $username);
$itemsMenu   = obtenerMenuPorRol($rol);

// Etiqueta visual del rol
$etiquetasRol = [
    'superadmin'  => 'Super Admin',
    'admin'       => 'Admin',
    'usuario'     => 'Usuario',
    'colaborador' => 'Colaborador',
];
$etiquetaRol = $etiquetasRol[$rol] ?? $rol;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS Demo — Panel principal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg:           #0f172a;
            --color-surface:      #1e293b;
            --color-border:       #334155;
            --color-accent:       #6366f1;
            --color-accent-hover: #4f46e5;
            --color-text:         #f1f5f9;
            --color-text-muted:   #94a3b8;
            --color-error:        #f87171;
            --color-success:      #34d399;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--color-bg);
            color: var(--color-text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        /* ---- Navbar ---- */
        .navbar {
            background-color: var(--color-surface);
            border-bottom: 1px solid var(--color-border);
            padding: 0 1.5rem;
            height: 4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-brand {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--color-text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .navbar-brand img {
            width: 1.5rem;
            height: 1.5rem;
            filter: invert(48%) sepia(79%) saturate(500%) hue-rotate(200deg) brightness(100%) contrast(90%);
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }
        .user-name {
            font-size: 0.9375rem;
            font-weight: 500;
        }

        .badge-rol {
            background-color: rgba(99,102,241,0.2);
            border: 1px solid rgba(99,102,241,0.4);
            color: var(--color-accent);
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
        }

        /* Contador de sesión */
        .session-timer {
            font-size: 0.8125rem;
            color: var(--color-text-muted);
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        .session-timer img { width: 1rem; height: 1rem; filter: invert(0.6); }
        .session-timer .tiempo {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            letter-spacing: 0.03em;
            transition: color 0.3s;
        }
        .session-timer.alerta .tiempo { color: var(--color-error); }

        /* Botón cerrar sesión */
        .btn-logout {
            background-color: transparent;
            border: 1px solid var(--color-border);
            color: var(--color-text-muted);
            border-radius: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.45rem 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            transition: border-color 0.2s, color 0.2s;
            text-decoration: none;
        }
        .btn-logout:hover:not(:disabled) {
            border-color: var(--color-error);
            color: var(--color-error);
        }
        .btn-logout:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-logout img { width: 1rem; height: 1rem; filter: invert(0.6); }

        /* Spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            width: 0.9rem;
            height: 0.9rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        /* ---- Contenido principal ---- */
        .main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }

        .saludo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.375rem;
        }
        .saludo-sub {
            color: var(--color-text-muted);
            font-size: 0.9375rem;
            margin-bottom: 2.5rem;
        }

        /* Grid de menú */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.25rem;
        }

        .menu-card {
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 0.875rem;
            padding: 1.5rem 1.25rem;
            cursor: pointer;
            transition: border-color 0.2s, transform 0.15s;
        }
        .menu-card:hover {
            border-color: var(--color-accent);
            transform: translateY(-2px);
        }
        .card-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            background-color: rgba(99,102,241,0.15);
            border-radius: 0.625rem;
            margin-bottom: 1rem;
            color: var(--color-accent);
        }
        .card-icon img { width: 1.4rem; height: 1.4rem; filter: invert(48%) sepia(79%) saturate(500%) hue-rotate(200deg) brightness(100%) contrast(90%); }
        .card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }
        .card-desc {
            font-size: 0.8125rem;
            color: var(--color-text-muted);
            line-height: 1.5;
        }

        /* ---- Overlay de sesión expirada ---- */
        .overlay-expirado {
            position: fixed;
            inset: 0;
            background-color: rgba(15,23,42,0.92);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 999;
            text-align: center;
            padding: 2rem;
        }
        .overlay-expirado img {
            width: 3.5rem;
            height: 3.5rem;
            filter: invert(60%) sepia(90%) saturate(500%) hue-rotate(300deg) brightness(110%);
            margin-bottom: 1rem;
        }
        .overlay-expirado h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: 0.5rem;
        }
        .overlay-expirado p {
            color: var(--color-text-muted);
            font-size: 0.9375rem;
        }

        @media (max-width: 600px) {
            .user-name, .badge-rol { display: none; }
            .saludo { font-size: 1.375rem; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">
        <!-- Heroicon: academic-cap (outline) vía CDN -->
        <img src="https://unpkg.com/heroicons@2.2.0/24/outline/academic-cap.svg" alt="" width="24" height="24">
        LMS Demo
    </div>

    <div class="navbar-right">
        <div class="session-timer" id="sessionTimer">
            <!-- Heroicon: clock (outline) vía CDN -->
            <img src="https://unpkg.com/heroicons@2.2.0/24/outline/clock.svg" alt="" width="16" height="16">
            <span>Sesión expira en </span>
            <span class="tiempo" id="tiempoRestante">--:--</span>
        </div>

        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge-rol"><?= htmlspecialchars($etiquetaRol, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <button type="button" class="btn-logout" id="btnLogout" onclick="cerrarSesion()">
            <span class="spinner" id="spinnerLogout"></span>
            <!-- Heroicon: arrow-right-on-rectangle (outline) vía CDN -->
            <img id="iconoLogout" src="https://unpkg.com/heroicons@2.2.0/24/outline/arrow-right-on-rectangle.svg" alt="" width="16" height="16">
            <span id="textoLogout">Cerrar sesión</span>
        </button>
    </div>
</nav>

<!-- Contenido principal -->
<main class="main">
    <h1 class="saludo"><?= htmlspecialchars($saludo, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="saludo-sub">Selecciona una sección para continuar.</p>

    <div class="menu-grid">
        <?php foreach ($itemsMenu as $item): ?>
        <div class="menu-card">
            <div class="card-icon">
                <!-- Heroicon vía CDN -->
                <img
                    src="https://unpkg.com/heroicons@2.2.0/24/outline/<?= htmlspecialchars($item['icono'], ENT_QUOTES, 'UTF-8') ?>.svg"
                    alt=""
                    width="22"
                    height="22"
                >
            </div>
            <div class="card-title"><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="card-desc"><?= htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Overlay de sesión expirada -->
<div class="overlay-expirado" id="overlayExpirado">
    <!-- Heroicon: exclamation-circle (outline) vía CDN -->
    <img src="https://unpkg.com/heroicons@2.2.0/24/outline/exclamation-circle.svg" alt="" width="56" height="56">
    <h2>Tu sesión ha expirado</h2>
    <p>Serás redirigido a la página de inicio de sesión…</p>
</div>

<script>
(function () {
    'use strict';

    // Tiempo restante en segundos calculado en PHP para evitar depender solo del reloj del cliente
    var segundosRestantes = <?= (int) $tiempoRestante ?>;

    var timerEl    = document.getElementById('sessionTimer');
    var tiempoEl   = document.getElementById('tiempoRestante');
    var overlayEl  = document.getElementById('overlayExpirado');
    var CINCO_MIN  = 300; // 5 minutos en segundos

    /**
     * Formatea un número de segundos en el formato MM:SS.
     *
     * @param {number} seg - Segundos a formatear.
     * @returns {string} Tiempo formateado como 'MM:SS'.
     */
    function formatearTiempo(seg) {
        var minutos  = Math.floor(seg / 60);
        var segundos = seg % 60;
        return String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
    }

    /**
     * Muestra el overlay de sesión expirada y redirige al logout tras un breve instante.
     */
    function sesionExpirada() {
        overlayEl.style.display = 'flex';
        // Redirigir después de mostrar el mensaje
        setTimeout(function () {
            window.location.href = 'logout.php?expired=1';
        }, 2500);
    }

    /**
     * Actualiza el contador regresivo en el navbar cada segundo.
     * Cambia el color a rojo cuando quedan menos de 5 minutos.
     * Al llegar a 00:00 muestra el overlay y redirige.
     */
    function actualizarContador() {
        if (segundosRestantes <= 0) {
            tiempoEl.textContent = '00:00';
            sesionExpirada();
            return;
        }

        tiempoEl.textContent = formatearTiempo(segundosRestantes);

        // Alerta visual cuando quedan menos de 5 minutos
        if (segundosRestantes <= CINCO_MIN) {
            timerEl.classList.add('alerta');
        } else {
            timerEl.classList.remove('alerta');
        }

        segundosRestantes--;
        setTimeout(actualizarContador, 1000);
    }

    /**
     * Activa el spinner del botón de cerrar sesión y redirige a logout.php.
     */
    function cerrarSesion() {
        var btn     = document.getElementById('btnLogout');
        var spinner = document.getElementById('spinnerLogout');
        var icono   = document.getElementById('iconoLogout');
        var texto   = document.getElementById('textoLogout');

        btn.disabled = true;
        spinner.style.display = 'inline-block';
        icono.style.display   = 'none';
        texto.style.display   = 'none';

        window.location.href = 'logout.php';
    }

    // Exponer cerrarSesion al ámbito global para el onclick inline
    window.cerrarSesion = cerrarSesion;

    // Iniciar el contador
    actualizarContador();

})();
</script>
</body>
</html>
