<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    private string $serviceName = 'wallet-service';

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || ($user->role ?? '') !== 'super_admin') {
            return response()->json(['message' => 'Unauthorized. Super admin access required.'], 403);
        }

        $lines = min((int) ($request->query('lines', 200)), 1000);
        $level = $request->query('level');
        $search = $request->query('search');
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return response()->json(['service' => $this->serviceName, 'entries' => [], 'file_size' => 0, 'message' => 'No log file found.']);
        }

        $fileSize = filesize($logFile);
        $rawLines = $this->tailFile($logFile, $lines * 3);
        $entries = $this->parseLogEntries($rawLines);

        if ($level) $entries = array_values(array_filter($entries, fn($e) => strtolower($e['level']) === strtolower($level)));
        if ($search) $entries = array_values(array_filter($entries, fn($e) => stripos($e['message'], $search) !== false || stripos($e['context'], $search) !== false));
        $entries = array_slice($entries, -$lines);

        return response()->json(['service' => $this->serviceName, 'entries' => $entries, 'file_size' => $fileSize, 'file_size_human' => $this->humanFileSize($fileSize), 'total_entries' => count($entries)]);
    }

    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || ($user->role ?? '') !== 'super_admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) file_put_contents($logFile, '');
        return response()->json(['message' => 'Log file cleared.']);
    }

    private function tailFile(string $path, int $lines): array
    {
        $result = []; $fp = fopen($path, 'r'); if (!$fp) return [];
        fseek($fp, 0, SEEK_END); $fileSize = ftell($fp);
        if ($fileSize === 0) { fclose($fp); return []; }
        $pos = -1; $lineCount = 0; $buffer = '';
        while ($lineCount < $lines && abs($pos) <= $fileSize) {
            fseek($fp, $pos, SEEK_END); $char = fgetc($fp);
            if ($char === "\n" && $buffer !== '') { $result[] = $buffer; $buffer = ''; $lineCount++; }
            else { $buffer = $char . $buffer; }
            $pos--;
        }
        if ($buffer !== '') $result[] = $buffer;
        fclose($fp); return array_reverse($result);
    }

    private function parseLogEntries(array $lines): array
    {
        $entries = []; $current = null;
        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s+\w+\.(\w+):\s(.*)$/', $line, $matches)) {
                if ($current) $entries[] = $current;
                $current = ['timestamp' => $matches[1], 'level' => strtolower($matches[2]), 'message' => $matches[3], 'context' => ''];
            } elseif ($current) { $current['context'] .= ($current['context'] ? "\n" : '') . $line; }
        }
        if ($current) $entries[] = $current;
        return $entries;
    }

    private function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB']; $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
