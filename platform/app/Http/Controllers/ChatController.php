<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Course;
use App\Services\ChatService;
use Illuminate\Http\Request;

/** المحادثات على الموقع: الطالب، والمدرب والأدمن (صندوق الوارد) */
class ChatController extends Controller
{
    public function __construct(private ChatService $chat) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $conversations = $user->conversations()->with(['instructor', 'course', 'lastMessage'])
            ->orderByDesc('last_message_at')->latest()->get();

        return view('chat.index', compact('conversations'));
    }

    public function support(Request $request)
    {
        return redirect()->route('chat.show', $this->chat->support($request->user()));
    }

    public function instructor(Request $request, Course $course)
    {
        return redirect()->route('chat.show', $this->chat->withInstructor($request->user(), $course));
    }

    public function inbox(Request $request)
    {
        $staff = $request->user();
        $conversations = $this->chat->staffScope($staff)
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->boolean('unread'), fn ($q) => $q->where('staff_unread', '>', 0))
            ->whereNotNull('last_message_at')
            ->orderByDesc('last_message_at')->paginate(30)->withQueryString();

        return view('chat.inbox', compact('conversations'));
    }

    public function show(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->chat->authorize($user, $conversation);
        $this->chat->markRead($user, $conversation);
        $conversation->load(['user', 'instructor', 'course']);
        $messages = $this->chat->messages($conversation)->map(fn ($m) => $this->chat->present($m, $user));
        $staffView = $conversation->user_id !== $user->id;

        return view($staffView ? 'chat.staff-show' : 'chat.show', compact('conversation', 'messages'));
    }

    public function poll(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->chat->authorize($user, $conversation);
        $this->chat->markRead($user, $conversation);
        $list = $this->chat->messages($conversation, (int) $request->query('after', 0));

        return response()->json(['messages' => $list->map(fn ($m) => $this->chat->present($m, $user))]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $request->validate(['body' => ['required', 'string', 'max:4000']], [], ['body' => 'الرسالة']);
        $m = $this->chat->send($request->user(), $conversation, $request->input('body'));

        return $request->expectsJson()
            ? response()->json(['message' => $this->chat->present($m->load('sender'), $request->user())])
            : back();
    }
}
