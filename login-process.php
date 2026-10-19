
<?php
session_start();

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $_SESSION['error'] = 'Enter a valid email and password.';
    header('Location: login.html');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, password
         FROM patients
         WHERE email = :email
         LIMIT 1'
    );

    $stmt->execute([':email' => $email]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient || !password_verify($password, $patient['password'])) {
        $_SESSION['error'] = 'Invalid email or password.';
        header('Location: login.html');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['patient_id'] = $patient['id'];
    $_SESSION['patient_name'] = $patient['full_name'];
    $_SESSION['patient_email'] = $patient['email'];

    header('Location: patient-dashboard.php');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Login could not be completed. Please try again later.');
}
