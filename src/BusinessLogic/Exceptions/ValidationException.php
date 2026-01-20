<?php

class ValidationException extends \Exception
{
    private array $errors;

    public function __construct($message = "", $code = 0, \Throwable $previous = null, array $errors = [])
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFormattedErrors(): string
    {
        return implode("\n", $this->errors);
    }
}