<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    // Carga los mensajes de un chat específico
    public function conversation($otherUserId)
    {
        $me = auth()->id();
        
        $messages = Message::where(function ($q) use ($me, $otherUserId) {
            $q->where('sender_id', $me)->where('receiver_id', $otherUserId);
        })->orWhere(function ($q) use ($me, $otherUserId) {
            $q->where('sender_id', $otherUserId)->where('receiver_id', $me);
        })
        ->with(['sender', 'receiver'])
        ->orderBy('created_at', 'asc')
        ->get();

        // Marcar mensajes como leídos
        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $me)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Retornamos directamente el array para que Vue lo lea sin confusiones
        return response()->json($messages);
    }

    // Guarda un mensaje nuevo
    public function store(Request $request)
    {
        $data = $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'content' => 'required|string'
        ]);

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $data['receiver_id'],
            'content' => $data['content']
        ]);

        event(new MessageSent($message));

        return response()->json($message, 201);
    }

    // Lista de todas las conversaciones activas (La que daba Error 500)
    public function getConversations()
    {
        $me = auth()->id();

        // Obtenemos todos los mensajes del usuario
        $allMessages = Message::where('sender_id', $me)
            ->orWhere('receiver_id', $me)
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupamos por el ID del contacto
        $groups = $allMessages->groupBy(function ($msg) use ($me) {
            return $msg->sender_id == $me ? $msg->receiver_id : $msg->sender_id;
        });

        $contacts = [];

        foreach ($groups as $userId => $msgs) {
            $lastMsg = $msgs->first();
            $user = User::find($userId);

            if ($user) {
                $contacts[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'last_message' => $lastMsg->content,
                    'last_message_time' => $lastMsg->created_at->format('H:i'),
                    'unread_count' => 0,
                    'latest_raw_date' => $lastMsg->created_at,
                ];
            }
        }

        return response()->json(collect($contacts)->sortByDesc('latest_raw_date')->values());
    }
}