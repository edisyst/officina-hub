<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoDviMedia;
use App\Http\Controllers\Controller;
use App\Models\DviIspezione;
use App\Models\DviMedia;
use App\Models\DviVoce;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DviUploadController extends Controller
{
    public function uploadChunk(Request $request): JsonResponse
    {
        $request->validate([
            'chunk'        => 'required|file',
            'chunk_index'  => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'upload_id'    => 'required|string|max:40',
            'voce_id'      => 'required|integer|exists:dvi_voci,id',
        ]);

        $voce = DviVoce::findOrFail($request->voce_id);
        $ispezione = $voce->ispezione;

        // Verifica che l'utente abbia accesso alla DVI
        $this->authorize('update', $ispezione);

        $uploadId   = preg_replace('/[^a-zA-Z0-9\-]/', '', $request->upload_id);
        $chunkIndex = (int) $request->chunk_index;
        $totalChunks = (int) $request->total_chunks;

        $tmpDir = 'tmp/dvi/' . $uploadId;
        $chunkName = 'chunk_' . $chunkIndex;

        Storage::disk('local')->putFileAs($tmpDir, $request->file('chunk'), $chunkName);

        if ($chunkIndex + 1 < $totalChunks) {
            return response()->json(['status' => 'partial', 'chunk' => $chunkIndex]);
        }

        // Ultimo chunk: assembla il file
        return $this->assembla($uploadId, $tmpDir, $totalChunks, $voce, $ispezione, $request);
    }

    private function assembla(
        string $uploadId,
        string $tmpDir,
        int $totalChunks,
        DviVoce $voce,
        DviIspezione $ispezione,
        Request $request
    ): JsonResponse {
        $anno  = now()->format('Y');
        $mese  = now()->format('m');
        $destDir = "dvi/video/{$anno}/{$mese}";

        $ext      = 'mp4';
        $filename = Str::uuid() . '.' . $ext;
        $destPath = $destDir . '/' . $filename;
        $fullDest = Storage::disk('local')->path($destPath);

        Storage::disk('local')->makeDirectory($destDir);

        $fp = fopen($fullDest, 'wb');
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = Storage::disk('local')->path($tmpDir . '/chunk_' . $i);
            if (! file_exists($chunkPath)) {
                fclose($fp);
                return response()->json(['error' => 'Chunk mancante: ' . $i], 422);
            }
            fwrite($fp, file_get_contents($chunkPath));
        }
        fclose($fp);

        // Pulisci i chunk temporanei
        Storage::disk('local')->deleteDirectory($tmpDir);

        $dimensione = filesize($fullDest);

        // Thumbnail: prova ffmpeg, altrimenti placeholder SVG
        $thumbnailPath = $this->generaThumbnail($fullDest, $destDir, $filename);

        $media = DviMedia::create([
            'dvi_voce_id'      => $voce->id,
            'dvi_ispezione_id' => $ispezione->id,
            'tipo'             => TipoDviMedia::Video,
            'percorso'         => $destPath,
            'nome_file'        => $filename,
            'mime_type'        => 'video/mp4',
            'dimensione_bytes' => $dimensione,
            'thumbnail_path'   => $thumbnailPath,
            'user_id'          => auth()->id(),
        ]);

        return response()->json([
            'status'   => 'complete',
            'media_id' => $media->id,
            'thumbnail' => $thumbnailPath ? route('dvi.media.thumb', $media->id) : null,
        ]);
    }

    private function generaThumbnail(string $videoPath, string $destDir, string $videoFilename): ?string
    {
        try {
            $thumbFilename = pathinfo($videoFilename, PATHINFO_FILENAME) . '_thumb.jpg';
            $thumbRelPath  = $destDir . '/' . $thumbFilename;
            $thumbFullPath = Storage::disk('local')->path($thumbRelPath);

            $ffmpeg = trim(shell_exec('which ffmpeg 2>/dev/null') ?? '');
            if (empty($ffmpeg)) {
                $ffmpeg = '/usr/bin/ffmpeg';
            }

            if (! file_exists($ffmpeg)) {
                return null;
            }

            $cmd = escapeshellarg($ffmpeg)
                . ' -i ' . escapeshellarg($videoPath)
                . ' -ss 00:00:01 -vframes 1 -q:v 2 '
                . escapeshellarg($thumbFullPath)
                . ' 2>/dev/null';

            exec($cmd, $out, $code);

            return file_exists($thumbFullPath) ? $thumbRelPath : null;
        } catch (\Throwable $e) {
            Log::warning('DVI thumbnail generation failed: ' . $e->getMessage());
            return null;
        }
    }
}
