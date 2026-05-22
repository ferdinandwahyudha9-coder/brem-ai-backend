<?php

namespace App\Services;

class ImageService
{
    /**
     * Generate an image URL using Pollinations.ai (free, no API key needed).
     * Returns a URL that the frontend can display directly.
     */
    public function generateImageUrl(string $prompt): string
    {
        $encoded = urlencode($prompt);
        $seed    = rand(1, 999999);

        // Pollinations.ai free image generation endpoint
        return "https://image.pollinations.ai/prompt/{$encoded}?width=1024&height=1024&seed={$seed}&nologo=true";
    }

    /**
     * Detect if a user message is requesting image generation.
     */
    public function isImageRequest(string $message): bool
    {
        $keywords = [
            'generate image', 'create image', 'make image', 'draw',
            'generate a picture', 'create a picture', 'make a picture',
            'generate photo', 'create photo', 'buatkan gambar',
            'buat gambar', 'gambarkan', 'generate gambar',
            'ilustrasi', 'ilustrasikan', 'lukiskan',
        ];

        $lower = strtolower($message);
        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the image prompt from the user message.
     */
    public function extractPrompt(string $message): string
    {
        $prefixes = [
            'generate image of', 'generate image', 'create image of', 'create image',
            'make image of', 'make image', 'draw', 'generate a picture of',
            'generate a picture', 'create a picture of', 'create a picture',
            'generate photo of', 'generate photo', 'create photo of', 'create photo',
            'buatkan gambar', 'buat gambar dari', 'buat gambar',
            'gambarkan', 'generate gambar dari', 'generate gambar',
            'ilustrasi dari', 'ilustrasi', 'ilustrasikan', 'lukiskan',
        ];

        $lower = strtolower($message);
        foreach ($prefixes as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return trim(substr($message, strlen($prefix)));
            }
        }

        // If keyword is in the middle, return the whole message as prompt
        return $message;
    }
}
