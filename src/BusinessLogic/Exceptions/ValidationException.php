<?php

class ValidationException extends \Exception
{
    private array $errors;

    public function __construct($message = "", $code = 0, ?\Throwable $previous = null, array $errors = [])
    {
        // PDOException::getCode() returns string (e.g. "42S02") — Exception requires int
        $intCode = is_numeric($code) ? (int) $code : 0;
        parent::__construct((string) $message, $intCode, $previous);
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
