<?php
return [
    'easy' => [
        [
            'question' => 'What does PHP stand for?',
            'choices'  => ['A' => 'Personal Home Page', 'B' => 'PHP: Hypertext Preprocessor', 'C' => 'Private Hypertext Protocol', 'D' => 'Public HTML Page'],
            'answer'   => 'B',
            'explain'  => 'PHP originally stood for "Personal Home Page" but now stands for "PHP: Hypertext Preprocessor" (a recursive acronym).',
        ],
        [
            'question' => 'Which symbol is used to declare a variable in PHP?',
            'choices'  => ['A' => '#', 'B' => '@', 'C' => '$', 'D' => '&'],
            'answer'   => 'C',
            'explain'  => 'In PHP, variables always start with the dollar sign ($), e.g., $name = "Alice";',
        ],
        [
            'question' => 'Which of the following is used to output text in PHP?',
            'choices'  => ['A' => 'print_text()', 'B' => 'echo', 'C' => 'console.log()', 'D' => 'display()'],
            'answer'   => 'B',
            'explain'  => '"echo" is used to output one or more strings in PHP.',
        ],
        [
            'question' => 'What is the correct way to end a PHP statement?',
            'choices'  => ['A' => '.', 'B' => ':', 'C' => ';', 'D' => ','],
            'answer'   => 'C',
            'explain'  => 'PHP statements end with a semicolon (;), just like C, Java, and other languages.',
        ],
        [
            'question' => 'Which superglobal holds data sent via an HTML form using the POST method?',
            'choices'  => ['A' => '$_GET', 'B' => '$_SESSION', 'C' => '$_POST', 'D' => '$_REQUEST'],
            'answer'   => 'C',
            'explain'  => '$_POST collects form data sent with the HTTP POST method.',
        ],
    ],
    'medium' => [
        [
            'question' => 'What does a "for" loop require to function properly?',
            'choices'  => ['A' => 'Only a condition', 'B' => 'Initialization, condition, and increment', 'C' => 'Only a counter variable', 'D' => 'A break statement'],
            'answer'   => 'B',
            'explain'  => 'A for loop has three parts: initialization (e.g., $i=0), condition ($i<5), and increment ($i++).',
        ],
        [
            'question' => 'Which PHP function is used to count the number of elements in an array?',
            'choices'  => ['A' => 'length()', 'B' => 'sizeof()', 'C' => 'count()', 'D' => 'Both B and C'],
            'answer'   => 'D',
            'explain'  => 'Both count() and sizeof() return the number of elements in an array. sizeof() is an alias of count().',
        ],
        [
            'question' => 'How do you start a PHP session?',
            'choices'  => ['A' => 'start_session()', 'B' => 'session_start()', 'C' => '$_SESSION = true', 'D' => 'session_init()'],
            'answer'   => 'B',
            'explain'  => 'session_start() must be called before any output to initialize or resume a session.',
        ],
        [
            'question' => 'Which of the following correctly defines a function in PHP?',
            'choices'  => ['A' => 'def myFunc() {}', 'B' => 'function: myFunc() {}', 'C' => 'function myFunc() {}', 'D' => 'func myFunc() {}'],
            'answer'   => 'C',
            'explain'  => 'PHP uses the "function" keyword followed by the function name and parentheses.',
        ],
        [
            'question' => 'What does $_GET do in PHP?',
            'choices'  => ['A' => 'Collects data from POST forms', 'B' => 'Gets the server IP', 'C' => 'Collects data sent via the URL query string', 'D' => 'Retrieves session data'],
            'answer'   => 'C',
            'explain'  => '$_GET collects data appended to the URL (e.g., page.php?name=John).',
        ],
    ],
    'hard' => [
        [
            'question' => 'What is the difference between "==" and "===" in PHP?',
            'choices'  => ['A' => 'No difference', 'B' => '"==" checks value only; "===" checks value AND type', 'C' => '"===" checks value only; "==" checks value AND type', 'D' => '"===" is used for arrays only'],
            'answer'   => 'B',
            'explain'  => '"==" is loose comparison (1 == "1" is true); "===" is strict (1 === "1" is false because types differ).',
        ],
        [
            'question' => 'Which function sends a raw HTTP header in PHP?',
            'choices'  => ['A' => 'send_header()', 'B' => 'http_header()', 'C' => 'header()', 'D' => 'set_header()'],
            'answer'   => 'C',
            'explain'  => 'header() sends a raw HTTP header. It must be called before any output.',
        ],
        [
            'question' => 'What is the purpose of htmlspecialchars() in PHP?',
            'choices'  => ['A' => 'Formats HTML tables', 'B' => 'Converts special characters to HTML entities to prevent XSS', 'C' => 'Removes HTML tags', 'D' => 'Encodes a string to Base64'],
            'answer'   => 'B',
            'explain'  => 'htmlspecialchars() converts characters like <, >, &, " into HTML entities, preventing Cross-Site Scripting (XSS) attacks.',
        ],
        [
            'question' => 'Which of the following is NOT a valid PHP loop?',
            'choices'  => ['A' => 'for', 'B' => 'foreach', 'C' => 'repeat...until', 'D' => 'while'],
            'answer'   => 'C',
            'explain'  => 'PHP has for, foreach, while, and do-while loops. "repeat...until" is not a PHP construct.',
        ],
        [
            'question' => 'What will var_dump(true) output in PHP?',
            'choices'  => ['A' => 'true', 'B' => '1', 'C' => 'bool(true)', 'D' => 'TRUE'],
            'answer'   => 'C',
            'explain'  => 'var_dump() displays the type AND value. For a boolean true, it outputs: bool(true).',
        ],
    ],
];
?>
