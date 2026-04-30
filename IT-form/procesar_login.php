<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Verifica el usuario y la contraseña
  $usuario = $_POST["usuario"];
  $contrasena = $_POST["contrasena"];

  // Lee la contraseña desde el archivo 'secret'
  $contrasenaGuardada = trim(file_get_contents('secret'));

  if ($usuario === "itspanama" && $contrasena === $contrasenaGuardada) {
    // Inicio de sesión exitoso, redirige al formulario
    header("Location: index.html");
    exit;
  } else {
    // Credenciales incorrectas, muestra un mensaje de error
    echo "Credenciales incorrectas. Intenta nuevamente.";
  }
}
?>
