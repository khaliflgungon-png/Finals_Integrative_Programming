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

// FEATURE 3: Accept partial submission (no minimum answer count required)
if ($action === 'submit_quiz' && !empty($_SESSION['logged_in']) && empty($_SESSION['quiz_submitted'])) {
    $questions      = $_SESSION['quiz_questions'] ?? [];
    $userAnswers    = $_POST['answers'] ?? [];
    $correctAnswers = array_column($questions, 'answer');
    $quizResult                 = checkAnswers($userAnswers, $correctAnswers);
    $quizResult['questions']    = $questions;
    addScoreHistory($quizResult['percentage'], $quizResult['score'], $quizResult['total'], $_SESSION['quiz_difficulty'] ?? 'mixed');
    $_SESSION['quiz_results']   = $quizResult;
    $_SESSION['quiz_submitted'] = true;
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

// State
$isLoggedIn     = !empty($_SESSION['logged_in']);
$isLocked       = !empty($_SESSION['locked']);
$quizSubmitted  = !empty($_SESSION['quiz_submitted']);
$quizResults    = $_SESSION['quiz_results'] ?? null;
$questions      = $_SESSION['quiz_questions'] ?? [];
$startTime      = $_SESSION['start_time'] ?? time();
$attempts       = $_SESSION['login_attempts'] ?? 0;
$quizDifficulty = $_SESSION['quiz_difficulty'] ?? '';
$scoreHistory   = $_SESSION['score_history'] ?? [];
$diffPicked     = !empty($quizDifficulty);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PHP Online Quiz System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Sora:wght@300;400;600;800&display=swap" rel="stylesheet">
<style>
    :root {
        --bg:      #eef2fb;
        --surface: #ffffff;
        --card:    #ffffff;
        --border:  #adbad6;
        --accent:  #1558c0;
        --accent2: #3730a3;
        --success: #166534;
        --warning: #92400e;
        --danger:  #991b1b;
        --text:    #0a0f1e;
        --muted:   #2d3a52;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Sora', sans-serif;
        background: var(--bg); color: var(--text);
        min-height: 100vh; display: flex; flex-direction: column; align-items: center;
        padding: 2rem 1rem 4rem;
        background-image:
          radial-gradient(ellipse 80% 50% at 20% -10%, rgba(21,88,192,.07) 0%, transparent 60%),
          radial-gradient(ellipse 60% 40% at 80% 110%, rgba(55,48,163,.06) 0%, transparent 60%);
    }

      /* HEADER */
      .site-header { width:100%; max-width:900px; display:flex; align-items:center; justify-content:space-between; padding:0 0 2rem; }
      .logo { font-family:'Space Mono',monospace; font-size:1.1rem; color:var(--accent); }
      .logo span { color:var(--muted); }

      /* CARD */
      .card { width:100%; max-width:900px; background:var(--card); border:1.5px solid var(--border); border-radius:1.25rem; padding:2.5rem; box-shadow:0 8px 32px rgba(21,88,192,.10),0 2px 8px rgba(0,0,0,.05); }
      .login-container { display:flex; justify-content:center; width:100%; }
      .card.login-card  { max-width:520px; padding:2rem; }

      h1 { font-size:2rem; font-weight:800; line-height:1.1; margin-bottom:.5rem; color:var(--text); }
      h2 { font-size:1.3rem; font-weight:700; margin-bottom:1rem; color:var(--text); }
      p  { color:var(--muted); line-height:1.7; }
    
      /* INPUTS */
      .form-group { margin-bottom:1.25rem; }
      label { display:block; font-size:.82rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--muted); margin-bottom:.45rem; }
      input[type="text"], input[type="password"] {
        width:100%; padding:.75rem 1rem; background:#f5f8ff;
        border:2px solid var(--border); border-radius:.6rem;
        color:var(--text); font-family:'Space Mono',monospace;
        font-size:.95rem; outline:none; transition:border-color .2s,background .2s;
      }
      input:focus { border-color:var(--accent); background:#fff; }

      /* BUTTONS */
      .btn { display:inline-flex; align-items:center; gap:.5rem; padding:.75rem 1.75rem; 
            border:none; border-radius:.65rem; font-family:'Sora',sans-serif; font-weight:700; 
            font-size:.95rem; cursor:pointer; transition:opacity .15s,transform .1s; text-decoration:none; }
      .btn:hover  { opacity:.88; transform:translateY(-1px); }
      .btn:active { transform:translateY(0); }
      .btn-primary   { background:var(--accent); color:#fff; }
      .btn-secondary { background:#f0f4ff; color:var(--text); border:2px solid var(--border); }
      .btn-danger    { background:var(--danger); color:#fff; }

      /* MESSAGES */
      .msg { padding:.9rem 1.1rem; border-radius:.65rem; margin-bottom:1.5rem; font-size:.95rem; font-weight:700; display:flex; align-items:center; gap:.6rem; }
      .msg-error   { background:#fef2f2; border:2px solid #fca5a5; color:#7f1d1d; }
      .msg-warning { background:#fffbeb; border:2px solid #fcd34d; color:#78350f; }
      .msg-success { background:#f0fdf4; border:2px solid #86efac; color:#14532d; }

      /* ATTEMPTS BAR */
      .attempts-bar { display:flex; gap:.45rem; margin-top:1rem; }
      .attempt-dot { width:12px; height:12px; border-radius:50%; background:var(--danger); transition:background .3s; }
      .attempt-dot.used { background:var(--border); }

      /* LOCK */
      .lock-icon { font-size:4rem; text-align:center; margin-bottom:1rem; }
    
      /* BADGES */
      .cat-badge { display:inline-block; padding:.25rem .75rem; border-radius:99px; font-size:.75rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; margin-bottom:.75rem; }
      .cat-easy   { background:#dcfce7; color:#166534; border:1.5px solid #86efac; }
      .cat-medium { background:#fef3c7; color:#92400e; border:1.5px solid #fcd34d; }
      .cat-hard   { background:#fee2e2; color:#991b1b; border:1.5px solid #ff4b4b; }
      .cat-mixed  { background:#ede9fe; color:#3730a3; border:1.5px solid #000000; }

      /* ── FEATURE 1: DIFFICULTY PICKER ── */
      .diff-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; margin:1.5rem 0; }
      @media(max-width:500px){ .diff-grid{grid-template-columns:1fr;} }
      .diff-card {
        border:2.5px solid var(--border); border-radius:1rem; padding:1.4rem 1rem;
        cursor:pointer; transition:all .2s; background:#fff; text-align:center;
        position:relative; user-select:none;
      }
      .diff-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(21,88,192,.13); }
      .diff-card input[type="radio"] { position:absolute; opacity:0; pointer-events:none; }
      .diff-card.sel-easy   { border-color:#166534; background:#f0fdf4; box-shadow:0 0 0 3px rgba(22,101,52,.15); }
      .diff-card.sel-medium { border-color:#92400e; background:#fffbeb; box-shadow:0 0 0 3px rgba(146,64,14,.15); }
      .diff-card.sel-hard   { border-color:#991b1b; background:#fef2f2; box-shadow:0 0 0 3px rgba(153,27,27,.15); }
      .diff-card.sel-mixed  { border-color:#3730a3; background:#ede9fe; box-shadow:0 0 0 3px rgba(0, 0, 0, 0); }
      .diff-icon  { font-size:2.4rem; margin-bottom:.5rem; }
      .diff-title { font-weight:800; font-size:1.05rem; margin-bottom:.25rem; }
      .diff-desc  { font-size:.82rem; color:var(--muted); }
    
      /* ── FEATURE 2: HISTORY TABLE ── */
      .history-table { width:100%; border-collapse:collapse; margin-top:.75rem; font-size:.88rem; }
      .history-table th { text-align:left; padding:.5rem .75rem; font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); border-bottom:2px solid var(--border); }
      .history-table td { padding:.6rem .75rem; border-bottom:1px solid #e5eaf5; }
      .history-table tr:last-child td { border-bottom:none; }
      .history-table tr:hover td { background:#f5f8ff; }
      .hist-pct { font-family:'Space Mono',monospace; font-weight:800; }
      .hist-excellent { color:#166534; }
      .hist-good      { color:#1558c0; }
      .hist-poor      { color:#92400e; }
    
      /* ── FIXED TOP BAR (quiz screen) ── */
      .fixed-bar { position:fixed; top:0; left:0; right:0; background:#fff; border-bottom:2px solid var(--border); padding:.9rem 2rem; z-index:100; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 12px rgba(21,88,192,.08); }
      .fixed-bar-left { flex:1; min-width:0; }
      .fixed-bar-right { display:flex; gap:.75rem; align-items:center; margin-left:2rem; flex-shrink:0; }
    
      /* Timer bar */
      .bar-label { font-family:'Space Mono',monospace; font-size:.8rem; font-weight:700; color:var(--muted); margin-bottom:.3rem; }
      .bar-track { background:#dce6f5; border-radius:99px; height:9px; overflow:hidden; border:1px solid var(--border); margin-bottom:.4rem; }
      .bar-fill  { height:100%; border-radius:99px; transition:width 1s linear, background .5s; }
      #timer-bar { background:var(--accent); width:100%; }
    
      /* ── FEATURE 5: PROGRESS BAR ── */
      #progress-bar { background:var(--success); width:0%; transition:width .4s ease; }
      .progress-label { font-family:'Space Mono',monospace; font-size:.78rem; font-weight:700; color:var(--muted); }
    
      /* ── QUESTION CARD ── */
      .q-card { background:#f8faff; border:1.5px solid var(--border); border-radius:1rem; padding:1.5rem; margin-bottom:1.25rem; transition:border-color .2s,box-shadow .2s; }
      .q-card:hover { border-color:var(--accent); box-shadow:0 4px 16px rgba(21,88,192,.08); }
      /* FEATURE 4: Unanswered = gray styling */
      .q-card.unanswered { border-color:#9ca3af; background:#f3f4f6; }
      .q-card.unanswered .q-number { color:#9ca3af; }
      .q-number { font-size:.78rem; font-weight:800; letter-spacing:.1em; color:var(--muted); text-transform:uppercase; margin-bottom:.5rem; }
      .q-text   { font-size:1.05rem; font-weight:700; margin-bottom:1rem; line-height:1.55; color:var(--text); }
    
      /* CHOICES */
      .choices { display:grid; grid-template-columns:1fr 1fr; gap:.65rem; }
      @media(max-width:540px){ .choices{grid-template-columns:1fr;} }
      .choice-label { display:flex; align-items:center; gap:.75rem; padding:.75rem 1rem; border:1.5px solid var(--border); border-radius:.6rem; cursor:pointer; transition:border-color .15s,background .15s; font-size:.95rem; font-weight:600; color:var(--text); background:#fff; }
      .choice-label:hover { border-color:var(--accent); background:#eef4ff; }
      .choice-label input[type="radio"] { accent-color:var(--accent); width:18px; height:18px; flex-shrink:0; }
      /* Results coloring */
      .choice-label.correct { border-color:#166534; background:#f0fdf4; color:#14532d; }
      .choice-label.wrong   { border-color:#991b1b; background:#fef2f2; color:#7f1d1d; }
      /* FEATURE 4 & 6: Unanswered/skipped = gray */
      .choice-label.skipped { border-color:#9ca3af; background:#f9fafb; color:#6b7280; }
      .choice-letter { font-family:'Space Mono',monospace; font-weight:800; font-size:.85rem; color:var(--accent); min-width:1.25rem; flex-shrink:0; }
      .choice-label.skipped .choice-letter  { color:#9ca3af; }
      .choice-label.correct .choice-letter  { color:#166534; }
      .choice-label.wrong   .choice-letter  { color:#991b1b; }
    
      /* Skipped question label */
      .skipped-tag { display:inline-flex; align-items:center; gap:.35rem; padding:.25rem .7rem; background:#f3f4f6; border:1.5px solid #9ca3af; border-radius:99px; font-size:.78rem; font-weight:800; color:#6b7280; margin-bottom:.5rem; }
    
      /* ── FEATURE 8: RESULT ANIMATIONS ── */
      .result-badge  { text-align:center; font-size:3.5rem; margin-bottom:.4rem; opacity:0; animation:fadeUp .5s ease .1s forwards; }
      .result-remark { text-align:center; font-size:1.65rem; font-weight:800; color:var(--text); margin-bottom:.25rem; opacity:0; animation:fadeUp .5s ease .2s forwards; }
      .result-sub    { text-align:center; color:var(--muted); margin-bottom:.5rem; opacity:0; animation:fadeUp .4s ease .35s forwards; }
    
      /* Animated score number */
      .result-score {
        font-family:'Space Mono',monospace; font-size:4.5rem; font-weight:700;
        text-align:center; margin:.75rem 0;
        background:linear-gradient(135deg,var(--accent),var(--accent2));
        -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        opacity:0; transform:scale(.5) translateY(20px);
        animation:scoreReveal .75s cubic-bezier(.34,1.56,.64,1) .45s forwards;
      }
      @keyframes scoreReveal { to { opacity:1; transform:scale(1) translateY(0); } }
      @keyframes fadeUp { from{opacity:0;transform:translateY(14px);} to{opacity:1;transform:translateY(0);} }
    
      /* Percentage ring */
      .ring-wrap { display:flex; justify-content:center; margin:.5rem 0 1rem; opacity:0; animation:fadeUp .5s ease .55s forwards; }
      .ring-svg  { transform:rotate(-90deg); }
      .ring-bg   { fill:none; stroke:#dce6f5; stroke-width:11; }
      .ring-fill { fill:none; stroke:var(--accent); stroke-width:11; stroke-linecap:round; stroke-dasharray:283; stroke-dashoffset:283; transition:stroke-dashoffset 1.4s cubic-bezier(.4,0,.2,1) .9s; }
      .ring-label { font-family:'Space Mono',monospace; font-size:1.1rem; font-weight:700; fill:var(--text); dominant-baseline:middle; text-anchor:middle; }
    
      .result-meta { display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; margin:1rem 0 1.75rem; }
      .result-pill { padding:.5rem 1.25rem; border-radius:99px; font-size:.88rem; font-weight:700; border:1.5px solid var(--border); background:#f0f4ff; color:var(--text); opacity:0; animation:fadeUp .4s ease forwards; }
      .result-pill:nth-child(1){animation-delay:.7s;}
      .result-pill:nth-child(2){animation-delay:.8s;}
      .result-pill:nth-child(3){animation-delay:.9s;}
      .result-pill:nth-child(4){animation-delay:1.0s;}
    
      /* EXPLAIN */
      .explain-box { margin-top:.75rem; padding:.75rem 1rem; background:#eff6ff; border-left:4px solid var(--accent); border-radius:0 .5rem .5rem 0; font-size:.88rem; color:#1e3a6e; line-height:1.7; font-weight:600; }
    
      hr { border:none; border-top:2px solid var(--border); margin:2rem 0; }
      .actions { display:flex; gap:1rem; flex-wrap:wrap; margin-top:2rem; }
      .user-pill { font-size:.82rem; font-weight:700; background:#f0f4ff; border:1.5px solid var(--border); border-radius:99px; padding:.4rem 1rem; color:var(--muted); display:flex; align-items:center; gap:.5rem; }
      .user-pill span { color:var(--accent); }
</style>
</head>
<body>
    
<header class="site-header">
  <?php if ($isLoggedIn && !empty($questions)): ?>
  <div style="display:flex;gap:.75rem;align-items:center;">
    <div class="user-pill">👤 Logged in as <span>student</span></div>
    <a href="?action=logout" class="btn btn-secondary" style="padding:.4rem 1rem;font-size:.8rem;">Logout</a>
  </div>
  <?php endif; ?>
</header>

<?php if (!$isLoggedIn): ?>
<!-- ══════════ LOGIN ══════════ -->
<div class="login-container">
  <div class="card login-card">
    <div class="logo" style="margin-bottom:1.5rem;">&lt;<span>PHP</span>Quiz /&gt;</div>
    <div style="margin-bottom:1.75rem;">
      <div style="font-size:2.5rem;margin-bottom:.5rem;">🔐</div>
      <h1>Online Quiz</h1>
      <p>Access the PHP Quiz System. You have <?= MAX_ATTEMPTS ?> attempts.</p>
    </div>

    <?php if ($isLocked): ?>
      <div class="lock-icon">🔒</div>
      <div class="msg msg-error" style="justify-content:center;flex-direction:column;text-align:center;gap:.25rem;">
        <strong>Account Locked</strong>
        <small>Maximum login attempts reached. Contact your instructor.</small>
      </div>
      <div style="text-align:center;"><a href="?action=reset_lock" class="btn btn-secondary">🔄 Reset (for testing)</a></div>
    <?php else: ?>
      <?php if ($message): ?><div class="msg msg-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" placeholder="student" autocomplete="off" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <?php if ($attempts > 0): ?>
        <div style="margin-bottom:1rem;">
          <small style="color:var(--muted);">Attempts used:</small>
          <div class="attempts-bar">
            <?php for ($i = 0; $i < MAX_ATTEMPTS; $i++): ?>
              <div class="attempt-dot <?= $i < $attempts ? '' : 'used' ?>"></div>
            <?php endfor; ?>
          </div>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary" style="width:100%;">Login →</button>
      </form>
      <p style="text-align:center;font-size:.8rem;margin-top:1.25rem;font-family:'Space Mono',monospace;">
        Hint: <code style="color:var(--accent);">student</code> / <code style="color:var(--accent);">quiz123</code>
      </p>
    <?php endif; ?>
  </div>
</div>

    <?php elseif (!$diffPicked): ?>
<!-- FEATURE 1 & 2: DIFFICULTY PICKER + HISTORY -->
<div class="card">
  <div style="margin-bottom:1.75rem;">
    <div style="font-size:2.2rem;margin-bottom:.5rem;">🎯</div>
    <h1>Choose Difficulty</h1>
    <p>Select a difficulty level. Each mode has up to 5 questions with shuffled answer positions.</p>
  </div>

  <form method="POST" id="diff-form">
    <input type="hidden" name="action" value="start_quiz">
    <div class="diff-grid">

      <label class="diff-card" id="dc-easy" onclick="selectDiff('easy')">
        <input type="radio" name="difficulty" value="easy" id="d-easy">
        <div class="diff-icon">🟢</div>
        <div class="diff-title" style="color:#166534;">Easy</div>
        <div class="diff-desc">Basic PHP concepts — great for beginners.</div>
      </label>

      <label class="diff-card" id="dc-medium" onclick="selectDiff('medium')">
        <input type="radio" name="difficulty" value="medium" id="d-medium">
        <div class="diff-icon">🟡</div>
        <div class="diff-title" style="color:#92400e;">Medium</div>
        <div class="diff-desc">Loops, functions, and superglobals.</div>
      </label>

      <label class="diff-card" id="dc-hard" onclick="selectDiff('hard')">
        <input type="radio" name="difficulty" value="hard" id="d-hard">
        <div class="diff-icon">🔴</div>
        <div class="diff-title" style="color:#991b1b;">Hard</div>
        <div class="diff-desc">Advanced PHP — types, headers, security.</div>
      </label>

      <label class="diff-card sel-mixed" id="dc-mixed" onclick="selectDiff('mixed')">
        <input type="radio" name="difficulty" value="mixed" id="d-mixed" checked>
        <div class="diff-icon">⚫</div>
        <div class="diff-title" style="color:#000000;">Mixed</div>
        <div class="diff-desc">All difficulty levels shuffled together.</div>
      </label>

    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;font-size:1.05rem;padding:.9rem;">
      Start Quiz →
    </button>
  </form>

  <!-- FEATURE 2: Score History -->
  <?php if (!empty($scoreHistory)): ?>
  <hr>
  <h2>📜 Score History</h2>
  <table class="history-table">
    <thead>
      <tr>
        <th>#</th><th>Score</th><th>%</th><th>Difficulty</th><th>Remark</th><th>When</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($scoreHistory as $hi => $h):
      $hRemark = $h['pct'] >= 90 ? 'Excellent' : ($h['pct'] >= 70 ? 'Good' : 'Needs Improvement');
      $hCls    = $h['pct'] >= 90 ? 'hist-excellent' : ($h['pct'] >= 70 ? 'hist-good' : 'hist-poor');
    ?>
      <tr>
        <td style="color:var(--muted);font-weight:700;font-size:.82rem;"><?= $hi + 1 ?></td>
        <td><strong><?= $h['score'] ?>/<?= $h['total'] ?></strong></td>
        <td><span class="hist-pct <?= $hCls ?>"><?= $h['pct'] ?>%</span></td>
        <td><span class="cat-badge cat-<?= htmlspecialchars($h['difficulty']) ?>"><?= ucfirst(htmlspecialchars($h['difficulty'])) ?></span></td>
        <td style="font-weight:600;"><?= $hRemark ?></td>
        <td style="color:var(--muted);font-size:.82rem;"><?= htmlspecialchars($h['time']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

    <script>
function selectDiff(d) {
  ['easy','medium','hard','mixed'].forEach(function(v){
    document.getElementById('dc-'+v).className = 'diff-card' + (v===d ? ' sel-'+v : '');
  });
  document.getElementById('d-'+d).checked = true;
}
</script>
