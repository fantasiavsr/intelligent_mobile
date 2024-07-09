<?php

namespace App\Http\Controllers;

use App\Models\topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class Android extends Controller
{

    protected $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . env('CHATGPT_API_KEY'),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function topic()
    {
        $user = Auth::user();
        /* $topic = \App\Models\Topic::all(); */

        /* topic first 2 */
        $topic = \App\Models\Topic::all()->take(2);

        /* dd($topic); */

        return view('pages.teacher.topic', [
            'title' => "Topic",
            'user' => $user,
            'topic' => $topic,
        ]);
    }

    public function task($id)
    {
        $user = Auth::user();
        $topic = \App\Models\Topic::find($id);
        $task = \App\Models\Task::where('topic', $id)->get();

        /* dd($task); */

        return view('pages.teacher.topic_task', [
            'title' => "Topic",
            'user' => $user,
            'topic' => $topic,
            'task' => $task,
        ]);
    }

    public function task_answer($id)
    {
        $user = Auth::user();
        $task = \App\Models\Task::find($id);
        $topic = \App\Models\Topic::find($task->topic);

        $test_file = \App\Models\test_file::where('taskid', $id)->first();
        /* dd($test_file); */

        $hasil = \App\Models\hasil2::where('testid', $test_file->id)->first();

        $report = $hasil->report;

        $task = \App\Models\Task::where('id', $test_file->taskid)->first();
        /* dd($task); */

        $taskdesc = $task->desc;

        $flight = \App\Models\hasil2::where('id', $hasil->id)->first();
        /* dd($flight); */

        /*  */

        if ($flight) {
            /* check if $flight->feedback not null */
            if (!isset($flight->feedback) || $flight->feedback == null || $flight->feedback == "") {
                /* dd($flight->feedback); */
            $content = <<<EOT
            topik:
            $taskdesc

            error:
            $report

            berikan solusi dari error yang diberikan dari topik tersebut dengan struktur
            topik:
            guide:
            (ex. 1. hati-hati dengan error ini, solusinya adalah ...)(pastikan jumlah guide sama dengan error di atas)(jagnan sebutkan error di atas di dalam guide)
            EOT;

                $response = $this->httpClient->post('chat/completions', [
                    'json' => [
                        'model' => 'gpt-3.5-turbo',
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                            ['role' => 'user', 'content' => $content],
                        ],
                    ],
                ]);

                $feedback = json_decode($response->getBody(), true)['choices'][0]['message']['content'];

                /* dd($feedback); */

                $flight->feedback = $feedback;
                $flight->save();
            }
        }

        return view('pages.teacher.task_answer', [
            'title' => "Task Answer",
            'user' => $user,
            'task' => $task,
            'topic' => $topic,
            'test_file' => $test_file,
            'hasil' => $hasil,
        ]);
    }

    public function calculateAndStoreErrorAverages()
    {
        // Mengambil data testid dengan status 'FAILED' dan report yang berisi error
        $errors = DB::table('student_validations')
            ->where('status', 'FAILED')
            ->select('testid', 'report')
            ->get();

        // Array untuk menyimpan jumlah kemunculan error untuk setiap testid
        $errorCounts = [];

        foreach ($errors as $error) {
            $testid = $error->testid;
            $errorDetails = explode(':', $error->report); // Misalnya, split berdasarkan ':' untuk memisahkan error

            // Memasukkan error ke dalam array $errorCounts
            if (!isset($errorCounts[$testid])) {
                $errorCounts[$testid] = [];
            }

            foreach ($errorDetails as $detail) {
                if (!isset($errorCounts[$testid][$detail])) {
                    $errorCounts[$testid][$detail] = 0;
                }
                $errorCounts[$testid][$detail]++;
            }
        }

        // Array untuk menyimpan error dengan frekuensi tertinggi untuk masing-masing testid
        $topErrors = [];

        // Mengambil error dengan frekuensi tertinggi untuk setiap testid
        foreach ($errorCounts as $testid => $errors) {
            // Mengurutkan error berdasarkan frekuensi tertinggi
            arsort($errors);

            // Mengambil error dengan frekuensi tertinggi (atau bisa ambil satu, tergantung kebutuhan)
            $topError = key($errors); // Mengambil error yang paling sering muncul

            // Menyimpan hasil ke dalam array $topErrors
            $topErrors[] = [
                'testid' => $testid,
                'error' => $topError,
            ];
        }

        // Simpan hasil error ke dalam tabel hasil
        foreach ($topErrors as $result) {
            DB::table('hasils')->insert([
                'testid' => $result['testid'],
                'error' => $result['error'],
            ]);
        }

        // Redirect atau berikan respons sukses
        return redirect()->route('hasil.index')->with('success', 'Rata-rata error telah dihitung dan disimpan.');
    }

    public function uploadJson(Request $request)
    {
        $request->validate([
            'json_file' => 'required|mimes:json'
        ]);

        $file = $request->file('json_file');
        $data = json_decode(file_get_contents($file), true);

        foreach ($data as $item) {
            \App\Models\Hasil2::create([
                'testid' => $item['testid'],
                'report' => $item['report']
            ]);
        }

        return redirect()->route('upload.form')->with('success', 'File uploaded and data saved successfully.');
    }

    /* student_learning */
    public function student_learning()
    {
        $user = Auth::user();


        return view('pages.student.student_learning', [
            'title' => "Student Learning",
            'user' => $user,

        ]);
    }

    /* student_learning_detail */
    public function student_learning_detail()
    {
        $user = Auth::user();
        $topic = \App\Models\Topic::all()->take(2);

        return view('pages.student.student_learning_detail', [
            'title' => "Student Learning Detail",
            'user' => $user,
            'topic' => $topic,
        ]);
    }

    public function student_learning_detail_task($id)
    {
        /* dd($id); */
        $user = Auth::user();
        /* $task = \App\Models\Task::find($id); */
        /* dd($task); */
        /* $topic = \App\Models\Topic::find($task->topic); */
        $topic = \App\Models\Topic::find($id);
        /* dd($topic); */

        $test_file = \App\Models\test_file::where('taskid', $id)->first();
        /* dd($test_file); */

        $hasil = \App\Models\hasil2::where('testid', $test_file->id)->first();

        $report = $hasil->report;

        $task = \App\Models\Task::where('topic', $topic->id)->get();

        /* dd($task); */

        $thistask = \App\Models\Task::where('topic', $topic->id)->first();

        /* dd($thistask->desc); */

        /* dd($task); */

        $taskdesc = $thistask->desc;

        $flight = \App\Models\hasil2::where('id', $hasil->id)->first();

        /* dd($task); */

        return view('pages.student.student_learning_detail_task', [
            'title' => "Student Learning Detail",
            'user' => $user,
            'topic' => $topic,
            'thistask' => $thistask,
            'hasil' => $hasil,
            'task' => $task,
        ]);
    }
}