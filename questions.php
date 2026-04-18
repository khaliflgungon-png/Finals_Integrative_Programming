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
?>
