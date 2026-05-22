<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Services\FileService;
use App\Services\ImageService;
use App\Services\OpenAIService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected OpenAIService $openAI,
        protected ImageService  $imageService,
        protected FileService   $fileService,
    ) {}

    /** List all chats for the authenticated user. */
    public function index(Request $request)
    {
        $chats = $request->user()
            ->chats()
            ->latest()
            ->get(['id', 'title', 'created_at']);

        return response()->json($chats);
    }

    /** Get a single chat with its messages. */
    public function show(Request $request, Chat $chat)
    {
        $this->authorize('view', $chat);
        $chat->load('messages');
        return response()->json($chat);
    }

    /** Create a new chat session. */
    public function store(Request $request)
    {
        $request->validate(['title' => 'nullable|string|max:255']);

        $chat = $request->user()->chats()->create([
            'title' => $request->input('title', 'New Chat'),
        ]);

        return response()->json($chat, 201);
    }

    /** Send a message (with optional file upload) and get AI response. */
    public function send(Request $request, Chat $chat)
    {
        $this->authorize('view', $chat);

        $request->validate([
            'message' => 'nullable|string|max:10000',
            'file'    => 'nullable|file|max:20480|mimes:pdf,jpg,jpeg,png,gif,webp,txt,md,csv,doc,docx',
        ]);

        $messageText = $request->input('message', '');
        $fileData    = null;
        $contextText = '';

        // ── Handle file upload ──────────────────────────────────────────
        if ($request->hasFile('file')) {
            $fileData    = $this->fileService->processUpload($request->file('file'));
            $contextText = $fileData['text'];
        }

        if (!$messageText && !$fileData) {
            return response()->json(['message' => 'Message or file is required.'], 422);
        }

        // ── Save user message ───────────────────────────────────────────
        $userMessage = $chat->messages()->create([
            'role'      => 'user',
            'content'   => $messageText ?: ('Uploaded file: ' . $fileData['name']),
            'file_path' => $fileData['path'] ?? null,
            'file_name' => $fileData['name'] ?? null,
            'file_type' => $fileData['type'] ?? null,
        ]);

        // ── Check if user wants image generation ────────────────────────
        if ($messageText && $this->imageService->isImageRequest($messageText)) {
            $prompt   = $this->imageService->extractPrompt($messageText);
            $imageUrl = $this->imageService->generateImageUrl($prompt);

            $assistantMessage = $chat->messages()->create([
                'role'      => 'assistant',
                'content'   => "Here's your generated image for: **{$prompt}**",
                'image_url' => $imageUrl,
            ]);

            $this->autoTitle($chat, $messageText);

            return response()->json([
                'user_message'      => $userMessage,
                'assistant_message' => $assistantMessage,
            ]);
        }

        // ── Build conversation history ──────────────────────────────────
        $history = $chat->messages()
            ->orderBy('created_at')
            ->get(['role', 'content'])
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        // Inject file content as extra context in the last user message
        if ($contextText) {
            $lastIdx = count($history) - 1;
            $history[$lastIdx]['content'] =
                "The user uploaded a file. Here is its content:\n\n```\n{$contextText}\n```\n\n" .
                ($messageText ? "User's question: {$messageText}" : "Please summarize this file.");
        }

        // ── Call AI ─────────────────────────────────────────────────────
        try {
            $aiResponse = $this->openAI->chat($history);
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            return response()->json(['message' => 'Rate limit exceeded. Please wait and try again.'], 429);
        } catch (\Exception $e) {
            return response()->json(['message' => 'AI error: ' . $e->getMessage()], 500);
        }

        $assistantMessage = $chat->messages()->create([
            'role'    => 'assistant',
            'content' => $aiResponse,
        ]);

        $this->autoTitle($chat, $messageText ?: $fileData['name']);

        return response()->json([
            'user_message'      => $userMessage,
            'assistant_message' => $assistantMessage,
        ]);
    }

    /** Delete a chat. */
    public function destroy(Request $request, Chat $chat)
    {
        $this->authorize('delete', $chat);
        $chat->delete();
        return response()->json(['message' => 'Chat deleted']);
    }

    /** Rename a chat. */
    public function update(Request $request, Chat $chat)
    {
        $this->authorize('update', $chat);
        $request->validate(['title' => 'required|string|max:255']);
        $chat->update(['title' => $request->title]);
        return response()->json($chat);
    }

    /** Auto-set chat title from first message. */
    private function autoTitle(Chat $chat, string $text): void
    {
        if ($chat->messages()->count() <= 2 && $chat->title === 'New Chat') {
            $chat->update(['title' => mb_substr($text, 0, 60)]);
        }
    }
}
