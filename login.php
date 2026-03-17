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
 * Redirige al menú principal si el usuario ya tiene una sesión válida y activa.
 * Evita mostrar el formulario de login a usuarios autenticados.
 *
 * @return void
 */
function redirigirSiAutenticado(): void
{
    if (
        isset($_SESSION['usuario_id'], $_SESSION['inicio_sesion']) &&
        (time() - $_SESSION['inicio_sesion']) < DURACION_SESION
    ) {
        header('Location: menu.php');
        exit;
    }
}

/**
 * Genera un token CSRF aleatorio y lo almacena en la sesión.
 * Si ya existe un token en la sesión, lo reutiliza.
 *
 * @return string Token CSRF en formato hexadecimal.
 */
function generarTokenCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida que el token CSRF recibido en el POST coincida con el almacenado en la sesión.
 * Utiliza comparación segura para prevenir ataques de temporización.
 *
 * @param string $tokenRecibido Token enviado por el formulario.
 * @return bool True si el token es válido, false en caso contrario.
 */
function validarTokenCsrf(string $tokenRecibido): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $tokenRecibido);
}

/**
 * Procesa el formulario de login: valida campos, consulta la base de datos
 * y gestiona el inicio de sesión exitoso o el mensaje de error.
 *
 * @return array{error: string|null} Arreglo con el mensaje de error o null si no hay error.
 */
function procesarLogin(): array
{
    $resultado = ['error' => null];

    $tokenRecibido = trim($_POST['csrf_token'] ?? '');
    if (!validarTokenCsrf($tokenRecibido)) {
        $resultado['error'] = 'Solicitud inválida. Por favor recarga la página.';
        return $resultado;
    }

    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validar que los campos no estén vacíos
    if ($email === '' || $password === '') {
        $resultado['error'] = 'Todos los campos son requeridos.';
        return $resultado;
    }

    // Validar longitud mínima de la contraseña
    if (strlen($password) < 6) {
        $resultado['error'] = 'La contraseña debe tener al menos 6 caracteres.';
        return $resultado;
    }

    // Validar formato de email en el servidor
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $resultado['error'] = 'El formato del correo electrónico no es válido.';
        return $resultado;
    }

    try {
        $pdo = obtenerConexion();

        // Consultar el usuario por email usando prepared statement
        $stmt = $pdo->prepare('SELECT id, username, password, rol FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $usuario = $stmt->fetch();

        // Verificar credenciales — mensaje genérico para no revelar si el email existe
        if ($usuario === false || !password_verify($password, $usuario['password'])) {
            $resultado['error'] = 'Credenciales incorrectas. Verifica tu correo y contraseña.';
            return $resultado;
        }

        // Credenciales válidas: regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);

        // Inicializar variables de sesión exactas definidas en el Data Contract
        $_SESSION['usuario_id']    = (int) $usuario['id'];
        $_SESSION['username']      = $usuario['username'];
        $_SESSION['rol']           = $usuario['rol'];
        $_SESSION['inicio_sesion'] = time();

        // Invalidar token CSRF tras login exitoso
        unset($_SESSION['csrf_token']);

        header('Location: menu.php');
        exit;

    } catch (\Exception $e) {
        $resultado['error'] = 'Error interno del servidor. Intenta de nuevo más tarde.';
        return $resultado;
    }
}

// ---- Lógica principal --------------------------------------------------------

redirigirSiAutenticado();

$errorLogin = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = trim($_POST['action'] ?? '');

    if ($accion === 'login') {
        $res = procesarLogin();
        $errorLogin = $res['error'];
    }
}

