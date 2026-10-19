
<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit;
}

$fullName = trim($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

$error = '';

if (
    $fullName === '' ||
    $email === '' ||
    $phone === '' ||
    $dob === '' ||
    $gender === '' ||
    $password === '' ||
    $confirmPassword === ''
) {
    $error = 'Please fill in all fields.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address.';
} elseif (strlen($password) < 8) {
    $error = 'Your password must be at least 8 characters long.';
} elseif ($password !== $confirmPassword) {
    $error = 'Your passwords do not match.';
} elseif (!in_array($gender, [
    'female', 'male', 'other', 'prefer-not-to-say'
], true)) {
    $error = 'Please select a valid gender option.';
} else {
    $date = DateTime::createFromFormat('!Y-m-d', $dob);
    $dateErrors = DateTime::getLastErrors();

    if (
        !$date ||
        ($dateErrors !== false &&
        ($dateErrors['warning_count'] > 0 ||
         $dateErrors['error_count'] > 0)) ||
        $date->format('Y-m-d') !== $dob ||
        $dob > date('Y-m-d')
    ) {
        $error = 'Please enter a valid date of birth.';
    }
}

if ($error !== '') {
    exit(htmlspecialchars($error, ENT_QUOTES, 'UTF-8')
        . ' <a href="register.html">Go back</a>');
}

try {
    $pdo->beginTransaction();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $userStmt = $pdo->prepare(
        'INSERT INTO users (email, password_hash, role)
         VALUES (?, ?, ?)'
    );

    $userStmt->execute([$email, $passwordHash, 'patient']);

    $userId = $pdo->lastInsertId();

    $patientStmt = $pdo->prepare(
        'INSERT INTO patients
         (user_id, full_name, phone, date_of_birth, gender)
         VALUES (?, ?, ?, ?, ?)'
    );

    $patientStmt->execute([
        $userId,
        $fullName,
        $phone,
        $dob,
        $gender
    ]);

    $pdo->commit();

    echo 'Registration successful! Your patient account has been created. ';
    echo '<a href="login.html">Log in here</a>.';

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Registration database error: ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        echo 'That email address may already be registered. ';
        echo '<a href="register.html">Go back</a>.';
    } else {
        http_response_code(500);
        echo 'Registration could not be completed. Please try again later.';
    }
}
