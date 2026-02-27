<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Read application log file (admin only).
     * Returns last N lines of the Laravel log.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super admin access required.'], 403);
        }

        $lines = min((int) ($request->query('lines', 200)), 1000);
        $level = $request->query('level'); // error, warning, info, debug, etc.
        $search = $request->query('search');

        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return response()->json([
                'service' => 'auth-service',
                'entries' => [],
                'file_size' => 0,
                'message' => 'No log file found.',
            ]);
        }

        $fileSize = filesize($logFile);
        $rawLines = $this->tailFile($logFile, $lines * 3); // read more raw lines to get enough parsed entries

        // Parse log entries
        $entries = $this->parseLogEntries($rawLines);

        // Filter by level
        if ($level) {
            $entries = array_values(array_filter($entries, fn($e) => strtolower($e['level']) === strtolower($level)));
        }

        // Filter by search
        if ($search) {
            $entries = array_values(array_filter($entries, fn($e) =>
                stripos($e['message'], $search) !== false ||
                stripos($e['context'], $search) !== false
            ));
        }

        // Return only the requested number of entries
        $entries = array_slice($entries, -$lines);

        return response()->json([
            'service' => 'auth-service',
            'entries' => $entries,
            'file_size' => $fileSize,
            'file_size_human' => $this->humanFileSize($fileSize),
            'total_entries' => count($entries),
        ]);
    }

    /**
     * Clear the log file (super_admin only).
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        return response()->json(['message' => 'Log file cleared.']);
    }

    /**
     * Read last N lines from a file efficiently.
     */
    private function tailFile(string $path, int $lines): array
    {
        $result = [];
        $fp = fopen($path, 'r');
        if (!$fp) return [];

        // Seek from end
        $pos = -1;
        $lineCount = 0;
        $buffer = '';

        fseek($fp, 0, SEEK_END);
        $fileSize = ftell($fp);

        if ($fileSize === 0) {
            fclose($fp);
            return [];
        }

        while ($lineCount < $lines && abs($pos) <= $fileSize) {
            fseek($fp, $pos, SEEK_END);
            $char = fgetc($fp);
            if ($char === "\n" && $buffer !== '') {
                $result[] = $buffer;
                $buffer = '';
                $lineCount++;
            } else {
                $buffer = $char . $buffer;
            }
            $pos--;
        }

        if ($buffer !== '') {
            $result[] = $buffer;
        }

        fclose($fp);

        return array_reverse($result);
    }

    /**
     * Parse raw log lines into structured entries.
     */
    private function parseLogEntries(array $lines): array
    {
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            // Match Laravel log format: [2026-02-27 12:00:00] production.ERROR: Message
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s+\w+\.(\w+):\s(.*)$/', $line, $matches)) {
                if ($current) {
                    $entries[] = $current;
                }
                $current = [
                    'timestamp' => $matches[1],
                    'level' => strtolower($matches[2]),
                    'message' => $matches[3],
                    'context' => '',
                ];
            } elseif ($current) {
                // Stack trace or continuation line
                $current['context'] .= ($current['context'] ? "\n" : '') . $line;
            }
        }

        if ($current) {
            $entries[] = $current;
        }

        return $entries;
    }

    private function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
