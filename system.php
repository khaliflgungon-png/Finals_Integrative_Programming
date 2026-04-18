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

// PART 3: QUIZ PROCESSING
function checkAnswers(array $userAnswers, array $correctAnswers): array
{
    $score = 0; $results = []; $total = count($correctAnswers);
    foreach ($correctAnswers as $qIndex => $correct) {
        $given   = $userAnswers[$qIndex] ?? null;
        $correct = trim($correct);
        $isRight = ($given !== null && strtoupper(trim($given)) === strtoupper($correct));
        if ($isRight) $score++;
        $results[$qIndex] = [
            'correct' => $isRight,
            'given'   => $given,
            'expected'=> $correct,
            'skipped' => $given === null,   // FEATURE 6
        ];
    }
    $percentage = $total > 0 ? round(($score / $total) * 100, 1) : 0;
    if      ($percentage >= 90) { $remark = 'Excellent';         $emoji = '🌟'; $color = '#166534'; }
    elseif  ($percentage >= 70) { $remark = 'Good';              $emoji = '👍'; $color = '#1558c0'; }
    else                        { $remark = 'Needs Improvement'; $emoji = '📚'; $color = '#92400e'; }
    return ['score' => $score, 'total' => $total, 'percentage' => $percentage,
            'remark' => $remark, 'emoji' => $emoji, 'color' => $color, 'results' => $results];
}

// FEATURE 1: prepareQuiz with difficulty filter
function prepareQuiz(array $allQuestions, string $difficulty = 'mixed'): array
{
    $selected = [];
    $cats = ($difficulty === 'mixed') ? ['easy', 'medium', 'hard'] : [$difficulty];
    foreach ($cats as $cat) {
        if (!isset($allQuestions[$cat])) continue;
        $pool = $allQuestions[$cat];
        shuffle($pool);
        foreach (array_slice($pool, 0, 5) as $q) {
            $q['category'] = $cat;
            // FEATURE 7: Shuffle answer positions while keeping correct answer tracked
            $correctText = $q['choices'][$q['answer']];
            $keys        = array_keys($q['choices']);
            $values      = array_values($q['choices']);
            shuffle($values);
            $newChoices = []; $newAnswer = $q['answer'];
            foreach ($keys as $ki => $k) {
                $newChoices[$k] = $values[$ki];
                if ($values[$ki] === $correctText) $newAnswer = $k;
            }
            $q['choices'] = $newChoices;
            $q['answer']  = $newAnswer;
            $selected[]   = $q;
        }
    }
    shuffle($selected);
    return $selected;
}

// FEATURE 2: Score history
function addScoreHistory(float $pct, int $score, int $total, string $difficulty): void
{
    if (!isset($_SESSION['score_history'])) $_SESSION['score_history'] = [];
    array_unshift($_SESSION['score_history'], [
        'pct'        => $pct,
        'score'      => $score,
        'total'      => $total,
        'difficulty' => $difficulty,
        'time'       => date('M j, g:i a'),
    ]);
    $_SESSION['score_history'] = array_slice($_SESSION['score_history'], 0, 10);
}

// HANDLE ACTIONS
$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$message = ''; $msgType = '';

if ($action === 'logout')     { session_destroy(); header('Location: '.$_SERVER['PHP_SELF']); exit; }
if ($action === 'reset_lock') { unset($_SESSION['locked'], $_SESSION['login_attempts']); header('Location: '.$_SERVER['PHP_SELF']); exit; }

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = loginSystem(trim($_POST['username'] ?? ''), trim($_POST['password'] ?? ''));
    if ($result['success']) { header('Location: '.$_SERVER['PHP_SELF']); exit; }
    $message = $result['message']; $msgType = 'error';
}

// FEATURE 1: Start quiz with chosen difficulty
if ($action === 'start_quiz' && !empty($_SESSION['logged_in'])) {
    $diff = in_array($_POST['difficulty'] ?? '', ['easy','medium','hard','mixed']) ? $_POST['difficulty'] : 'mixed';
    unset($_SESSION['quiz_questions'], $_SESSION['quiz_results'], $_SESSION['quiz_submitted'],
          $_SESSION['start_time'], $_SESSION['quiz_difficulty']);
    $_SESSION['quiz_difficulty'] = $diff;
    $_SESSION['quiz_questions']  = prepareQuiz($allQuestions, $diff);
    $_SESSION['start_time']      = time();
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

// Retry = go back to difficulty picker
if ($action === 'retry' && !empty($_SESSION['logged_in'])) {
    unset($_SESSION['quiz_questions'], $_SESSION['quiz_results'], $_SESSION['quiz_submitted'],
          $_SESSION['start_time'], $_SESSION['quiz_difficulty']);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
?>
