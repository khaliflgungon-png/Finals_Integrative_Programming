<!--
Bañganan. John Mcnesse - Frontend
Butuhan, Nick Andrei - Quality Assurance
Delacruz, Aljen Peter - Business Analyst
Gungon, Khalif - Backend
--->
<?php
session_start();
$allQuestions = require 'questions.php';
// CONFIGURATION
define('VALID_USERNAME', 'student');
define('VALID_PASSWORD', 'quiz123');
define('MAX_ATTEMPTS', 3);
define('QUIZ_TIME_LIMIT', 60); // seconds

// PART 1: LOGIN SYSTEM
function loginSystem(string $username, string $password): array
{
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
    if (!isset($_SESSION['locked']))         $_SESSION['locked']         = false;

    if ($_SESSION['locked']) return ['success' => false, 'message' => 'LOCKED'];

    if ($username === VALID_USERNAME && $password === VALID_PASSWORD) {
        $_SESSION['logged_in']      = true;
        $_SESSION['login_attempts'] = 0;
        $_SESSION['locked']         = false;
        return ['success' => true, 'message' => 'Login successful!'];
    }

    $_SESSION['login_attempts']++;
    $remaining = MAX_ATTEMPTS - $_SESSION['login_attempts'];
    if ($_SESSION['login_attempts'] >= MAX_ATTEMPTS) {
        $_SESSION['locked'] = true;
        return ['success' => false, 'message' => 'LOCKED'];
    }
    return ['success' => false, 'message' => "Invalid credentials. {$remaining} attempt(s) remaining.", 'remaining' => $remaining];
}
?>
