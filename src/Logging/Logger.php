<?php
class Logger
{
    private string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
    }

    public function log(string $action, string $message, array $context = []): void
    {
        $logMessage = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($action),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        error_log($logMessage, 3, $this->logPath);
    }
}
//namespace App\Logging;