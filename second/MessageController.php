<?php

namespace App\Http\Controllers;

use App\Events\MessageSentEvent;
use App\Models\Company;
use App\Models\Feedback;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function textTo(Request $request)
    {
        if (Auth::user()->inn)
        {
            $db = 'users';
            $from = 0;
            $request->validate([
                'to' => ['required', 'exists:'.$db.',id'],
                'text' => 'required'
            ]);
            $user = User::find($request->to);
            $company = Auth::user();
        }
        else
        {
            $db = 'companies';
            $from = 1;
            $request->validate([
                'to' => ['required', 'exists:'.$db.',id'],
                'text' => 'required'
            ]);
            $company = Company::find($request->to);
            $user = Auth::user();
        }
        $resumes = $user->resumes()->pluck('id');
        $vacancies = $company->vacancies()->pluck('id');
        if (!Feedback::whereIn('vacancy_id', $vacancies)->whereIn('resume_id', $resumes)->whereNotNull('answer')->exists())
        {
            return new Response(['message' => 'No feedback'], 403);
        }
        $message = Message::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'from_user' => $from,
            'body' => $request->text,
        ]);
        broadcast(new MessageSentEvent($message, $company, $user));
        return $message;
    }

    public function getChat(Request $request)
    {
        $id = $request->id;
        $user = Auth::user();
        if (Auth::user()->inn)
        {
            return $user->messages()->where('user_id', $id)->get();
        }
        return $user->messages()->where('company_id', $id)->get();
    }

    public function getChats()
    {
        $user = Auth::user();
        if ($user->inn)
        {
            $chats = DB::table('messages')->select('user_id as id')->distinct()->where('company_id', $user->id)->get();
            foreach ($chats as $chat)
            {
                $chat->name = User::find($chat->id)->name;
                $chat->lastmsg = DB::table('messages')
                    ->find(
                        DB::table('messages')
                            ->where([
                                ['company_id', $user->id],
                                ['user_id', $chat->id]])
                            ->max('id')
                    )->body;
            }
        }
        else
        {
            $chats = DB::table('messages')->select('company_id as id')->distinct()->where('user_id', $user->id)->get();
            foreach ($chats as $chat)
            {
                $chat->name = Company::find($chat->id)->name;
                $chat->lastmsg = DB::table('messages')
                    ->find(
                        DB::table('messages')
                            ->where([
                                ['company_id', $chat->id],
                                ['user_id', $user->id]])
                            ->max('id')
                    )->body;
            }
        }
        return $chats;
    }
}
