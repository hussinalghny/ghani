<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Course;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private ChatService $chat) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $list = $user->conversations()->with(['instructor', 'course', 'lastMessage'])
            ->orderByDesc('last_message_at')->latest()->get();

        return response()->json([
            'data' => $list->map(fn ($c) => $this->chat->presentConversation($c, $user)),
            'unread' => (int) $list->sum('student_unread'),
        ]);
    }

    public function support(Request $request)
    {
        $c = $this->chat->support($request->user());

        return response()->json(['data' => $this->chat->presentConversation($c->load('lastMessage'), $request->user())]);
    }

    public function instructor(Request $request, Course $course)
    {
        $c = $this->chat->withInstructor($request->user(), $course);

        return response()->json(['data' => $this->chat->presentConversation($c->load(['instructor', 'course', 'lastMessage']), $request->user())]);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->chat->authorize($user, $conversation);
        $this->chat->markRead($user, $conversation);
        $list = $this->chat->messages($conversation, (int) $request->query('after', 0));

        return response()->json(['data' => $list->map(fn ($m) => $this->chat->present($m, $user))]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $request->validate(['body' => ['required', 'string', 'max:4000']]);
        $m = $this->chat->send($request->user(), $conversation, $request->input('body'));

        return response()->json(['data' => $this->chat->present($m->load('sender'), $request->user())], 201);
    }
}
