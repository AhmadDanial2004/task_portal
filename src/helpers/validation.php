<?php

// Escapes output before rendering HTML to prevent XSS
function sanitize(string $input): string //return type string, function sanitizes input by trimming whitespace and converting special characters to HTML entities to prevent XSS attacks
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8'); //UTF-8 encoding ensures proper handling of multibyte characters
}

// Checks if required fields are present and not empty
function validateRequired(array $requiredFields, array $data): array //return type array, function validates required fields and returns an array of error messages
{
    $errors = [];
    foreach ($requiredFields as $field) //loop through each required field and check if it is set and not empty in the provided data
    {
        if (!isset($data[$field]) || trim($data[$field]) === '') 
        {
            $errors[] = "The " . str_replace('_', ' ', $field) . " field is required.";
        }
    }
    return $errors;
}

// Validates email format
function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; //built-in PHP function filter_var with FILTER_VALIDATE_EMAIL checks if the email is in a valid format, returns true if valid, false otherwise
}
?>