<?php 
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Dashboard Ejecutivo</title>
    <style>
        :root {
            --bg-main: #0B0E14;
            --bg-card: rgba(21, 26, 37, 0.8);
            --border-color: #2A3143;
            --text-main: #FFFFFF;
            --text-muted: #8E96A8;
            --brand-color: #8b5cf6;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body {
            background-color: var(--bg-main);
            background-image: radial-gradient(circle at 50% -20%, #2c1a4d 0%, var(--bg-main) 40%);
            height: 100vh; display: flex; align-items: center; justify-content: center; color: var(--text-main);
        }

        .login-container {
            background: var(--bg-card); backdrop-filter: blur(10px);
            border: 1px solid var(--border-color); border-radius: 16px;
            padding: 40px; width: 100%; max-width: 400px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); text-align: center;
        }

        .login-logo { font-size: 3rem; margin-bottom: 10px; }
        .login-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 5px; }
        .login-subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 30px; }

        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;}
        .input-group input {
            width: 100%; padding: 12px 15px; background: rgba(0,0,0,0.2);
            border: 1px solid var(--border-color); border-radius: 8px; color: white;
            font-size: 1rem; outline: none; transition: border 0.3s;
        }
        .input-group input:focus { border-color: var(--brand-color); background: rgba(139, 92, 246, 0.05); }

        .btn-login {
            width: 100%; padding: 12px; background: var(--brand-color);
            color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: bold;
            cursor: pointer; transition: 0.2s; display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-login:hover { background: #7c3aed; }
        .btn-login:disabled { background: #4c2889; cursor: not-allowed; color: #a1a1aa; }

        .error-msg {
            color: #ef4444; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 10px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 20px; display: none;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-logo">🐧</div>
        <h1 class="login-title">Portal Ejecutivo</h1>
        <p class="login-subtitle">Ingresa tus credenciales para continuar</p>

        <div id="error-box" class="error-msg"></div>

        <form id="loginForm">
            <div class="input-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" placeholder="Ej: ANALISTA_KPI" autocomplete="off" required>
            </div>
            
            <div class="input-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" id="btnSubmit" class="btn-login">
                Iniciar Sesión
            </button>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const form = e.target;
            const btn = document.getElementById('btnSubmit');
            const errorBox = document.getElementById('error-box');
            
            // Estado de carga
            btn.disabled = true;
            btn.innerHTML = 'Validando... ⏳';
            errorBox.style.display = 'none';

            const formData = new FormData(form);

            try {
                const response = await fetch('api/login_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();

                if (data.status === 'success') {
                    btn.innerHTML = '¡Acceso Concedido! ✓';
                    btn.style.background = '#10b981'; 
                    
                    // Redirigir al dashboard tras 1 segundo
                    setTimeout(() => {
                        window.location.href = 'index.php'; 
                    }, 1000);
                } else {
                    // Mostrar error
                    errorBox.textContent = data.message;
                    errorBox.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = 'Iniciar Sesión';
                }
            } catch (err) {
                errorBox.textContent = 'Error de conexión. Intenta de nuevo.';
                errorBox.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = 'Iniciar Sesión';
            }
        });
    </script>
</body>
</html>