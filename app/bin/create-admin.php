<?php

declare(strict_types=1);

/**
 * Crea (o asciende) un usuario administrador.
 *
 *   docker compose exec web php bin/create-admin.php
 *
 * Pide usuario, email y contraseña por consola. Sustituye al admin con contraseña fija
 * que creaba antes init.sql: una credencial conocida en un repositorio público es una
 * puerta abierta en cuanto alguien despliega sin cambiarla.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require __DIR__ . '/../config/app.php';
require __DIR__ . '/../config/database.php';

function ask(string $label, bool $hidden = false): string
{
    echo $label;
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        shell_exec('stty -echo');
        $value = trim((string)fgets(STDIN));
        shell_exec('stty echo');
        echo PHP_EOL;

        return $value;
    }

    return trim((string)fgets(STDIN));
}

$username = ask('Usuario (3-50, letras, números y _): ');
$email    = strtolower(ask('Email: '));
$password = ask('Contraseña (mínimo 12 caracteres): ', true);

if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
    fwrite(STDERR, "Usuario no válido.\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Email no válido.\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "La contraseña de un administrador debe tener al menos 12 caracteres.\n");
    exit(1);
}

$db   = Database::getConnection();
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$email, $username]);
$existing = $stmt->fetchColumn();

if ($existing !== false) {
    $db->prepare('UPDATE users SET role = \'admin\', active = 1, password = ? WHERE id = ?')
       ->execute([$hash, (int)$existing]);
    echo "Usuario existente ascendido a administrador.\n";
} else {
    $db->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, \'admin\')')
       ->execute([$username, $email, $hash]);
    echo "Administrador creado.\n";
}
