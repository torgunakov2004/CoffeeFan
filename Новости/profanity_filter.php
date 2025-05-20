<?php

function containsProfanity($text, $profanityList) {
    if (empty($text) || empty($profanityList)) {
        return false;
    }

    $normalizedText = $text;

    $normalizedText = function_exists('mb_strtolower') ? mb_strtolower($normalizedText, 'UTF-8') : strtolower($normalizedText);

    $replacements = [
        '@' => 'а',
        '(' => 'с',
        '0' => 'о',
        '1' => 'л',
        '3' => 'з',
        '4' => 'ч',
        '5' => 's',
        '6' => 'б',
        '8' => 'в',
        'q' => 'к',
        'w' => 'в',
        'u' => 'и',
        'i' => 'и',
        'o' => 'о',
        'a' => 'а',
        'e' => 'е',
        'c' => 'с',
        'k' => 'к',
        'x' => 'х',
        'y' => 'у',
        '$' => 's',
        '€' => 'е',
        '|' => 'l',
        '#' => 'х',
    ];
    $normalizedText = str_replace(array_keys($replacements), array_values($replacements), $normalizedText);
    $normalizedText = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalizedText);
    foreach ($profanityList as $word) {
        $trimmedWord = trim($word);
        $pattern = '/\b' . preg_quote($trimmedWord, '/') . '\b/u'; 
        if (preg_match($pattern, $normalizedText)) {
            return true;
        }
    }
    return false;
}
?>