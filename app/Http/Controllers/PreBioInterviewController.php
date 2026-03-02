<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PreBioInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Exceptions\ErrorException;

class PreBioInterviewController extends Controller
{
    public function index()
    {
        $interviews = PreBioInterview::with('client')->latest()->get();
        return view('pre_bio_interviews.index', compact('interviews'));
    }

    public function create()
    {
        $clients = Client::orderBy('last_name')->get();
        return view('pre_bio_interviews.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'date' => 'required|date',
            'ua_date' => 'nullable|date',
            'ua_results' => 'nullable|string',
            'substance_use' => 'nullable|string',
            'vital_signs' => 'nullable|string',
            'drug_problems' => 'nullable|string',
            'normal_health' => 'nullable|string',
            'withdrawal_symptoms' => 'nullable|string',
            'high_blood_pressure' => 'nullable|string',
            'hospitalization_due_to_drugs' => 'nullable|string',
            'mental_health_treatment' => 'nullable|string',
            'substance_use_history' => 'nullable|string',
            'onset_of_use' => 'nullable|string',
            'last_use' => 'nullable|string',
            'mat_enrollment' => 'nullable|string',
            'overdose_history' => 'nullable|string',
            'withdrawal_risks' => 'nullable|string',
            'suicidal_ideation' => 'nullable|string',
            'periods_of_abstinence' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'primary_care_provider' => 'nullable|string',
            'recent_hospitalization' => 'nullable|string',
            'mental_health_history' => 'nullable|string',
            'psychotropic_medications' => 'nullable|string',
            'current_mental_health_provider' => 'nullable|string',
            'referral_needed' => 'nullable|string',
            'stage_of_change' => 'nullable|string',
            'motivation_for_treatment' => 'nullable|string',
            'coercion_for_treatment' => 'nullable|string',
            'coercion_for_treatment_details' => 'nullable|string',
            'expected_benefits' => 'nullable|string',
            'previous_treatment' => 'nullable|string',
            'sobriety_strategies' => 'nullable|string',
            'coping_skills' => 'nullable|string',
            'medication_compliance' => 'nullable|string',
            'barriers_to_sobriety' => 'nullable|string',
            'recommended_loc' => 'nullable|string',
            'living_situation' => 'nullable|string',
            'support_system' => 'nullable|string',
            'group_participation' => 'nullable|string',
            'group_participation_details' => 'nullable|string',
            'legal_issues' => 'nullable|string',
            'drug_tests' => 'nullable|string',
            'previous_treatment_details' => 'nullable|string',
            'longest_sober_period' => 'nullable|string',
            'needed_help' => 'nullable|string',
            'barriers_to_treatment' => 'nullable|string'
        ]);
        // dd($validated);
        PreBioInterview::create($validated);
        return redirect()->route('pre-bio-interviews.index')->with('success', 'Pre-Bio Interview created successfully.');
    }

    public function show(PreBioInterview $preBioInterview)
    {
        $clients = Client::orderBy('last_name')->get();
        return view('pre_bio_interviews.show', compact('preBioInterview', 'clients'));
    }

    public function edit(PreBioInterview $preBioInterview)
    {
        $clients = Client::orderBy('last_name')->get();
        return view('pre_bio_interviews.edit', compact('preBioInterview', 'clients'));
    }

    public function update(Request $request, PreBioInterview $preBioInterview)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'date' => 'required|date',
            'ua_date' => 'nullable|date',
            'ua_results' => 'nullable|string',
            'substance_use' => 'nullable|string',
            'vital_signs' => 'nullable|string',
            'drug_problems' => 'nullable|string',
            'normal_health' => 'nullable|string',
            'withdrawal_symptoms' => 'nullable|string',
            'high_blood_pressure' => 'nullable|string',
            'hospitalization_due_to_drugs' => 'nullable|string',
            'mental_health_treatment' => 'nullable|string',
            'substance_use_history' => 'nullable|string',
            'onset_of_use' => 'nullable|string',
            'last_use' => 'nullable|string',
            'mat_enrollment' => 'nullable|string',
            'overdose_history' => 'nullable|string',
            'withdrawal_risks' => 'nullable|string',
            'suicidal_ideation' => 'nullable|string',
            'periods_of_abstinence' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'primary_care_provider' => 'nullable|string',
            'recent_hospitalization' => 'nullable|string',
            'mental_health_history' => 'nullable|string',
            'psychotropic_medications' => 'nullable|string',
            'current_mental_health_provider' => 'nullable|string',
            'referral_needed' => 'nullable|string',
            'stage_of_change' => 'nullable|string',
            'motivation_for_treatment' => 'nullable|string',
            'coercion_for_treatment' => 'nullable|string',
            'coercion_for_treatment_details' => 'nullable|string',
            'expected_benefits' => 'nullable|string',
            'previous_treatment' => 'nullable|string',
            'sobriety_strategies' => 'nullable|string',
            'coping_skills' => 'nullable|string',
            'medication_compliance' => 'nullable|string',
            'barriers_to_sobriety' => 'nullable|string',
            'recommended_loc' => 'nullable|string',
            'living_situation' => 'nullable|string',
            'support_system' => 'nullable|string',
            'group_participation' => 'nullable|string',
            'group_participation_details' => 'nullable|string',
            'legal_issues' => 'nullable|string',
            'drug_tests' => 'nullable|string',
            'previous_treatment_details' => 'nullable|string',
            'longest_sober_period' => 'nullable|string',
            'needed_help' => 'nullable|string',
            'barriers_to_treatment' => 'nullable|string'
        ]);

        $preBioInterview->update($validated);
        return redirect()->route('pre-bio-interviews.index')->with('success', 'Pre-Bio Interview updated successfully.');
    }

    public function destroy(PreBioInterview $preBioInterview)
    {
        $preBioInterview->delete();
        return redirect()->route('pre-bio-interviews.index')->with('success', 'Pre-Bio Interview deleted.');
    }

    public function generate(Request $request)
    {
        // $validated = $request->validate([
        //     'gpt_input' => 'required|string' // Add content length limit
        // ]);

        // try {
        //     $response = OpenAI::chat()->create([
        //         'model' => 'gpt-4-turbo',
        //         'messages' => [
        //             [
        //                 'role' => 'system',
        //                 'content' => "You are a clinical data processor. Follow these rules:\n"
        //                         . "1. Use mm/dd/yyyy date format\n"
        //                         . "2. Mark missing data as 'Not Reported'\n"
        //                         . "3. Maintain clinical terminology\n"
        //                         . "4. Word Limit: 5000\n"
        //             ],
        //             [
        //                 'role' => 'user',
        //                 'content' => $validated['gpt_input']
        //             ]
        //         ],
        //         'temperature' => 0.2,
        //         'max_tokens' => 2000,
        //         'timeout' => 180, // Increase timeout to 60 seconds
        //     ]);

        //     if (!isset($response->choices[0]->message->content)) {
        //         throw new \RuntimeException('Invalid API response structure');
        //     }

        //     // Return raw line breaks for JSON, let frontend handle presentation
        //     $content = $response->choices[0]->message->content;

        //     return response()->json([
        //         'content' => $content,
        //         'usage' => $response->usage->total_tokens ?? null
        //     ]);

        // } catch (ErrorException $e) {
        //     Log::error('OpenAI API Error: '.$e->getMessage());
        //     return response()->json(['error' => 'AI service unavailable'], 503);
        // } catch (\Exception $e) {
        //     Log::error('Processing Error: '.$e->getMessage());
        //     return response()->json(['error' => 'Processing failed'], 500);
        // }
        set_time_limit(300);
        $validated = $request->validate([
            'gpt_input' => 'required|string' // Adjust the max length as needed
        ]);

        try {
            // Initialize the HTTP client with the DeepSeek API base URL
            $client = new \GuzzleHttp\Client(['base_uri' => 'https://api.deepseek.com/']);

            // Prepare the request payload
            $payload = [
                'model' => 'deepseek-chat', // Use 'deepseek-reasoner' for DeepSeek-R1
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a clinical data processor. Follow these rules:\n"
                                . "1. Use mm/dd/yyyy date format\n"
                                . "2. Mark missing data as 'Not Reported'\n"
                                . "3. Maintain clinical terminology\n"
                                . "4. Word Limit: 5000\n"
                                . "5. Extract key details from the provided DATA section\n"
                                . "6. Accurately complete the form using the given template\n"
                                . "7. Maintain a clinical tone and adhere strictly to the template format\n"
                                . "8. For ICD-10 Codes, include the code as well as the name.\n"
                                . "9. For ASAMs, write ratings beside each dimensions.\n"

                    ],
                    [
                        'role' => 'user',
                        'content' => $validated['gpt_input']
                    ]
                ],
                'temperature' => 0.2,
                'max_tokens' => 2000,
                'stream' => false // Set to true if you want streaming responses
            ];

            // Send the POST request to DeepSeek's chat completions endpoint
            $response = $client->post('/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . env('DEEPSEEK_API_KEY'), // Ensure your API key is set in the environment variables
                ],
                'json' => $payload,
                'timeout' => 180 // Increase timeout to 60 seconds
            ]);

            // Decode the JSON response
            $responseBody = json_decode($response->getBody(), true);

            // Check if the response contains the expected data
            if (!isset($responseBody['choices'][0]['message']['content'])) {
                throw new \RuntimeException('Invalid API response structure');
            }

            // Extract the content from the response
            $content = nl2br($responseBody['choices'][0]['message']['content']);

            return response()->json([
                'content' => $content,
                'usage' => $responseBody['usage']['total_tokens'] ?? null
            ]);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('DeepSeek API Request Error: '.$e->getMessage());
            return response()->json(['error' => 'AI service unavailable'], 503);
        } catch (\Exception $e) {
            Log::error('Processing Error: '.$e->getMessage());
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

}
