<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\MessageSent;
use Carbon\Carbon;

class MessageController extends Controller
{
    // Fetch all messages
    public function index()
    {
        $messages = Message::with('user')->orderBy('id', 'asc')->get(); // Fetch with user details
        return response()->json($messages);
    }

    // Send a new message
    public function sendMessage(Request $request)
    {

        $request->validate([
            'message' => 'string' . (!$request->hasFile('file') ? '|required' : '|nullable'),
            'file' => 'nullable|file|max:2048',
            'reply_to' => 'nullable|exists:messages,id',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('messages', 'public');
        }

        $message = Message::create([
            'user_id' => Auth::id(),
            'message' => $request->message,
            'file' => $filePath,
            'reply_to' => $request->reply_to,
        ]);

        // echo '<pre>';
        // print_r($message);
        // die();
        if (!$message) {
            return response()->json(['error' => 'Message not created'], 500);
        }

        try {
            broadcast(new MessageSent($message))->toOthers(); // Load user info
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            Log::error('Broadcasting Error: ' . $e->getMessage());
            return response()->json(['error' => 'Broadcast failed', 'details' => $e->getMessage()], 500);
        }
    }

    // Fetch messages (alternative to index)
    public function fetchMessages()
    {
        $users = User::all();
        $messages = Message::with(['user', 'replies.user'])
            ->whereNull('reply_to') // Only fetch parent messages
            ->orderBy('created_at', 'asc')
            ->get();
        return response()->json([
            'messages' => $messages,
            'users' => $users
        ]);
    }
    public function deleteMessage(Request $request, $id)
    {
        $message = Message::findOrFail($id);

        // Check if the message belongs to the authenticated user and is within 5 minutes
        if ($message->user_id == Auth::id() && Carbon::parse($message->created_at)->diffInMinutes(Carbon::now()) <= 5) {
            // Mark the message as deleted
            $message->deleted_at = Carbon::now();  // Mark deletion timestamp
            $message->save();

            // Broadcast the updated message to others
            broadcast(new MessageSent($message))->toOthers();

            return response()->json(['success' => true, 'message' => $message]);
        }

        return response()->json(['error' => 'Unauthorized or message is too old to delete'], 403);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'reply_to' => 'nullable|exists:messages,id', // Ensure valid parent message ID
        ]);

        $message = Message::create([
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'reply_to' => $validated['reply_to'] ?? null, // Store reply_to if it's a reply
        ]);

        // Broadcast event (optional)
        broadcast(new MessageSent($message->load('user', 'replies')))->toOthers();

        return response()->json($message);
    }
    public function allusers()
    {
        $users = User::all();
        return response()->json($users);
    }
}
