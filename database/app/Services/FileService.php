<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser as PdfParser;

class FileService
{
    /**
     * Store uploaded file and extract its text content.
     *
     * @return array{path: string, name: string, type: string, text: string}
     */
    public function processUpload(UploadedFile $file): array
    {
        $type      = $this->detectType($file);
        $filename  = time() . '_' . $file->getClientOriginalName();
        $path      = $file->storeAs('uploads', $filename, 'public');
        $text      = $this->extractText($file, $type);

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'type' => $type,
            'text' => $text,
        ];
    }

    private function detectType(UploadedFile $file): string
    {
        $mime = $file->getMimeType();
        $ext  = strtolower($file->getClientOriginalExtension());

        if ($mime === 'application/pdf' || $ext === 'pdf') return 'pdf';
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return 'image';
        if (in_array($ext, ['doc', 'docx'])) return 'docx';
        if (in_array($ext, ['txt', 'md', 'csv'])) return 'text';

        return 'file';
    }

    private function extractText(UploadedFile $file, string $type): string
    {
        try {
            if ($type === 'pdf') {
                $parser = new PdfParser();
                $pdf    = $parser->parseFile($file->getPathname());
                $text   = $pdf->getText();
                // Limit to 8000 chars to avoid token overflow
                return mb_substr(trim($text), 0, 8000);
            }

            if ($type === 'text') {
                return mb_substr(file_get_contents($file->getPathname()), 0, 8000);
            }

            if ($type === 'image') {
                return '[Image uploaded: ' . $file->getClientOriginalName() . ']';
            }

            if ($type === 'docx') {
                return $this->extractDocxText($file->getPathname());
            }
        } catch (\Exception $e) {
            return '[Could not extract text from file: ' . $e->getMessage() . ']';
        }

        return '[File uploaded: ' . $file->getClientOriginalName() . ']';
    }

    private function extractDocxText(string $path): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                $xml  = $zip->getFromName('word/document.xml');
                $zip->close();
                $text = strip_tags(str_replace(['</w:p>', '</w:tr>'], "\n", $xml));
                return mb_substr(trim($text), 0, 8000);
            }
        } catch (\Exception $e) {
            // fall through
        }
        return '[Could not read DOCX file]';
    }
}
