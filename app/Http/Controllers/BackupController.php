<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class BackupController extends Controller
{
    /**
     * List all backup files with metadata.
     */
    public function index()
    {
        $disk = Storage::disk('local');
        $appName = config('backup.backup.name');
        $path = $appName;

        $files = [];
        if ($disk->exists($path)) {
            foreach ($disk->files($path) as $file) {
                if (str_ends_with($file, '.zip')) {
                    $size = $disk->size($file);
                    $lastModified = $disk->lastModified($file);
                    $files[] = [
                        'path'      => $file,
                        'filename'  => basename($file),
                        'size'      => $size,
                        'size_human'=> $this->formatBytes($size),
                        'date'      => Carbon::createFromTimestamp($lastModified)->format('d/m/Y H:i'),
                        'timestamp' => $lastModified,
                        'age'       => Carbon::createFromTimestamp($lastModified)->diffForHumans(),
                    ];
                }
            }
        }

        // Sort by newest first
        usort($files, fn($a, $b) => $b['timestamp'] - $a['timestamp']);

        // Disk usage
        $totalSize = array_sum(array_column($files, 'size'));

        return response()->json([
            'backups'    => $files,
            'total_size' => $this->formatBytes($totalSize),
            'count'      => count($files),
            'disk'       => 'local',
            'path'       => $path,
        ]);
    }

    /**
     * Trigger a manual backup (DB only for speed).
     */
    public function runDbOnly()
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            $output = Artisan::output();
            return response()->json([
                'success' => true,
                'message' => 'Backup de la base de données terminé avec succès.',
                'output'  => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger a full backup (DB + files).
     */
    public function runFull()
    {
        try {
            Artisan::call('backup:run');
            $output = Artisan::output();
            return response()->json([
                'success' => true,
                'message' => 'Backup complet (DB + fichiers) terminé avec succès.',
                'output'  => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a backup file.
     */
    public function download(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $path = $request->input('path');
        $disk = Storage::disk('local');

        if (!$disk->exists($path) || !str_ends_with($path, '.zip')) {
            return response()->json(['message' => 'Fichier introuvable.'], 404);
        }

        return $disk->download($path, basename($path));
    }

    /**
     * Delete a backup file.
     */
    public function destroy(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $path = $request->input('path');
        $disk = Storage::disk('local');

        if (!$disk->exists($path) || !str_ends_with($path, '.zip')) {
            return response()->json(['message' => 'Fichier introuvable.'], 404);
        }

        $disk->delete($path);
        return response()->json(['success' => true, 'message' => 'Backup supprimé.']);
    }

    /**
     * Clean old backups.
     */
    public function clean()
    {
        try {
            Artisan::call('backup:clean');
            return response()->json([
                'success' => true,
                'message' => 'Nettoyage des anciens backups terminé.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