$csrfToken = generarTokenCsrf();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS Demo — Iniciar sesión</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .card {
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
        }

        /* Logo / Título */
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            background-color: var(--color-accent);
            border-radius: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .logo-icon img { width: 1.75rem; height: 1.75rem; filter: invert(1); }
        .logo h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-text);
        }
        .logo p {
            font-size: 0.875rem;
            color: var(--color-text-muted);
            margin-top: 0.25rem;
        }

        /* Formulario */
        .form-group { margin-bottom: 1.25rem; }
        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.4rem;
            color: var(--color-text);
        }

        .input-wrapper { position: relative; }
        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            color: var(--color-text);
            font-family: 'Inter', sans-serif;
            font-size: 0.9375rem;
            padding: 0.65rem 0.875rem;
            outline: none;
            transition: border-color 0.2s;
        }
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="text"]:focus {
            border-color: var(--color-accent);
        }
        input.input-error  { border-color: var(--color-error) !important; }
        input.input-success { border-color: var(--color-success) !important; }

        /* Botón de mostrar/ocultar password */
        .toggle-pass {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--color-text-muted);
            padding: 0;
            display: flex;
            align-items: center;
        }
        .toggle-pass img { width: 1.25rem; height: 1.25rem; filter: invert(0.6); }
        input[type="password"],
        input[type="text"].pass-input {
            padding-right: 2.75rem;
        }

        /* Mensajes inline */
        .msg-error, .msg-success, .msg-field-error {
            font-size: 0.8125rem;
            margin-top: 0.35rem;
            display: block;
        }
        .msg-error, .msg-field-error { color: var(--color-error); }
        .msg-success { color: var(--color-success); }

        /* Alerta de error de formulario */
        .alert-error {
            background-color: rgba(248,113,113,0.1);
            border: 1px solid var(--color-error);
            border-radius: 0.5rem;
            color: var(--color-error);
            font-size: 0.875rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
        }

        /* Botón principal */
        .btn {
            width: 100%;
            background-color: var(--color-accent);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.9375rem;
            font-weight: 600;
            padding: 0.7rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn:hover:not(:disabled) { background-color: var(--color-accent-hover); }
        .btn:disabled { opacity: 0.65; cursor: not-allowed; }

        /* Spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            width: 1.1rem;
            height: 1.1rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        /* Link recuperar contraseña */
        .link-recuperar {
            display: block;
            text-align: center;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: var(--color-accent);
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
            font-family: 'Inter', sans-serif;
        }
        .link-recuperar:hover { color: var(--color-accent-hover); }

        /* Sección de recuperación */
        .seccion-recuperar {
            margin-top: 1.5rem;
            border-top: 1px solid var(--color-border);
            padding-top: 1.5rem;
            display: none;
        }
        .seccion-recuperar h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .seccion-recuperar p {
            font-size: 0.8125rem;
            color: var(--color-text-muted);
            margin-bottom: 1rem;
        }

        /* Expirado banner */
        .banner-expired {
            background-color: rgba(248,113,113,0.1);
            border: 1px solid var(--color-error);
            border-radius: 0.5rem;
            color: var(--color-error);
            font-size: 0.875rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">
            <!-- Heroicon: academic-cap (outline) vía CDN -->
            <img src="https://unpkg.com/heroicons@2.2.0/24/outline/academic-cap.svg" alt="" width="28" height="28">
        </div>
        <h1>LMS Demo</h1>
        <p>Plataforma de Aprendizaje</p>
    </div>

    <?php if (isset($_GET['expired'])): ?>
        <div class="banner-expired">Tu sesión ha expirado. Por favor inicia sesión nuevamente.</div>
    <?php endif; ?>

    <?php if ($errorLogin !== null): ?>
        <div class="alert-error"><?= htmlspecialchars($errorLogin, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form id="formLogin" method="POST" action="login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="login">

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                required
                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
            <span class="msg-field-error" id="emailError" aria-live="polite"></span>
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <div class="input-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="pass-input"
                    autocomplete="current-password"
                    required
                    minlength="6"
                >
                <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar u ocultar contraseña">
                    <!-- Heroicons vía CDN: eye y eye-slash, se alternan mediante JS -->
                    <img id="iconoOjoVisible" src="https://unpkg.com/heroicons@2.2.0/24/outline/eye.svg" alt="Mostrar contraseña" width="20" height="20">
                    <img id="iconoOjoOculto" src="https://unpkg.com/heroicons@2.2.0/24/outline/eye-slash.svg" alt="Ocultar contraseña" width="20" height="20" style="display:none;">
                </button>
            </div>
        </div>

        <button type="submit" class="btn" id="btnLogin">
            <span class="spinner" id="spinnerLogin"></span>
            <span id="textoLogin">Iniciar sesión</span>
        </button>
    </form>

    <button type="button" class="link-recuperar" id="btnMostrarRecuperar">
        ¿Olvidaste tu contraseña?
    </button>

    <!-- Sección de recuperación de contraseña (inline, oculta por defecto) -->
    <!-- Toda la lógica es del lado del cliente: sin POST al servidor ni recarga de página -->
    <div class="seccion-recuperar" id="seccionRecuperar">
        <h2>Recuperar contraseña</h2>
        <p>Ingresa tu correo electrónico y te enviaremos instrucciones para restablecer tu contraseña.</p>

        <div id="recuperarFormulario">
            <div class="form-group">
                <label for="email_recuperar">Correo electrónico</label>
                <input
                    type="email"
                    id="email_recuperar"
                    name="email_recuperar"
                    autocomplete="email"
                >
                <span class="msg-field-error" id="emailRecuperarError" aria-live="polite"></span>
            </div>

            <button type="button" class="btn" id="btnRecuperar">
                <span class="spinner" id="spinnerRecuperar"></span>
                <span id="textoRecuperar">Enviar instrucciones</span>
            </button>
        </div>

        <!-- Mensaje de éxito simulado — se muestra mediante JS sin recargar la página -->
        <span class="msg-success" id="recuperarExito" style="display:none;">
            Si el correo existe en nuestro sistema, recibirás instrucciones en breve.
        </span>
    </div>
</div>

<script>
(function () {
    'use strict';

    // --- Expresión regular para validación de email ---
    var REGEX_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // --- Referencia a elementos del DOM ---
    var emailInput     = document.getElementById('email');
    var emailError     = document.getElementById('emailError');
    var passwordInput  = document.getElementById('password');
    var togglePass     = document.getElementById('togglePass');
    var iconoOjoVisible = document.getElementById('iconoOjoVisible');
    var iconoOjoOculto  = document.getElementById('iconoOjoOculto');
    var formLogin      = document.getElementById('formLogin');
    var btnLogin       = document.getElementById('btnLogin');
    var spinnerLogin   = document.getElementById('spinnerLogin');
    var textoLogin     = document.getElementById('textoLogin');

    var btnMostrarRecuperar  = document.getElementById('btnMostrarRecuperar');
    var seccionRecuperar     = document.getElementById('seccionRecuperar');
    var recuperarFormulario  = document.getElementById('recuperarFormulario');
    var emailRecuperarInput  = document.getElementById('email_recuperar');
    var emailRecuperarError  = document.getElementById('emailRecuperarError');
    var btnRecuperar         = document.getElementById('btnRecuperar');
    var spinnerRecuperar     = document.getElementById('spinnerRecuperar');
    var textoRecuperar       = document.getElementById('textoRecuperar');
    var recuperarExito       = document.getElementById('recuperarExito');

    /**
     * Valida el formato del campo de email en tiempo real.
     * Aplica clase CSS de error o éxito según el resultado.
     *
     * @param {HTMLInputElement} input  - Campo de email a validar.
     * @param {HTMLElement}      errorEl - Elemento donde mostrar el mensaje de error.
     * @returns {boolean} True si el formato es válido.
     */
    function validarEmail(input, errorEl) {
        var valor = input.value.trim();
        if (valor === '') {
            input.classList.remove('input-error', 'input-success');
            if (errorEl) errorEl.textContent = '';
            return false;
        }
        if (!REGEX_EMAIL.test(valor)) {
            input.classList.add('input-error');
            input.classList.remove('input-success');
            if (errorEl) errorEl.textContent = 'El formato del correo no es válido.';
            return false;
        }
        input.classList.remove('input-error');
        input.classList.add('input-success');
        if (errorEl) errorEl.textContent = '';
        return true;
    }

    /**
     * Activa el estado de carga de un botón: lo deshabilita y muestra el spinner.
     *
     * @param {HTMLButtonElement} btn       - Botón a deshabilitar.
     * @param {HTMLElement}       spinner   - Elemento spinner a mostrar.
     * @param {HTMLElement}       textoEl   - Elemento de texto a ocultar.
     */
    function activarSpinner(btn, spinner, textoEl) {
        btn.disabled = true;
        spinner.style.display = 'block';
        textoEl.style.display = 'none';
    }

    // --- Validación en tiempo real del email de login ---
    if (emailInput) {
        emailInput.addEventListener('input', function () {
            validarEmail(emailInput, emailError);
        });
    }

    // --- Toggle mostrar/ocultar contraseña ---
    // Alterna la visibilidad entre los dos íconos CDN (eye / eye-slash)
    if (togglePass && passwordInput) {
        togglePass.addEventListener('click', function () {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                iconoOjoVisible.style.display = 'none';
                iconoOjoOculto.style.display  = 'inline';
                togglePass.setAttribute('aria-label', 'Ocultar contraseña');
            } else {
                passwordInput.type = 'password';
                iconoOjoVisible.style.display = 'inline';
                iconoOjoOculto.style.display  = 'none';
                togglePass.setAttribute('aria-label', 'Mostrar contraseña');
            }
        });
    }

    // --- Spinner en submit del formulario de login ---
    if (formLogin) {
        formLogin.addEventListener('submit', function (e) {
            var emailValido = validarEmail(emailInput, emailError);
            if (!emailValido) {
                e.preventDefault();
                return;
            }
            activarSpinner(btnLogin, spinnerLogin, textoLogin);
        });
    }

    // --- Mostrar/ocultar sección de recuperación de contraseña ---
    if (btnMostrarRecuperar && seccionRecuperar) {
        btnMostrarRecuperar.addEventListener('click', function () {
            var estaVisible = seccionRecuperar.style.display === 'block';
            seccionRecuperar.style.display = estaVisible ? 'none' : 'block';
        });
    }

    // --- Validación en tiempo real del email de recuperación ---
    if (emailRecuperarInput) {
        emailRecuperarInput.addEventListener('input', function () {
            validarEmail(emailRecuperarInput, emailRecuperarError);
        });
    }

    // --- Recuperación de contraseña: lógica completamente del lado del cliente ---
    // No realiza ninguna petición al servidor ni recarga la página.
    if (btnRecuperar) {
        btnRecuperar.addEventListener('click', function () {
            var emailValido = validarEmail(emailRecuperarInput, emailRecuperarError);
            if (!emailValido) {
                return;
            }

            // Mostrar spinner y deshabilitar botón mientras se "procesa"
            activarSpinner(btnRecuperar, spinnerRecuperar, textoRecuperar);

            // Simular petición con retraso de 600 ms
            setTimeout(function () {
                // Ocultar el formulario y mostrar mensaje de éxito en verde
                recuperarFormulario.style.display = 'none';
                recuperarExito.style.display = 'block';
            }, 600);
        });
    }

})();
</script>
</body>
</html>
