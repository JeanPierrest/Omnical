<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso - Omnicanal</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #E2E8F0; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
        .login-box h2 { color: #0064AF; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; font-size: 13px; font-weight: bold; color: #555; margin-bottom: 8px; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #CBD5E1; border-radius: 4px; box-sizing: border-box; }
        .btn-login { background-color: #0064AF; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-login:hover { background-color: #004D86; }
        .error-msg { background-color: #FDEDEC; color: #C0392B; padding: 10px; border-radius: 4px; font-size: 12px; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="login-box">
        <h2>🎫 Omnicanal</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg">Correo o contraseña incorrectos.</div>
        <?php endif; ?>

        <form action="index.php?accion=procesar_login" method="POST">
            <div class="form-group">
                <label>Correo Electrónico:</label>
                <input type="email" name="correo" required placeholder="Ej: juan@empresa.com">
            </div>
            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" required placeholder="******">
            </div>
            <button type="submit" class="btn-login">Ingresar al Sistema</button>
        </form>
    </div>

</body>
</html>