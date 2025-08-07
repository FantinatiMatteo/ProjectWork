<?php
/**
 * Script temporaneo per generare password hash corretti
 * Esegui questo script per ottenere gli hash da inserire nel database
 */

// Password da hashare
$passwords = [
    'Admin@123!' => 'admin@ticketing.local',
    'User@123!' => 'user@ticketing.local', 
    'Test@123!' => 'test@ticketing.local'
];

echo "<h2>Password Hash Generator</h2>";
echo "<p>Copia questi comandi SQL e eseguili in phpMyAdmin:</p>";
echo "<textarea style='width: 100%; height: 200px; font-family: monospace;'>";

foreach ($passwords as $password => $email) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "UPDATE users SET password_hash = '$hash' WHERE email = '$email';\n";
}

echo "</textarea>";

echo "<h3>Credenziali di accesso:</h3>";
echo "<ul>";
foreach ($passwords as $password => $email) {
    echo "<li><strong>$email</strong> → <code>$password</code></li>";
}
echo "</ul>";

echo "<p><strong>Dopo aver eseguito gli SQL:</strong></p>";
echo "<ol>";
echo "<li>Elimina questo file (fix_passwords.php)</li>";
echo "<li>Vai su <a href='login.php'>login.php</a></li>";
echo "<li>Prova ad accedere con le credenziali sopra</li>";
echo "</ol>";
?>
