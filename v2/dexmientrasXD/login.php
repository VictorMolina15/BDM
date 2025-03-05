<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'],
        'passwordHash' => $_POST['password']
    ];

    $jsonData = json_encode($data);

    $ch = curl_init('http://localhost:5000/api/auth/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['token'])) {
        echo "Login exitoso. Token: " . $result['token'];
    } else {
        echo "Error de login";
    }
}
