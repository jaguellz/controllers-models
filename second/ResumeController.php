<?php

namespace App\Http\Controllers;

use App\Models\Additional;
use App\Models\Category;
use App\Models\Company;
use App\Models\Feedback;
use App\Models\Grade;
use App\Models\Resume;
use App\Models\Stage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\isEmpty;

class ResumeController extends Controller
{
    public function createResume(Request $request)
    {
        $request->validate([
            'abilities' => 'required',
            'body' => 'required',
            'stages.*.company_name' => 'required',
            'stages.*.position' => 'required',
            'stages.*.description' => 'required',
            'stages.*.period' => 'required',
            'grades.*.university_name' => 'required',
            'grades.*.grade' => 'required',
            'grades.*.period' => 'required',
            'stages' => 'required',
            'grades' => 'required',
            'city' => 'required',
            'name' => 'required',
            'category' => ['required', 'exists:categories,id'],
            'sphere' => ['required', 'exists:spheres,id'],
            'email' => ['required', 'email'],
            'phone' => ['required', /*'regex:/[7-8]{1}[0-9]{10}/',*/ 'size:11'],
        ]);
        $resume = Resume::create([
            'name' => $request->name,
            'user_id' => Auth::user()->id,
            'body' => $request->body,
            'abilities' => $request->abilities,
            'email' => $request->email,
            'phone' => $request->phone,
            'city' => $request->city,
            'category_id' => $request->category,
            'sphere_id' => $request->sphere,
        ]);
        if ($request->stages){
            foreach ($request->stages as $stage) {
                Stage::create([
                    'company_name' => $stage['company_name'],
                    'position' => $stage['position'],
                    'description' => $stage['description'],
                    'period' => $stage['period'],
                    'resume_id' => $resume->id,
                ]);
            }
        }
        if ($request->grades){
            foreach ($request->grades as $grade) {
                Grade::create([
                    'university_name' => $grade['university_name'],
                    'grade' => $grade['grade'],
                    'period' => $grade['period'],
                    'resume_id' => $resume->id,
                ]);
            }
        }
        return Resume::with(['grades', 'stages'])->find($resume->id);
    }

