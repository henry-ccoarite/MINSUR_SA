<?php
// login.php - Login compacto con video de fondo
session_start();
require_once __DIR__ . '/config/db_mongo.php';
require_once __DIR__ . '/config/auth.php';

if (isLogged()) { 
    header('Location: ' . BASE . '/modules/minsur/index.php'); 
    exit; 
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usu  = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    $rol_seleccionado = $_POST['rol'] ?? '';
    
    if ($usu && $pass && $rol_seleccionado) {
        $row = getUserByCredentials($usu, $pass);
        
        if ($row) {
            if ($row['rol'] === $rol_seleccionado) {
                $_SESSION['emp_id'] = (string)$row['_id'];
                $_SESSION['nombre'] = $row['nombre'];
                $_SESSION['rol']    = $row['rol'];
                
                if ($rol_seleccionado === 'Admin') {
                    header('Location: ' . BASE . '/modules/minsur/index.php');
                } else {
                    header('Location: ' . BASE . '/modules/minsur/reportes/');
                }
                exit;
            } else {
                $err = 'El rol seleccionado no coincide con tus credenciales.';
            }
        } else {
            $err = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $err = 'Completa todos los campos.';
    }
}

require_once __DIR__ . '/config/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>MINSUR S.A. - Iniciar Sesión</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 20px;
        }

        #videoFondo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }

        .login-container {
            display: flex;
            max-width: 820px;
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5);
            z-index: 1;
            position: relative;
        }

        .login-left {
            flex: 1.2;
            padding: 28px 24px;
            background: rgba(255, 255, 255, 0.02);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-right {
            flex: 0.8;
            background: linear-gradient(135deg, rgba(233, 69, 96, 0.9), rgba(199, 58, 82, 0.9));
            padding: 28px 22px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
        }

        .login-left .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .login-left .logo .icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .login-left .logo .icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .login-left .logo h1 {
            color: white;
            font-size: 18px;
            font-weight: 700;
        }

        .login-left .logo span {
            color: #888;
            font-weight: 400;
            font-size: 11px;
        }

        .login-left h2 {
            color: white;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .login-left p {
            color: #888;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group label {
            display: block;
            color: #ccc;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .form-group label i {
            margin-right: 4px;
        }

        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 13px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: #e94560;
            outline: none;
            background: rgba(255, 255, 255, 0.08);
        }

        .form-group input::placeholder {
            color: #666;
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            margin-top: 2px;
        }

        .roles-grid label {
            padding: 4px 2px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            font-size: 8px;
            transition: all 0.2s;
            color: #999;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1px;
        }

        .roles-grid label:hover {
            border-color: #e94560;
            background: rgba(233, 69, 96, 0.08);
        }

        .roles-grid input[type="radio"] {
            display: none;
        }

        .roles-grid input[type="radio"]:checked + label {
            border-color: #e94560;
            background: rgba(233, 69, 96, 0.12);
            color: white;
        }

        .roles-grid .role-icon {
            font-size: 14px;
        }

        .btn-login {
            width: 100%;
            padding: 9px;
            background: #e94560;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 2px;
        }

        .btn-login:hover {
            background: #c73a52;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(233, 69, 96, 0.3);
        }

        .btn-login i {
            margin-right: 6px;
        }

        .login-right h2 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .login-right p {
            font-size: 13px;
            opacity: 0.8;
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .login-right .features {
            list-style: none;
        }

        .login-right .features li {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
            font-size: 12px;
            opacity: 0.9;
        }

        .login-right .features li i {
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        /* ============================================ */
        /* LOGO DERECHO - MÁS GRANDE                   */
        /* ============================================ */
        .login-icon-big {
            width: 140px;
            height: 140px;
            margin: 0 auto 16px auto;
            border-radius: 50%;
            overflow: hidden;
        }

        .login-icon-big img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .error-msg {
            background: rgba(220, 38, 38, 0.12);
            border: 1px solid rgba(220, 38, 38, 0.15);
            color: #fca5a5;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .error-msg i {
            margin-right: 4px;
        }

        .login-footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
            color: #666;
        }

        .login-footer strong {
            color: #ccc;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 95%;
            }
            .login-right {
                display: none;
            }
            .login-left {
                padding: 20px 16px;
            }
            .roles-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            .login-left .logo .icon {
                width: 50px;
                height: 50px;
            }
        }

        @media (max-width: 480px) {
            .roles-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .login-left .logo .icon {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>

    <video id="videoFondo" autoplay muted loop playsinline>
        <source src="<?= BASE ?>/assets/videos/fondo.mp4" type="video/mp4">
    </video>

    <div class="overlay"></div>

    <div class="login-container">
        <!-- Lado Izquierdo -->
        <div class="login-left">
    <div class="logo">
        <div class="icon" style="width:130px;height:130px;border-radius:70px;overflow:hidden;flex-shrink:0;">
            <img src="<?= BASE ?>/assets/img/logo3.png" alt="MINSUR" style="width:100%;height:100%;object-fit:cover;">
        </div>
        <div>
            <h1>MINSUR S.A.</h1>
            <span>Reportes de Guardia</span>
        </div>
    </div>
            <h2>Bienvenido</h2>
            <p>Ingresa tus credenciales para acceder al sistema</p>

            <?php if($err): ?>
                <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Usuario</label>
                    <input type="text" name="usuario" placeholder="Ingresa tu usuario" required autofocus>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Selecciona tu rol</label>
                    <div class="roles-grid">
                        <input type="radio" name="rol" id="rol_admin" value="Admin" checked>
                        <label for="rol_admin">
                            <span class="role-icon"><i class="fas fa-crown"></i></span>
                            Admin
                        </label>
                        
                        <input type="radio" name="rol" id="rol_gerente" value="Gerente">
                        <label for="rol_gerente">
                            <span class="role-icon"><i class="fas fa-chart-line"></i></span>
                            Gerente
                        </label>
                        
                        <input type="radio" name="rol" id="rol_jefe" value="Jefe Guardia">
                        <label for="rol_jefe">
                            <span class="role-icon"><i class="fas fa-user-tie"></i></span>
                            Jefe
                        </label>
                        
                        <input type="radio" name="rol" id="rol_supervisor" value="Supervisor">
                        <label for="rol_supervisor">
                            <span class="role-icon"><i class="fas fa-user-check"></i></span>
                            Super
                        </label>
                        
                        <input type="radio" name="rol" id="rol_agente" value="Agente">
                        <label for="rol_agente">
                            <span class="role-icon"><i class="fas fa-user-hard-hat"></i></span>
                            Agente
                        </label>
                        
                        <input type="radio" name="rol" id="rol_tecnico" value="Tecnico">
                        <label for="rol_tecnico">
                            <span class="role-icon"><i class="fas fa-wrench"></i></span>
                            Técnico
                        </label>
                        
                        <input type="radio" name="rol" id="rol_operador" value="Operador">
                        <label for="rol_operador">
                            <span class="role-icon"><i class="fas fa-user-cog"></i></span>
                            Operador
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Ingresar
                </button>
            </form>
            
            <div class="login-footer">
                <i class="fas fa-users"></i> Usuarios: <strong>admin</strong> | <strong>gerente</strong> | <strong>jefe_guardia</strong> | <strong>supervisor</strong> | <strong>agente</strong> | <strong>tecnico</strong> | <strong>operador</strong>
                <span style="color:#555;margin:0 6px;">|</span>
                <i class="fas fa-key"></i> Contraseña: <strong>123456</strong>
            </div>
        </div>

        <!-- Lado Derecho -->
        <div class="login-right">
            <div class="login-icon-big">
                <img src="<?= BASE ?>/assets/img/logo4.png" alt="MINSUR">
            </div>
            <h2>MINSUR S.A.</h2>
            <p>Reportes de Guardia<br>Mina San Rafael</p>
            <ul class="features">
                <li><i class="fas fa-check"></i> Reportes en tiempo real</li>
                <li><i class="fas fa-check"></i> Control de personal</li>
                <li><i class="fas fa-check"></i> Incidencias</li>
                <li><i class="fas fa-check"></i> Estadísticas</li>
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('videoFondo');
            video.play().catch(function() {
                setTimeout(function() {
                    video.play();
                }, 1000);
            });
        });

        document.addEventListener('click', function() {
            const video = document.getElementById('videoFondo');
            if (video.paused) {
                video.play();
            }
        });
    </script>

</body>
</html>