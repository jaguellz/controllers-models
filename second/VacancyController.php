<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Feedback;
use App\Models\Message;
use App\Models\Resume;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isEmpty;

class VacancyController extends Controller
{
    public function createVacancy(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'city' => ['required'],
            'grade' => ['required'],
            'stage' => ['required'],
            'schedule' => ['required'],
            'category' => ['required', 'exists:categories,id'],
            'sphere' => ['required', 'exists:spheres,id'],
            'body' => ['required'],
            'minsalary' => ['required'],
            'maxsalary' => ['required'],
            'type' => ['required'],
            'abilities' => ['required']
        ]);
        return Vacancy::create([
            'name' => $request->name,
            'city' => $request->city,
            'grade' => $request->grade,
            'stage' => $request->stage,
            'schedule' => $request->schedule,
            'category' => $request->category,
            'sphere_id' => $request->sphere,
            'body' => $request->body,
            'company_id' => Auth::user()->id,
            'minsalary' => $request->minsalary,
            'maxsalary' => $request->maxsalary,
            'type' => $request->type,
            'abilities' => $request->abilities,
        ]);
    }

    public function changeVacancy(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:vacancies,id'],
            'category' => ['exists:categories,id'],
            'sphere' => ['exists:spheres,id'],
        ]);
        $vacancy = Vacancy::find($request->id);
        if ($vacancy->company_id != Auth::user()->id)
        {
            return new Response(['Access denied'], 403);
        }
        $vacancy->grade = $request->grade ?:$vacancy->grade;
        $vacancy->stage = $request->stage ?:$vacancy->stage;
        $vacancy->body = $request->body ?: $vacancy->body;
        $vacancy->name = $request->name ?: $vacancy->name;
        $vacancy->city = $request->city ?: $vacancy->city;
        $vacancy->schedule = $request->schedule ?: $vacancy->schedule;
        $vacancy->category = $request->category ?: $vacancy->category;
        $vacancy->sphere_id = $request->sphere ?: $vacancy->sphere_id;
        $vacancy->maxsalary = $request->maxsalary ?: $vacancy->maxsalary;
        $vacancy->minsalary = $request->minsalary ?: $vacancy->minsalary;
        $vacancy->type = $request->type ?: $vacancy->type;
        $vacancy->abilities = $request->abilities ?: $vacancy->abilities;
        $vacancy->save();
        return Vacancy::with('category', 'company')->find($vacancy->id);
    }

    public function activateVacancy(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:vacancies,id'],
        ]);
        $vacancy = Vacancy::find($request->id);
        if ($vacancy->company_id != Auth::user()->id)
        {
            return new Response(['Access denied'], 403);
        }
        $vacancy->active = 1;
        $vacancy->save();
        return Vacancy::with('category', 'company')->find($request->id);
    }

    public function deactivateVacancy(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:vacancies,id'],
        ]);
        $vacancy = Vacancy::find($request->id);
        if ($vacancy->company_id != Auth::user()->id)
        {
            return new Response(['Access denied'], 403);
        }
        $vacancy->active = 0;
        $vacancy->save();
        return Vacancy::with('category', 'company')->find($request->id);
    }

    public function getCompanyVacancies()
    {
        $company = Company::find(Auth::user()->id);
        return $company->vacancies()->with('company', 'category')->get();
    }

    public function searchVacancies(Request $request)
    {
        $request->validate([
            'sphere' => ['exists:spheres,id'],
            'category' => ['exists:categories,id'],
        ]);
        $vacancies = Vacancy::with('company')->with('category')/*->select('name','salary','body', 'company_id')*/;
        $q = [
            ['active', 1],
            $request->body ? ['body', 'like', "%".$request->body."%"] : null,
        ];
        if ($request->stage){
            $vacancies = $vacancies->whereIn('stage', $request->stage);
        }
        if ($request->city){
            $vacancies = $vacancies->whereIn('city', $request->city);
        }
        if ($request->schedule){
            $vacancies = $vacancies->whereIn('schedule', $request->schedule);
        }
        if ($request->sphere){
            $vacancies = $vacancies->whereIn('sphere_id', $request->sphere);
        }
        if ($request->category){
            $vacancies = $vacancies->whereIn('category', $request->category);
        }
        if ($request->type){
            $vacancies = $vacancies->whereIn('type', $request->type);
        }
        $q = array_filter($q);
        $q = array_values($q);
        return $vacancies->where($q)->paginate(5);
    }

    public function getVacancies()
    {
        return Auth::user()->vacancies()->with('company', 'category')->get();
    }

    public function getFeedbacks(Request $request)
    {
        $request->validate([
            'vacancy' => ['required', 'exists:vacancies,id'],
        ]);
        $feedbacks = Feedback::with(['resume.additionals', 'resume.grades', 'resume.stages', 'vacancy.category', 'vacancy.company'])->where('vacancy_id', $request->vacancy)->get();

        if (!(Auth::user()->subscribe > Carbon::now()))
        {
            foreach ($feedbacks as $feedback){
                $feedback->user = Resume::find($feedback->resume_id)->user->name;
            }
            return $feedbacks;
        }
        foreach ($feedbacks as $feedback){
            $feedback->user = Resume::find($feedback->resume_id)->user;
        }
        return $feedbacks;
    }

    public function popularVacancies()
    {
        return Vacancy::with('company', 'category')->where('active', 1)->orderByDesc('views')->paginate(5);
    }

    public function getVacancy(Request $request)
    {
        $request->validate([
            'vacancy' => ['required', 'exists:vacancies,id'],
        ]);
        $vac = Vacancy::with('category', 'company')->find($request->vacancy);
        $vac->views++;
        $vac->save();
        return $vac;
    }

    public function makeFeedback(Request $request)
    {
        $request->validate([
            'vacancy' => ['required', 'exists:vacancies,id'],
            'resume' => ['required', 'exists:resumes,id'],
            'answer' => ['required'],
            'contact_type' => ['required'],
            'contact' => ['required'],
            'date' => ['required', 'date'],
        ]);
        $user = Resume::find($request->resume)->user;
        $company = Auth::user();
        $expires = explode(' ', Carbon::tomorrow())[0].' '.explode(' ', Carbon::now())[1];
        $message = Message::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'from_user' => 0,
            'body' => $request->answer,
        ]);
        $feedback = Feedback::create([
            'vacancy_id' => $request->vacancy,
            'resume_id' => $request->resume,
            'answer' => $request->answer,
            'contact_type' => $request->contact_type,
            'contact' => $request->contact,
            'date' => $request->date,
            'expires_at' => $expires,
        ]);
        return $feedback;
    }

    public function answerFeedback(Request $request)
    {
        $request->validate([
            'vacancy' => ['required', 'exists:vacancies,id'],
            'resume' => ['required', 'exists:resumes,id'],
            'answer' => ['required'],
            'contact_type' => ['required'],
            'contact' => ['required'],
            'date' => ['required', 'date'],
        ]);
        if (!(Auth::user()->subscribe > Carbon::now()))
        {
            return new Response([
                'message' => 'No subscription'
            ], 403);
        }
        $user = Resume::find($request->resume)->user;
        $company = Auth::user();
        $expires = explode(' ', Carbon::tomorrow())[0].' '.explode(' ', Carbon::now())[1];
        $feedback = Feedback::where('vacancy_id', $request->vacancy)->where('resume_id', $request->resume)->first();
        $feedback->answer = $request->answer;
        $message = Message::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'from_user' => 0,
            'body' => $request->answer,
        ]);
        $feedback->contact_type = $request->contact_type;
        $feedback->contact = $request->contact;
        $feedback->date = $request->date;
        $feedback->expires_at = $expires;
        $feedback->save();
        return $feedback;
    }


    public function recommendedVacancies()
    {
        $user = Auth::user();
        if (!$user->resumes()->exists())
        {
            return $this->popularVacancies();
        }
        $result = [];
        $resumes = $user->resumes()->limit(1)->get();
        $q = Vacancy::with('category', 'company');
        foreach ($resumes as $resume)
        {
            $q->where('category', $resume->category_id);
        }
        return $q->paginate(5);
    }
}
