<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingComment;
use Illuminate\Http\Request;

class MeetingCommentController extends Controller
{
    public function index(Meeting $meeting)
    {
        $comments = $meeting->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'comments' => $comments
        ]);
    }

    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'content' => 'required|string'
        ]);

        $comment = $meeting->comments()->create([
            'content' => $validated['content'],
            'user_id' => auth()->id()
        ]);

        $comment->load('user');

        return response()->json([
            'comment' => $comment
        ]);
    }
} 