    public function deleteFile(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:additionals,id'],
        ]);
        $additional = Additional::find($request->id);
        if ($additional->resume->user->id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $z = str_replace('/storage/', 'public/', $additional->url);
        Storage::delete($z);
        $additional->delete();
        return new Response(['message' => "Successfully deleted"]);
    }

    public function addPdf(Request $request)
    {
        $request->validate([
            'resume' => ['required', 'exists:resumes,id'],
            'pdf' => ['required', 'mimes:pdf']
        ]);
        if (Resume::find($request->resume)->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $path = $request->file('pdf')->storePublicly('public/additional/'.$request->resume);
        $path = Storage::url($path);
        return Additional::create([
            'resume_id' => $request->resume,
            'name' => 'pdf',
            'url' => url($path),
        ]);
    }

    public function addVideo(Request $request)
    {
        $request->validate([
            'resume' => ['required', 'exists:resumes,id'],
            'video' => ['required', 'mimes:mp4s']
        ]);
        if (Resume::find($request->resume)->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $path = $request->file('video')->storePublicly('public/additional/'.$request->resume);
        $path = Storage::url($path);
        return Additional::create([
            'resume_id' => $request->resume,
            'name' => 'video',
            'url' => url($path),
        ]);
    }

    public function addStage(Request $request)
    {
        $request->validate([
            'stages' => 'required',
            'stages.*.company_name' => 'required',
            'stages.*.position' => 'required',
            'stages.*.description' => 'required',
            'stages.*.period' => 'required',
            'resume' => ['required', 'exists:resumes,id']
        ]);
        if (Resume::find($request->resume)->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $stages = [];
        foreach ($request->stages as $stage)
        {
            array_push($stages, Stage::create([
                'company_name' => $stage['company_name'],
                'position' => $stage['position'],
                'description' => $stage['description'],
                'period' => $stage['period'],
                'resume_id' => $request->resume,
            ]));
        }
        return $stages;
    }

    public function addGrade(Request $request)
    {
        $request->validate([
            'grades' => 'required',
            'grades.*.university_name' => 'required',
            'grades.*.grade' => 'required',
            'grades.*.period' => 'required',
            'resume' => ['required', 'exists:resumes,id']
        ]);
        if (Resume::find($request->resume)->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $grades = [];
        foreach ($request->grades as $grade)
        {
            array_push($grades, Grade::create([
                'university_name' => $grade['university_name'],
                'grade' => $grade['grade'],
                'period' => $grade['period'],
                'resume_id' => $request->resume,
            ]));
        }
        return $grades;
    }

    public function deleteStage(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:stages,id']
        ]);
        $stage = Stage::find($request->id);
        if ($stage->resume->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $stage->delete();
        return new Response(['message' => "Successfully deleted"]);
    }

    public function deleteGrade(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:grades,id']
        ]);
        $grade = Grade::find($request->id);
        if ($grade->resume->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $grade->delete();
        return new Response(['message' => "Successfully deleted"]);
    }

    public function changeResume(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:resumes,id'],
            'email' => ['email'],
            'phone' => [/*'regex:/[7-8]{1}[0-9]{10}/',*/ 'size:11'],
            'category' => ['exists:categories,id'],
            'sphere' => ['exists:spheres,id'],
        ]);
        $resume = Resume::find($request->id);
        if ($resume->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $resume->body = $request->body ?: $resume->body;
        $resume->abilities = $request->abilities ?: $resume->abilities;
        $resume->name = $request->name ?: $resume->name;
        $resume->email = $request->email ?: $resume->email;
        $resume->sphere_id = $request->sphere ?: $resume->sphere_id;
        $resume->phone = $request->phone ?: $resume->phone;
        $resume->city = $request->city ?: $resume->city;
        $resume->category_id = $request->category ?: $resume->category_id;
        $resume->save();
        return Resume::with(['grades', 'stages', 'additionals'])->find($request->id);
    }

    public function deleteResume(Request $request)
    {
        $request->validate(['id' => ['required', 'exists:resumes,id']]);
        $resume = Resume::find($request->id);
        if ($resume->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $resume->delete();
        return new Response(['message' => "Successfully deleted"]);
    }

    public function getResume(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:resumes,id'],
        ]);
        $resume = Resume::with('additionals')->with('grades')->with('user')->with('stages')->find($request->id);
        if (Auth::user()->inn and !(Auth::user()->subscribe > Carbon::now()))
        {
            return collect($resume)->except(['email', 'phone', 'user.phone']);
        }
        return $resume;
    }

    public function getResumes()
    {
        $user = Auth::user();
        return $user->resumes()->with('additionals')->with('grades')->with('stages')->get();
    }

    public function feedback(Request $request)
    {
        $request->validate([
            'resume' => ['required', 'exists:resumes,id'],
            'vacancy' => ['required', 'exists:vacancies,id'],
        ]);
        if (Resume::find($request->resume)->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        return Feedback::create([
            'resume_id' => $request->resume,
            'vacancy_id' => $request->vacancy,
        ]);
    }

    public function getFeedback(Request $request)
    {
        $request->validate([
            'resume' => ['required', 'exists:resumes,id'],
        ]);
        $fs = Feedback::with('vacancy.company')->with('vacancy.category')->where('resume_id', $request->resume)->get();
        return $fs;
    }

    public function answers()
    {
        $user = Auth::user();
        $resumes = $user->resumes()->pluck('id');
        return Feedback::with('vacancy')->whereIn('resume_id', $resumes)->whereNotNull('answer')->get();
    }

    public function activateResume(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:resumes,id'],
        ]);
        $resume = Resume::find($request->id);
        if ($resume->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $resume->active = 1;
        $resume->save();
        return $resume;
    }

    public function deactivateResume(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:resumes,id'],
        ]);
        $resume = Resume::find($request->id);
        if ($resume->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $resume->active = 0;
        $resume->save();
        return $resume;
    }

    public function changeStage(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:stages,id']
        ]);
        $stage = Stage::find($request->id);
        if ($stage->resume->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $stage->company_name = $request->company_name ?: $stage->company_name;
        $stage->position = $request->position ?: $stage->position;
        $stage->description = $request->description ?: $stage->description;
        $stage->period = $request->period ?: $stage->period;
        $stage->save();
        return $stage;
    }

    public function changeGrade(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:grades,id']
        ]);
        $grade = Grade::find($request->id);
        if ($grade->resume->user_id != Auth::user()->id){
            return new Response(['Access denied'], 403);
        }
        $grade->university_name = $request->university_name ?: $grade->university_name;
        $grade->grade = $request->grade ?: $grade->grade;
        $grade->period = $request->period ?: $grade->period;
        $grade->save();
        return $grade;
    }

    public function acceptInvite(Request $request)
    {
        $request->validate([
            'vacancy' => ['required', 'exists:vacancies,id'],
            'resume' => ['required', 'exists:resumes,id'],
        ]);
        $feedback = Feedback::where('vacancy_id', $request->vacancy)->where('resume_id', $request->resume)->first();
        $feedback->accepted = 1;
        $feedback->save();
        return new Response(['message' => 'Successfully accepted']);
    }

    public function declineInvite(Request $request)
    {
        $request->validate([
            'vacancy' => ['required', 'exists:vacancies,id'],
            'resume' => ['required', 'exists:resumes,id'],
        ]);
        $feedback = Feedback::where('vacancy_id', $request->vacancy)->where('resume_id', $request->resume)->first();
        $feedback->accepted = 0;
        $feedback->save();
        return new Response(['message' => 'Successfully declined']);
    }

    //im tired)))

    public function recommendedResumes()
    {
        $company = Auth::user();
        if (!$company->vacancies()->exists())
        {
            return Resume::with('user', 'category')->limit(50)->paginate(5);
        }
        $categories = [];
        $result = [];
        foreach ($company->vacancies as $vacancy) {
            if (key_exists("$vacancy->category", $categories))$categories["$vacancy->category"] += 1;
            else $categories["$vacancy->category"] = 1;
        }
        $q = Resume::with('user', 'category')->with(['grades', 'stages', 'additionals']);
        for ($i = 0; $i < 3; $i++)
        {
            $max = array_search(max($categories), $categories);
            $q->orWhere('category_id', $max);
            $categories[$max] = 0;
        }
        return $q->paginate(5);
    }

    public function searchResumes(Request $request)
    {
        $request->validate([
            'category' => ['exists:categories,id'],
            'sphere' => ['exists:spheres,id'],
        ]);
        $resumes = Resume::with('user')->with(['grades', 'stages', 'additionals'])->with('category')/*->select('name','salary','body', 'company_id')*/;
        $q = [
            $request->body ? ['body', 'like', "%".$request->body."%"] : null,
            ['active', 1],
        ];
        $q = array_filter($q);
        $q = array_values($q);
        if ($request->city){
            $resumes->whereIn('city', $request->city);
        }
        if ($request->sphere){
            $resumes->whereIn('sphere_id', $request->sphere);
        }
        if ($request->category){
            $resumes->whereIn('category', $request->category);
        }
        if ($request->abilities)
        {
            $abilities = explode(',', $request->abilities);
            $resumes->where(function ($query) use ($abilities) {
                foreach ($abilities as $ability)
                {
                    $query->orWhere('abilities', 'like',  "%$ability%");
                }
            });
        }
        return $resumes->where($q)->paginate(5);
    }
}
