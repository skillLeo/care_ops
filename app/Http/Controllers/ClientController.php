<?php

namespace App\Http\Controllers;

use App\Models\AuthLineOfService;
use App\Models\Authorization;
use App\Models\Apartment;
use App\Models\House;
use App\Models\Client;
use App\Models\Attendance2026;
use App\Models\ClientGroup;
use App\Models\Dropbox;
use App\Models\ClientHospitalization;
use App\Models\ClientLevelOfCares;
use App\Models\ClientClientGroup;
use App\Models\ClientPeerGroup;
use App\Models\ClientPeer;
use App\Models\ClientCounselor;
use App\Models\ClientApartment;
use App\Models\LevelOfCare;
use App\Models\PeerGroup;
use App\Models\Role;
use App\Models\User;
use App\Models\HospitalizationFacility;
use App\Services\TaskWorkflowService;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\AuditLogger;

class ClientController extends Controller
{
    private function formatLevelOfCareHistory($history): string
    {
        return $history->map(function ($entry) {
            $label = $entry->levelOfCare?->display_name ?? 'Unknown';
            $start = Carbon::parse($entry->start_date)->format('m/d/Y');
            $end = $entry->end_date ? Carbon::parse($entry->end_date)->format('m/d/Y') : 'Ongoing';

            return sprintf('%s (%s - %s)', $label, $start, $end);
        })->implode(', ');
    }


    private function syncHistoryRecords(Client $client, string $relation, string $valueField, array $values, array $startDates, array $endDates): void
    {
        $client->{$relation}()->delete();

        foreach ($startDates as $index => $startDate) {
            if (! $startDate) {
                continue;
            }

            if (empty($values[$index])) {
                continue;
            }

            $client->{$relation}()->create([
                $valueField => $values[$index],
                'start_date' => $startDate,
                'end_date' => $endDates[$index] ?? null,
            ]);
        }
    }

    private function createInitialHistoryRecords(Client $client, array $validated): void
    {
        $startDate = $validated['starting_date'] ?? now()->toDateString();

        if (! empty($validated['client_group_id'])) {
            $client->clientGroupHistory()->create([
                'client_group_id' => $validated['client_group_id'],
                'start_date' => $startDate,
            ]);
        }

        if (! empty($validated['peer_group_id'])) {
            $client->peerGroupHistory()->create([
                'peer_group_id' => $validated['peer_group_id'],
                'start_date' => $startDate,
            ]);
        }

        if (! empty($validated['counselor_id'])) {
            $client->counselorHistory()->create([
                'counselor_id' => $validated['counselor_id'],
                'start_date' => $startDate,
            ]);
        }

        if (! empty($validated['peer_id'])) {
            $client->peerHistory()->create([
                'peer_id' => $validated['peer_id'],
                'start_date' => $startDate,
            ]);
        }

        if (! empty($validated['apartment_id'])) {
            $client->apartmentHistory()->create([
                'apartment_id' => $validated['apartment_id'],
                'start_date' => $startDate,
            ]);
        }
    }
    private function parseEmails($envKey)
    {
        return $this->parseEmailList(env($envKey, ''));
    }

    private function parseEmailList(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[;,\n]+/', $value) ?: [])));
    }

    private function notificationRecipients(Client $client, string $fallbackEnvKey): array
    {
        $counselor = $client->counselor()->first();
        $recipients = [];

        if ($counselor && $counselor->email) {
            $recipients[] = $counselor->email;
        }

        if (empty($recipients)) {
            $recipients = $this->parseEmails($fallbackEnvKey);
        }

        return $recipients;
    }

    private function notify(array $to, string $subject, string $body, array $cc = [], array $bcc = []): void
    {
        if (empty($to)) {
            return;
        }

        Mail::raw($body, function ($message) use ($to, $subject, $cc, $bcc) {
            $message->to($to)
                ->subject($subject);

            if (! empty($cc)) {
                $message->cc($cc);
            }

            if (! empty($bcc)) {
                $message->bcc($bcc);
            }
        });
    }

    private function sendTemplateNotification(
        string $templateKey,
        Client $client,
        array $fallbackRecipients,
        string $fallbackSubject,
        string $fallbackBody,
        array $context = []
    ): void {
        $templateService = app(EmailTemplateService::class);
        $message = $templateService->buildMessage($templateKey, $client, $context);

        if ($message) {
            $recipients = $message['to'];
            if (empty($recipients)) {
                $recipients = $fallbackRecipients;
            }

            $this->notify($recipients, $message['subject'], $message['body'], $message['cc'], $message['bcc']);
            return;
        }

        $this->notify($fallbackRecipients, $fallbackSubject, $fallbackBody);
    }

    private function getLastAuthorizationInfo(Client $client): array
    {
        $lastLevel = $client->levelOfCareHistory()
            ->with('levelOfCare')
            ->get()
            ->sortByDesc('start_date')
            ->first();

        $lastLevelId = $lastLevel?->level_of_care;
        $lastLevelEndDate = $lastLevel?->end_date;
        $lastLevelStartDate = $lastLevel?->start_date;
        $lastLevelLabel = $lastLevel?->levelOfCare?->display_name ?? $lastLevel?->levelOfCare?->level_of_care;
        $lastServiceDate = null;

        if ($lastLevelId) {
            $lastAuth = $client->authorizations()
                ->where('level_of_care', $lastLevelId)
                ->orderByDesc('auth_ending_date')
                ->orderByDesc('auth_starting_date')
                ->first();

            if ($lastAuth) {
                $lastLine = $lastAuth->lineOfServices()
                    ->orderByDesc('ending_date')
                    ->orderByDesc('starting_date')
                    ->first();

                $lastServiceDate = $lastLine?->ending_date
                    ?? $lastAuth->auth_ending_date
                    ?? $lastAuth->auth_starting_date;
            }
        }

        return [
            'last_level_id' => $lastLevelId,
            'last_level_end_date' => $lastLevelEndDate,
            'last_level_start_date' => $lastLevelStartDate,
            'last_level_label' => $lastLevelLabel,
            'last_service_date' => $lastServiceDate,
        ];
    }

    private function notifyCounselorCaseloadChange(
        Client $client,
        ?User $previousCounselor,
        ?User $newCounselor
    ): void {
        if ($previousCounselor && $previousCounselor->id !== ($newCounselor?->id)) {
            $subject = 'Client removed from your caseload';
            $body = 'Client Name: ' . $client->last_name . ', ' . $client->first_name . "\n" .
                'MRN: ' . $client->mrn . "\n" .
                'Status: Removed from your caseload.';
            $this->sendTemplateNotification(
                'counselor_caseload_removed',
                $client,
                array_filter([$previousCounselor->email]),
                $subject,
                $body,
                [
                    'counselor_name' => $previousCounselor->name ?? '',
                    'counselor_email' => $previousCounselor->email ?? '',
                ]
            );
        }

        if ($newCounselor && $newCounselor->id !== ($previousCounselor?->id)) {
            $subject = 'Client added to your caseload';
            $body = 'Client Name: ' . $client->last_name . ', ' . $client->first_name . "\n" .
                'MRN: ' . $client->mrn . "\n" .
                'Status: Added to your caseload.';
            $this->sendTemplateNotification(
                'counselor_caseload_added',
                $client,
                array_filter([$newCounselor->email]),
                $subject,
                $body,
                [
                    'counselor_name' => $newCounselor->name ?? '',
                    'counselor_email' => $newCounselor->email ?? '',
                ]
            );
        }
    }


    private function notifyPeerCaseloadChange(Client $client, ?User $previousPeer, ?User $newPeer): void
    {
        if ($newPeer && $newPeer->id !== ($previousPeer?->id)) {
            $subject = 'Client added to your peer caseload';
            $body = 'Client Name: ' . $client->last_name . ', ' . $client->first_name . "
" .
                'MRN: ' . $client->mrn . "
" .
                'Status: Added to your peer caseload.';

            $this->sendTemplateNotification(
                'peer_caseload_added',
                $client,
                array_filter([$newPeer->email]),
                $subject,
                $body,
                [
                    'peer_name' => $newPeer->name ?? '',
                    'peer_email' => $newPeer->email ?? '',
                ]
            );
        }
    }

    private function notifyCounselorAssignmentChange(
        Client $client,
        ?User $counselor,
        bool $levelChanged,
        bool $groupChanged,
        bool $apartmentChanged
    ): void {
        if (! $counselor || (! $levelChanged && ! $groupChanged && ! $apartmentChanged)) {
            return;
        }

        $changes = [];
        if ($levelChanged) {
            $changes[] = 'level of care';
        }
        if ($groupChanged) {
            $changes[] = 'group';
        }
        if ($apartmentChanged) {
            $changes[] = 'house/apartment';
        }

        $changeSummary = implode(', ', $changes);
        $subject = 'Client assignment updated: [full_name]';
        $body = "Hello [counselor_name],

" .
            "[full_name] (MRN: [mrn]) has updated assignment data: {$changeSummary}.

" .
            "Thank you,
SNB";

        $this->sendTemplateNotification(
            'counselor_assignment_changed',
            $client,
            array_filter([$counselor->email]),
            str_replace('[full_name]', trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')), $subject),
            str_replace(['[counselor_name]', '[full_name]', '[mrn]'], [$counselor->name ?? '', trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')), $client->mrn ?? ''], $body),
            [
                'counselor_name' => $counselor->name ?? '',
                'counselor_email' => $counselor->email ?? '',
            ]
        );
    }
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'active');
        $allowedStatuses = ['active', 'inactive', 'all'];
        if (! in_array($statusFilter, $allowedStatuses, true)) {
            $statusFilter = 'active';
        }

        $clientsQuery = Client::with([
            'counselor' => function ($query) {
                $query->withCount('clients'); // Count clients assigned to each counselor
            },
            'peer',
            'levelOfCareHistory.levelOfCare',
            'hospitalizations.facility',
        ]);

        if ($statusFilter === 'active') {
            $clientsQuery->active();
        } elseif ($statusFilter === 'inactive') {
            $clientsQuery->where('status', 'inactive');
        }

        $clients = $clientsQuery->get();
        $apartments = Apartment::all();

        return view('clients.index', [
            'clients' => $clients,
            'apartments' => $apartments,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function downloadPdf(Client $client)
    {
        $profilePhotoPath = null;

        if ($client->profile_photo && Storage::exists($client->profile_photo)) {
            $profilePhotoPath = Storage::path($client->profile_photo);
        }

        $pdf = Pdf::loadView('clients.pdf', [
            'client' => $client->load(['apartment.house', 'counselor', 'peer', 'peerGroup', 'levelOfCareHistory.levelOfCare', 'dropbox']),
            'profilePhotoPath' => $profilePhotoPath,
            'generatedAt' => now(),
        ])->setPaper('letter');

        $fileName = sprintf('client_%s_%s.pdf', $client->last_name, $client->first_name);

        $actorName = request()->user()?->name ?? 'System';
        AuditLogger::log(
            'client_report_downloaded',
            sprintf('%s downloaded %s %s client report.', $actorName, $client->first_name, $client->last_name),
            $client
        );

        return $pdf->download($fileName);
    }

    public function create(Request $request)
    {
        $houses = House::all();
        $nextMrn = Client::max('mrn') + 1;

        $dropbox = null;
        $dropboxId = $request->query('dropbox_id', $request->old('dropbox_id'));
        if ($dropboxId) {
            $dropbox = Dropbox::with('client')->findOrFail($dropboxId);
            if ($dropbox->status !== 'approved' || $dropbox->client) {
                abort(404);
            }
        }

        $counselors = User::whereHas('roles', function ($query) {
            $query->where('name', 'counselor'); // Filter by role name
        })->withCount('clients') // Count assigned clients
            ->get();

        $peers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['peer', 'Peer_Support']);
        })->withCount('peerClients')->get();

        $clientGroups = ClientGroup::orderBy('name')->get();
        $peerGroups = PeerGroup::orderBy('name')->get();
        $levelOfCares = LevelOfCare::orderBy('level_of_care')->get();

        // Retrieve gender and level_of_care from the URL query parameters
        $gender = $request->query('gender', null);
        $levelOfCareParam = $request->query('level_of_care', null);
        $levelOfCareId = null;
        if ($levelOfCareParam !== null) {
            $levelOfCareId = LevelOfCare::where('level_of_care', $levelOfCareParam)
                ->orWhere('id', $levelOfCareParam)
                ->value('id');
        }

        // $bedStatus = $request->query('bed_status', null);
        return view('clients.create', [
            'houses' => $houses,
            'nextMrn' => $nextMrn,
            'gender' => $gender,
            'levelOfCareId' => $levelOfCareId,
            // 'bedStatus' => $bedStatus,
            'counselors' => $counselors,
            'peers' => $peers,
            'dropbox' => $dropbox,
            'clientGroups' => $clientGroups,
            'peerGroups' => $peerGroups,
            'levelOfCares' => $levelOfCares,
        ]);
    }

    public function store(Request $request, TaskWorkflowService $taskWorkflow)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string',
            'mrn' => 'required|integer|unique:clients,mrn',
            'carelon_id' => 'nullable|string',
            'primary_diagnosis_code' => 'nullable|string',
            'medicaid_id' => 'nullable|string',
            'counselor_id' => 'required|exists:users,id',
            'peer_id' => 'nullable|exists:users,id',
            'peer_group_id' => 'nullable|exists:peer_groups,id',
            'client_group_id' => 'required|exists:client_groups,id',
            'redetermination_date' => 'nullable|date',
            'redetermination_last_checked' => 'nullable|date',
            'eligibility' => ['required', 'string', Rule::in(['eligible', 'not_eligible'])],
            'redetermination_remarks' => 'nullable|string|max:255',
            'starting_date' => 'nullable|date',
            'discharge_date' => 'nullable|date',
            'guest' => 'required|boolean',
            'level_of_care' => 'required|exists:level_of_cares,id',
            'ssn' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|regex:/^\d{3}-\d{3}-\d{4}$/',
            'address' => 'nullable|string',
            'apartment_id' => 'nullable|exists:apartments,id',
            'notes' => 'nullable|string',
            'evs_data' => 'nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'captured_photo' => 'nullable', // Handle base64 webcam image
            'dropbox_id' => 'nullable|exists:dropboxes,id|unique:clients,dropbox_id',
        ]);

        $selectedGroup = ClientGroup::find($validated['client_group_id']);
        if ($selectedGroup && (int) $selectedGroup->level_of_care_id !== (int) $validated['level_of_care']) {
            return back()->withErrors(['client_group_id' => 'Selected client group does not belong to selected level of care.'])->withInput();
        }

        $selectedDropbox = null;
        if (! empty($validated['dropbox_id'])) {
            $selectedDropbox = Dropbox::with('client')->findOrFail($validated['dropbox_id']);
            if ($selectedDropbox->status !== 'approved' || $selectedDropbox->client) {
                return back()->withErrors(['dropbox_id' => 'The selected Dropbox entry is not available for linking.'])->withInput();
            }
        }

        if (!empty($validated['phone'])) {
            $digits = preg_replace('/\D/', '', $validated['phone']);
            if (strlen($digits) === 10) {
                $validated['phone'] = substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
            }
        }

        $validated['redetermination_last_checked'] = $validated['starting_date'] ?? now()->toDateString();
        $client = Client::create($validated);
        $this->createInitialHistoryRecords($client, $validated);
        $selectedLevel = LevelOfCare::findOrFail($validated['level_of_care']);

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            // dd($file->getClientOriginalName(), $file->getMimeType(), $file->isValid());
            $filename = 'profile_'.time().'.'.$file->getClientOriginalExtension();

            // Store file in `storage/app/private/clients_photo/`
            $path = $file->storeAs('clients_photo', $filename);

            // Save the file path in the database
            $client->update(['profile_photo' => $path]);
        }

        // Handle Webcam Captured Image
        if ($request->filled('captured_photo')) {
            $imageData = $request->input('captured_photo');

            // Remove the Base64 prefix (e.g., "data:image/png;base64,")
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageData = base64_decode($imageData);

            // Check if decoding was successful
            if ($imageData === false) {
                dd('Error: Base64 decoding failed');
            }

            // Generate a unique filename
            $filename = 'profile_'.time().'.jpg';
            $filePath = storage_path("app/private/clients_photo/$filename");

            // Convert base64 to an image using GD
            $image = imagecreatefromstring($imageData);
            if ($image === false) {
                dd('Error: Image creation failed');
            }

            // Save the image as a proper JPEG
            imagejpeg($image, $filePath, 90); // 90 is the quality (adjust as needed)
            imagedestroy($image); // Free memory

            // Ensure the file was successfully saved
            if (! file_exists($filePath)) {
                dd('Error: Image saving failed');
            }

            // Save file path in the database
            $client->update(['profile_photo' => "clients_photo/$filename"]);
        }
        ClientLevelOfCares::create([
            'client_id' => $client->id,
            'level_of_care' => $validated['level_of_care'],
            'start_date' => $request->starting_date ?? now(),
        ]);

        if ($selectedDropbox) {
            $selectedDropbox->update([
                'status' => 'created',
                'status_updated_at' => now(),
            ]);
        }

        if ($selectedDropbox) {
            $linkedTasks = $taskWorkflow->assignDropboxTasksToClient($selectedDropbox, $client);
            if ($linkedTasks === 0) {
                $taskWorkflow->triggerFirstTaskForCategory('Intake', $client);
            }
        }

        $counselor = User::find($request->counselor_id);
        $peer = User::find($request->peer_id);

        Authorization::create([
            'client_id' => $client->id,
            'auth_starting_date' => $request->starting_date,
            'level_of_care' => $selectedLevel->id,
        ]);

        $emails = $this->parseEmails('INTAKE_EMAILS');
        $subject = 'A Client has been added.';
        $body = 'Client Name: ' . $client->last_name . ', ' . $client->first_name . "\n" .
            'MRN: ' . $client->mrn . "\n" .
            'DOB: ' . (Carbon::parse($client->date_of_birth)->format('m/d/Y')) . "\n" .
            'Medicaid ID: ' . ($client->medicaid_id ?? '') . "\n" .
            'Carelon ID: ' . ($client->carelon_id ?? '') . "\n" .
            'Starting Date: ' . ($client->starting_date ? Carbon::parse($client->starting_date)->format('m/d/Y') : '') . "\n" .
            'LOC: ' . $selectedLevel->display_name . "\n" .
            'Counselor: ' . ($counselor ? $counselor->name : 'Not Available') . "\n" .
            'Peer: ' . ($peer ? $peer->name : 'Not Available') . "\n\n" .
            'Please Update Attendances (if necessary), create Icanotes, make sure all the notes are done up until today, Complete the assessments, Complete consents. If any of the details are wrong or missing, please update.';
        $this->sendTemplateNotification(
            'intake',
            $client,
            $emails,
            $subject,
            $body,
            [
                'starting_date' => $request->starting_date ?? $client->starting_date,
                'current_level_of_care' => $selectedLevel->display_name,
            ]
        );
        $this->notifyCounselorCaseloadChange($client, null, $counselor);

        return redirect()->route('clients.index')->with('success', 'Client added successfully.');
    }

    public function show($id)
    {
        $client = Client::with([
            'counselor' => function ($query) {
                $query->withCount('clients'); // Count clients assigned to each counselor
            },
            'peer',
            'peerGroup',
            'counselorHistory.assignment',
            'peerHistory.assignment',
            'peerGroupHistory.assignment',
            'clientGroupHistory.assignment',
            'apartmentHistory.assignment.house',
            'hospitalizations.facility',
            'levelOfCareHistory.levelOfCare',
        ])->findOrFail($id);
        $client->load('dropbox');
        $houses = House::all();

        return view('clients.show', ['client' => $client, 'houses' => $houses]);
    }

    public function edit($id)
    {
        $client = Client::with(['hospitalizations.facility', 'levelOfCareHistory.levelOfCare', 'counselorHistory.assignment', 'peerHistory.assignment', 'peerGroupHistory.assignment', 'clientGroupHistory.assignment', 'apartmentHistory.assignment'])->findOrFail($id);
        $client->load('dropbox');
        $houses = House::all();
        $levelOfCares = LevelOfCare::orderBy('level_of_care')->get();
        $hospitalizationFacilities = HospitalizationFacility::orderBy('name')->get();

        $counselors = User::whereHas('roles', function ($query) {
            $query->where('name', 'counselor');
        })->withCount('clients')->get();

        $peers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['peer', 'Peer_Support']);
        })->withCount('peerClients')->get();

        $availableDropboxes = Dropbox::whereIn('status', ['approved', 'created'])
            ->where(function ($query) use ($client) {
                $query->whereDoesntHave('client');

                if ($client->dropbox_id) {
                    $query->orWhere('id', $client->dropbox_id);
                }
            })
            ->orderByDesc('created_at')
            ->get();

        $clientGroups = ClientGroup::orderBy('name')->get();
        $peerGroups = PeerGroup::orderBy('name')->get();
        $today = now()->toDateString();
        $displayCounselor = $client->getDisplayCounselor($today);
        $displayPeer = $client->getDisplayPeer($today);
        $displayPeerGroup = $client->getDisplayPeerGroup($today);

        return view('clients.edit', compact(
            'client',
            'houses',
            'counselors',
            'peers',
            'availableDropboxes',
            'clientGroups',
            'peerGroups',
            'levelOfCares',
            'hospitalizationFacilities',
            'displayCounselor',
            'displayPeer',
            'displayPeerGroup'
        ));
    }

    public function update(Request $request, Client $client, TaskWorkflowService $taskWorkflow)
    {
        $originalMedicaidId = $client->medicaid_id;
        $originalProfilePhoto = $client->profile_photo;
        $originalLevelHistory = $client->levelOfCareHistory()->with('levelOfCare')->orderBy('start_date')->get();
        $previousDropboxId = $client->dropbox_id;
        $today = now()->toDateString();
        $previousCounselor = $client->getDisplayCounselor($today);
        $previousPeer = $client->getDisplayPeer($today);
        $previousGroupId = optional($client->getDisplayClientGroup($today))->id;
        $previousApartmentId = optional($client->getDisplayApartment($today))->id;
        $previousLocId = optional($client->levelOfCareHistory()->whereNull('end_date')->latest('start_date')->first())->level_of_care;

        $hospitalizationRules = [];
        if ($request->user()->can('hospitalization.edit')) {
            $hospitalizationRules = [
                'hospitalization_type' => 'nullable|array',
                'hospitalization_type.*' => ['required', Rule::in(['detox', 'hospitalization'])],
                'hospitalization_facility_id' => 'nullable|array',
                'hospitalization_facility_id.*' => 'nullable|exists:hospitalization_facilities,id',
                'hospitalization_start_date' => 'nullable|array',
                'hospitalization_start_date.*' => 'required|date',
                'hospitalization_end_date' => 'nullable|array',
                'hospitalization_end_date.*' => 'nullable|date',
                'hospitalization_mode_of_transport' => 'nullable|array',
                'hospitalization_mode_of_transport.*' => 'nullable|string',
                'hospitalization_remarks' => 'nullable|array',
                'hospitalization_remarks.*' => 'nullable|string',
            ];
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string',
            'mrn' => 'required|integer|unique:clients,mrn,'.$client->id,
            'carelon_id' => 'nullable|string',
            'medicaid_id' => 'nullable|string',
            'starting_date' => 'nullable|date',
            'discharge_date' => 'nullable|date',
            'ssn' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|regex:/^\d{3}-\d{3}-\d{4}$/',
            'address' => 'nullable|string',
            'apartment_id' => 'nullable|exists:apartments,id',
            'notes' => 'nullable|string',
            'evs_data' => 'nullable|string',
            'status' => 'required|string|in:active,inactive',
            'guest' => 'required|boolean',
            'level_of_care' => 'nullable|array',
            'level_of_care.*' => 'required|exists:level_of_cares,id',
            'start_date' => 'nullable|array',
            'start_date.*' => 'required|date',
            'end_date' => 'nullable|array',
            'end_date.*' => 'nullable|date',
            'client_group_history' => 'nullable|array',
            'client_group_history.*' => 'nullable|exists:client_groups,id',
            'client_group_start_date' => 'nullable|array',
            'client_group_start_date.*' => 'required_with:client_group_history|date',
            'client_group_end_date' => 'nullable|array',
            'client_group_end_date.*' => 'nullable|date',
            'peer_group_history' => 'nullable|array',
            'peer_group_history.*' => 'nullable|exists:peer_groups,id',
            'peer_group_start_date' => 'nullable|array',
            'peer_group_start_date.*' => 'required_with:peer_group_history|date',
            'peer_group_end_date' => 'nullable|array',
            'peer_group_end_date.*' => 'nullable|date',
            'counselor_history' => 'nullable|array',
            'counselor_history.*' => 'nullable|exists:users,id',
            'counselor_start_date' => 'nullable|array',
            'counselor_start_date.*' => 'required_with:counselor_history|date',
            'counselor_end_date' => 'nullable|array',
            'counselor_end_date.*' => 'nullable|date',
            'peer_history' => 'nullable|array',
            'peer_history.*' => 'nullable|exists:users,id',
            'peer_start_date' => 'nullable|array',
            'peer_start_date.*' => 'required_with:peer_history|date',
            'peer_end_date' => 'nullable|array',
            'peer_end_date.*' => 'nullable|date',
            'apartment_history' => 'nullable|array',
            'apartment_history.*' => 'nullable|exists:apartments,id',
            'apartment_start_date' => 'nullable|array',
            'apartment_start_date.*' => 'required_with:apartment_history|date',
            'apartment_end_date' => 'nullable|array',
            'apartment_end_date.*' => 'nullable|date',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'captured_photo' => 'nullable',
            'dropbox_id' => 'nullable|exists:dropboxes,id|unique:clients,dropbox_id,' . $client->id,
            'counselor_id' => 'nullable|exists:users,id',
            'peer_id' => 'nullable|exists:users,id',
            'peer_group_id' => 'nullable|exists:peer_groups,id',
            'apartment_id' => 'nullable|exists:apartments,id',
            'redetermination_date' => 'nullable|date',
            'redetermination_last_checked' => 'required|date',
            'eligibility' => ['required', 'string', Rule::in(['eligible', 'not_eligible'])],
            'redetermination_remarks' => 'nullable|string|max:255',
            'reactivation_date' => 'nullable|date',
        ] + $hospitalizationRules);

        $newDropboxId = $validated['dropbox_id'] ?? null;

        $selectedDropbox = null;
        if ($newDropboxId) {
            $selectedDropbox = Dropbox::with('client')->findOrFail($newDropboxId);
            if ($selectedDropbox->id !== $client->dropbox_id && ($selectedDropbox->status !== 'approved' || $selectedDropbox->client)) {
                return back()->withErrors(['dropbox_id' => 'The selected Dropbox entry is not available for linking.'])->withInput();
            }
        }

        if (!empty($validated['phone'])) {
            $digits = preg_replace('/\D/', '', $validated['phone']);
            if (strlen($digits) === 10) {
                $validated['phone'] = substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
            }
        }
        $validated['apartment_id'] = $request->input('apartment_id', null);
        $canEditHospitalizations = $request->user()->can('hospitalization.edit');

        DB::transaction(function () use ($client, $validated, $canEditHospitalizations) {
            // Update client details
            $client->update($validated);

            // Sync Level of Care
            if (! empty($validated['level_of_care'])) {
                // Delete old records (if you want to completely replace them)
                $client->levelOfCareHistory()->delete();

                // Insert new level of care records
                foreach ($validated['level_of_care'] as $index => $level) {
                    ClientLevelOfCares::create([
                        'client_id' => $client->id,
                        'level_of_care' => $level,
                        'start_date' => $validated['start_date'][$index],
                        'end_date' => $validated['end_date'][$index] ?? null,
                    ]);
                }
            }

            if (array_key_exists('client_group_history', $validated)) {
                $this->syncHistoryRecords($client, 'clientGroupHistory', 'client_group_id', $validated['client_group_history'] ?? [], $validated['client_group_start_date'] ?? [], $validated['client_group_end_date'] ?? []);
                $this->syncHistoryRecords($client, 'peerGroupHistory', 'peer_group_id', $validated['peer_group_history'] ?? [], $validated['peer_group_start_date'] ?? [], $validated['peer_group_end_date'] ?? []);
                $this->syncHistoryRecords($client, 'counselorHistory', 'counselor_id', $validated['counselor_history'] ?? [], $validated['counselor_start_date'] ?? [], $validated['counselor_end_date'] ?? []);
                $this->syncHistoryRecords($client, 'peerHistory', 'peer_id', $validated['peer_history'] ?? [], $validated['peer_start_date'] ?? [], $validated['peer_end_date'] ?? []);
                $this->syncHistoryRecords($client, 'apartmentHistory', 'apartment_id', $validated['apartment_history'] ?? [], $validated['apartment_start_date'] ?? [], $validated['apartment_end_date'] ?? []);
            }

            if ($canEditHospitalizations && ! empty($validated['hospitalization_type'])) {
                $client->hospitalizations()->delete();

                foreach ($validated['hospitalization_type'] as $index => $type) {
                    ClientHospitalization::create([
                        'client_id' => $client->id,
                        'type' => $type,
                        'hospitalization_facility_id' => $validated['hospitalization_facility_id'][$index] ?? null,
                        'start_date' => $validated['hospitalization_start_date'][$index],
                        'end_date' => $validated['hospitalization_end_date'][$index] ?? null,
                        'mode_of_transport' => $validated['hospitalization_mode_of_transport'][$index] ?? null,
                        'remarks' => $validated['hospitalization_remarks'][$index] ?? null,
                    ]);
                }
            }
        });

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            // dd($file->getClientOriginalName(), $file->getMimeType(), $file->isValid());
            $filename = 'profile_'.time().'.'.$file->getClientOriginalExtension();

            // Store file in `storage/app/private/clients_photo/`
            $path = $file->storeAs('clients_photo', $filename);

            // Save the file path in the database
            $client->update(['profile_photo' => $path]);
        }

        // Handle Webcam Captured Image
        if ($request->filled('captured_photo')) {
            $imageData = $request->input('captured_photo');

            // Remove the Base64 prefix (e.g., "data:image/png;base64,")
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageData = base64_decode($imageData);

            // Check if decoding was successful
            if ($imageData === false) {
                dd('Error: Base64 decoding failed');
            }

            // Generate a unique filename
            $filename = 'profile_'.time().'.jpg';
            $filePath = storage_path("app/private/clients_photo/$filename");

            // Convert base64 to an image using GD
            $image = imagecreatefromstring($imageData);
            if ($image === false) {
                dd('Error: Image creation failed');
            }

            // Save the image as a proper JPEG
            imagejpeg($image, $filePath, 90); // 90 is the quality (adjust as needed)
            imagedestroy($image); // Free memory

            // Ensure the file was successfully saved
            if (! file_exists($filePath)) {
                dd('Error: Image saving failed');
            }

            // Save file path in the database
            $client->update(['profile_photo' => "clients_photo/$filename"]);
        }

        if ($previousDropboxId && $previousDropboxId !== $newDropboxId) {
            $previousDropbox = Dropbox::find($previousDropboxId);
            if ($previousDropbox && $previousDropbox->status === 'created') {
                $previousDropbox->update([
                    'status' => 'approved',
                    'status_updated_at' => now(),
                ]);
            }
        }

        if ($selectedDropbox && $selectedDropbox->status !== 'created') {
            $selectedDropbox->update([
                'status' => 'created',
                'status_updated_at' => now(),
            ]);
        }

        if ($newDropboxId && $newDropboxId !== $previousDropboxId) {
            $taskWorkflow->triggerFirstTaskForCategory('Intake', $client);
        }

        $client->refresh();
        $actorName = $request->user()?->name ?? 'System';

        if ($originalMedicaidId !== $client->medicaid_id) {
            AuditLogger::log(
                'medicaid_id_updated',
                sprintf(
                    "%s changed client %s %s's medicaid ID from %s to %s.",
                    $actorName,
                    $client->first_name,
                    $client->last_name,
                    $originalMedicaidId ?? 'N/A',
                    $client->medicaid_id ?? 'N/A'
                ),
                $client,
                [
                    'from' => $originalMedicaidId,
                    'to' => $client->medicaid_id,
                ]
            );
        }

        if (! empty($validated['level_of_care'])) {
            $beforeHistory = $this->formatLevelOfCareHistory($originalLevelHistory);
            $afterHistory = $this->formatLevelOfCareHistory(
                $client->levelOfCareHistory()->with('levelOfCare')->orderBy('start_date')->get()
            );

            if ($beforeHistory !== $afterHistory) {
                AuditLogger::log(
                    'level_of_care_updated',
                    sprintf(
                        '%s changed client %s %s level of care from %s to %s.',
                        $actorName,
                        $client->first_name,
                        $client->last_name,
                        $beforeHistory ?: 'N/A',
                        $afterHistory ?: 'N/A'
                    ),
                    $client,
                    [
                        'from' => $beforeHistory,
                        'to' => $afterHistory,
                    ]
                );
            }
        }

        if ($request->hasFile('profile_photo') || $request->filled('captured_photo')) {
            $action = $originalProfilePhoto ? 'reuploaded' : 'uploaded';

            AuditLogger::log(
                'client_photo_updated',
                sprintf(
                    '%s %s client %s %s photo.',
                    $actorName,
                    $action,
                    $client->first_name,
                    $client->last_name
                ),
                $client
            );
        }

        $client->refresh();
        $this->notifyCounselorCaseloadChange($client, $previousCounselor, $client->counselor);
        $this->notifyPeerCaseloadChange($client, $previousPeer, $client->peer);

        $newCurrentGroupId = optional($client->getClientGroupOnDate(now()->toDateString()))->id;
        $newCurrentApartmentId = optional($client->apartment)->id;
        $newCurrentLocId = optional($client->levelOfCareHistory()->whereNull('end_date')->latest('start_date')->first())->level_of_care;

        $this->notifyCounselorAssignmentChange(
            $client,
            $client->counselor,
            (string) ($previousCurrentLocId ?? '') !== (string) ($newCurrentLocId ?? ''),
            (string) ($previousCurrentGroupId ?? '') !== (string) ($newCurrentGroupId ?? ''),
            (string) ($previousCurrentApartmentId ?? '') !== (string) ($newCurrentApartmentId ?? '')
        );

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }

    public function assignProgram(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $validatedData = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $client->programs()->attach($validatedData['program_id'], [
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
        ]);

        return redirect()->route('clients.show', $client->id)
            ->with('success', 'Program assigned successfully.');
    }

    public function removeProgram($clientId, $programId)
    {
        $client = Client::findOrFail($clientId);

        $client->programs()->detach($programId);

        return redirect()->route('clients.show', $client->id)
            ->with('success', 'Program removed successfully.');
    }

    public function photo($clientId)
    {
        $client = Client::findOrFail($clientId);
        $path = storage_path("app/private/{$client->profile_photo}");

        if (! $client->profile_photo) {
            abort(404);
        }

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => mime_content_type($path),
        ]);
    }

    public function showCalendar($id, Request $request)
    {
        $client = Client::findOrFail($id);
        $currentMonth = $request->input('month', now()->format('Y-m'));

        $levelOfCareHistory = $client->levelOfCareHistory()
            ->with('levelOfCare')
            ->orderBy('start_date')
            ->get();
        $hospitalizations = $client->hospitalizations()->with('facility')->orderBy('start_date')->get();

        $start = \Carbon\Carbon::parse($currentMonth)->startOfMonth()->startOfWeek();
        $end = \Carbon\Carbon::parse($currentMonth)->endOfMonth()->endOfWeek();

        $sessionTypes = ['group', 'peer_group', 'peer_individual'];
        $attendances = [];
        $notes = [];

        foreach ($sessionTypes as $type) {
            $attendances[$type] = $client->attendances()
                ->whereBetween('service_date', [$start, $end])
                ->where('session_type', $type)
                ->get()
                ->keyBy(fn ($a) => $a->service_date->format('Y-m-d'));

            $notes[$type] = $client->notes()
                ->whereBetween('service_date', [$start, $end])
                ->where('session_type', $type)
                ->get()
                ->keyBy(fn ($n) => $n->service_date->format('Y-m-d'));
        }

        $claimLines = $client->claims()
            ->with('lines')
            ->get()
            ->flatMap(fn ($claim) => $claim->lines)
            ->filter(function ($line) use ($start, $end) {
                $serviceDate = Carbon::parse($line->service_date);
                return $serviceDate->betweenIncluded($start, $end);
            })
            ->groupBy(fn ($line) => Carbon::parse($line->service_date)->format('Y-m-d'));

        return view('clients.calendar', [
            'client' => $client,
            'currentMonth' => $currentMonth,
            'levelOfCares' => $levelOfCareHistory,
            'attendances' => $attendances,
            'notes' => $notes,
            'claimLines' => $claimLines,
            'hospitalizations' => $hospitalizations,
        ]);
    }

    public function showCalendar2026(Client $client, Request $request)
    {
        $currentMonth = $request->input('month', now()->format('Y-m'));

        $levelOfCareHistory = $client->levelOfCareHistory()
            ->with('levelOfCare')
            ->orderBy('start_date')
            ->get();

        $start = Carbon::parse($currentMonth)->startOfMonth()->startOfWeek();
        $end = Carbon::parse($currentMonth)->endOfMonth()->endOfWeek();

        $attendanceRows = Attendance2026::with(['serviceCode.prices'])
            ->where('client_id', $client->id)
            ->whereBetween('service_date', [$start, $end])
            ->get();

        $attendance2026 = $this->buildAttendance2026Map($attendanceRows);
        $hospitalizations = $client->hospitalizations()->orderBy('start_date')->get();

        return view('clients.calendar_2026', [
            'client' => $client,
            'currentMonth' => $currentMonth,
            'levelOfCares' => $levelOfCareHistory,
            'attendance2026' => $attendance2026,
            'claimLines' => collect(),
            'processedClaims' => [],
            'hospitalizations' => $hospitalizations,
        ]);
    }

    private function buildAttendance2026Map($attendanceRows): array
    {
        $attendanceByDate = [];

        foreach ($attendanceRows as $row) {
            $date = $row->service_date?->format('Y-m-d');
            $code = $row->serviceCode?->service_code;
            $label = $row->serviceCode?->friendly_name;

            if (! $date || ! $code) {
                continue;
            }

            $units = $row->units ?? 1;

            if (! isset($attendanceByDate[$date][$code])) {
                $attendanceByDate[$date][$code] = [
                    'units' => 0,
                    'label' => $label,
                ];
            }

            $attendanceByDate[$date][$code]['units'] += $units;
        }

        return $attendanceByDate;
    }

    public function showDischargeForm(Client $client)
    {
        return view('clients.discharge', compact('client'));
    }

    public function submitDischarge(Request $request, Client $client)
    {
        $request->validate([
            'discharge_date' => 'required|date',
        ]);

        $loc = $client->levelOfCareHistory()->with('levelOfCare')->whereNull('end_date')->first();
        $final = [
            'level_id' => $loc?->level_of_care,
            'label' => $loc?->levelOfCare?->display_name,
            'end' => $request->discharge_date,
        ];

        $client->update([
            'discharge_date' => $request->discharge_date,
            'reactivation_date' => null,
        ]);

        $client->levelOfCareHistory()
            ->whereNull('end_date')
            ->update(['end_date' => $request->discharge_date]);

        $client->clientGroupHistory()
            ->whereNull('end_date')
            ->update(['end_date' => $request->discharge_date]);

        $client->peerGroupHistory()
            ->whereNull('end_date')
            ->update(['end_date' => $request->discharge_date]);

        $client->apartmentHistory()
            ->whereNull('end_date')
            ->update(['end_date' => $request->discharge_date]);

        $client->counselorHistory()
            ->whereNull('end_date')
            ->update(['end_date' => $request->discharge_date]);

        $client->peerHistory()
            ->whereNull('end_date')
            ->update(['end_date' => $request->discharge_date]);

        $client->authorizations()
            ->whereNull('auth_ending_date')
            ->update(['auth_ending_date' => $request->discharge_date]);

        $emails = $this->notificationRecipients($client, 'DISCHARGE_EMAILS');
        $auths = [];
        if ($final['level_id']) {
            $auths = $client->authorizations()
                ->where('level_of_care', $final['level_id'])
                ->whereDate('auth_ending_date', $final['end'])
                ->pluck('auth_number')
                ->filter()
                ->toArray();
        }
        $authList = $auths ? implode(', ', $auths) : 'None';

        $subject = 'A client has been discharged';
        $body = 'Client Name: ' . $client->last_name . ', ' . $client->first_name . "\n" .
            'MRN: ' . $client->mrn . "\n" .
            'DOB: ' . (Carbon::parse($client->date_of_birth)->format('m/d/Y')) . "\n" .
            'Carelon ID: ' . ($client->carelon_id ?? '') . "\n" .
            'Medicaid ID: ' . ($client->medicaid_id ?? '') . "\n" .
            'LOC: ' . ($final['label'] ?? '') . "\n" .
            'Discharge Date: ' . Carbon::parse($final['end'])->format('m/d/Y') . "\n\n" .
            'Please update necessary information and discharge the following ' . ($final['label'] ?? '') . ' authorization(s) in Carelon: ' . $authList . "\n" .
            'Please make sure all the notes are complete before making the icanotes account inactive.';
        $this->sendTemplateNotification(
            'discharge',
            $client,
            $emails,
            $subject,
            $body,
            [
                'discharge_date' => $final['end'],
                'last_level_of_care' => $final['label'] ?? '',
                'authorization_numbers' => $authList,
            ]
        );

        return redirect()->route('clients.index')->with('success', 'Client discharged and records updated.');
    }

    public function hospitalizeForm(Client $client)
    {
        $facilities = HospitalizationFacility::orderBy('name')->get();

        return view('clients.hospitalize', compact('client', 'facilities'));
    }

    public function submitHospitalize(Request $request, Client $client)
    {
        $validated = $request->validate([
            'hospitalization_date' => ['required', 'date'],
            'hospitalization_type' => ['required', Rule::in(['detox', 'hospitalization'])],
            'mode_of_transport' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'hospitalization_facility_id' => ['nullable', 'exists:hospitalization_facilities,id'],
        ]);

        $hospitalization = $client->hospitalizations()->create([
            'start_date' => $validated['hospitalization_date'],
            'type' => $validated['hospitalization_type'],
            'mode_of_transport' => $validated['mode_of_transport'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'hospitalization_facility_id' => $validated['hospitalization_facility_id'] ?? null,
        ]);

        $facilityName = $hospitalization->facility?->name ?? 'N/A';

        $subject = 'Client hospitalized/detoxed';
        $body = 'Client Name: ' . $client->last_name . ', ' . $client->first_name . "\n" .
            'MRN: ' . $client->mrn . "\n" .
            'Hospitalization Date: ' . Carbon::parse($validated['hospitalization_date'])->format('m/d/Y') . "\n" .
            'Type: ' . ucfirst($validated['hospitalization_type']) . "\n" .
            'Facility: ' . $facilityName . "\n\n" .
            'Client has been marked as hospitalized/detoxed.';

        $recipients = $this->notificationRecipients($client, 'HOSPITALIZATION_EMAILS');
        $this->sendTemplateNotification(
            'hospitalization',
            $client,
            $recipients,
            $subject,
            $body,
            [
                'hospitalization_date' => $validated['hospitalization_date'],
                'hospitalization_type' => ucfirst($validated['hospitalization_type']),
                'hospitalization_facility' => $facilityName,
                'counselor_name' => $client->counselor?->name ?? '',
                'counselor_email' => $client->counselor?->email ?? '',
            ]
        );

        return redirect()->route('clients.index')->with('success', 'Client hospitalization recorded.');
    }

    public function transitionForm(Client $client)
    {
        $activeLOC = $client->levelOfCareHistory()->with('levelOfCare')->whereNull('end_date')->first();

        $levelOfCares = LevelOfCare::orderBy('level_of_care')->get();

        $counselors = User::whereHas('roles', function ($query) {
            $query->where('name', 'counselor');
        })->withCount('clients')->get();

        $peers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['peer', 'Peer_Support']);
        })->withCount('peerClients')->get();

        $clientGroups = ClientGroup::orderBy('name')->get();
        $peerGroups = PeerGroup::orderBy('name')->get();

        $today = now()->toDateString();
        $currentCounselor = $client->getDisplayCounselor($today);
        $currentPeer = $client->getDisplayPeer($today);
        $currentPeerGroup = $client->getDisplayPeerGroup($today);
        $currentGroup = $client->getDisplayClientGroup($today);
        $currentApartment = $client->getDisplayApartment($today);

        $apartments = Apartment::with('house')->orderBy('apartment_number')->get();
        return view('clients.transition', compact('client', 'activeLOC', 'levelOfCares', 'counselors', 'peers', 'clientGroups', 'peerGroups', 'apartments', 'currentCounselor', 'currentPeer', 'currentPeerGroup', 'currentGroup', 'currentApartment'));
    }

    public function submitTransition(Request $request, Client $client)
    {
        $today = now()->toDateString();
        $previousCounselor = $client->getDisplayCounselor($today);
        $previousPeer = $client->getDisplayPeer($today);
        $previousGroupId = optional($client->getDisplayClientGroup($today))->id;
        $previousApartmentId = optional($client->getDisplayApartment($today))->id;
        $previousLocId = optional($client->levelOfCareHistory()->whereNull('end_date')->latest('start_date')->first())->level_of_care;

        $request->validate([
            'new_loc' => 'required|exists:level_of_cares,id',
            'new_start_date' => 'required|date',
            'counselor_id' => 'nullable|exists:users,id',
            'peer_id' => 'nullable|exists:users,id',
            'client_group_id' => 'nullable|exists:client_groups,id',
            'peer_group_id' => 'nullable|exists:peer_groups,id',
            'apartment_id' => 'nullable|exists:apartments,id',
        ]);

        if ($request->filled('end_loc_date')) {
            $endDate = Carbon::parse($request->input('end_loc_date'))->toDateString();
            $startDate = Carbon::parse($request->input('new_start_date'))->toDateString();

            if ($startDate <= $endDate) {
                return back()
                    ->withErrors(['new_start_date' => 'Start date must be after end date.'])
                    ->withInput();
            }
        }

        $transitionStart = $request->input('new_start_date');
        $pairs = [
            ['relation' => 'counselorHistory', 'field' => 'counselor_id', 'current' => optional($client->getDisplayCounselor($today))->id],
            ['relation' => 'peerHistory', 'field' => 'peer_id', 'current' => optional($client->getDisplayPeer($today))->id],
            ['relation' => 'peerGroupHistory', 'field' => 'peer_group_id', 'current' => optional($client->getDisplayPeerGroup($today))->id],
            ['relation' => 'clientGroupHistory', 'field' => 'client_group_id', 'current' => optional($client->getDisplayClientGroup($today))->id],
            ['relation' => 'apartmentHistory', 'field' => 'apartment_id', 'current' => optional($client->getDisplayApartment($today))->id],
        ];

        foreach ($pairs as $pair) {
            $incoming = $request->input($pair['field']);
            if ((string) ($incoming ?? '') === (string) ($pair['current'] ?? '')) {
                continue;
            }

            $client->{$pair['relation']}()->whereNull('end_date')->update(['end_date' => $request->input('end_loc_date') ?: null]);

            if (empty($incoming)) {
                continue;
            }

            $client->{$pair['relation']}()->create([$pair['field'] => $incoming, 'start_date' => $transitionStart]);
        }

        // Resolve requested LOC and only perform LOC/auth updates when it actually changes.
        $newLevel = LevelOfCare::findOrFail($request->new_loc);
        $newLoc = [
            'level_id' => $newLevel->id,
            'label' => $newLevel->display_name,
            'start' => $request->new_start_date,
        ];
        $locChanged = (string) ($previousLocId ?? '') !== (string) ($newLevel->id ?? '');

        // End current LOC and capture its details
        $previous = null;
        if ($locChanged && $request->has('end_loc_date') && $request->filled('end_loc_date') && $request->has('active_loc_id')) {
            $loc = $client->levelOfCareHistory()->find($request->active_loc_id);
            if ($loc) {
                $previous = [
                    'level_id' => $loc->level_of_care,
                    'label' => $loc->levelOfCare?->display_name,
                    'start' => $loc->start_date,
                    'end' => $request->end_loc_date,
                ];
                $loc->update(['end_date' => $request->end_loc_date]);
            }
        }

        if ($locChanged) {
            $client->levelOfCareHistory()->create([
                'level_of_care' => $newLoc['level_id'],
                'start_date' => $newLoc['start'],
            ]);

            // End current Auth automatically if one exists
            if ($request->has('end_loc_date') && $request->filled('end_loc_date')) {
                $activeAuth = $client->authorizations()->whereNull('auth_ending_date')->first();
                if ($activeAuth) {
                    $activeAuth->update(['auth_ending_date' => $request->end_loc_date]);
                }
            }

            // Add new Auth based on new level of care info
            $client->authorizations()->create([
                'level_of_care' => $newLevel->id,
                'auth_starting_date' => $newLoc['start'],
            ]);
        }

        $emails = $this->notificationRecipients($client, 'TRANSITION_EMAILS');
        if ($locChanged && $previous) {
            $auths = $client->authorizations()
                ->where('level_of_care', $previous['level_id'])
                ->whereDate('auth_ending_date', $previous['end'])
                ->pluck('auth_number')
                ->filter()
                ->toArray();
            $authList = $auths ? implode(', ', $auths) : 'None';

            $subject = 'A client has transitioned';
            $body = 'Client Name: ' . $client->last_name . ', ' . $client->first_name . "\n" .
                'MRN: ' . $client->mrn . "\n" .
                'DOB: ' . (Carbon::parse($client->date_of_birth)->format('m/d/Y')) . "\n" .
                'Carelon ID: ' . ($client->carelon_id ?? '') . "\n" .
                'Medicaid ID: ' . ($client->medicaid_id ?? '') . "\n" .
                'Previous LOC: ' . $previous['label'] . ' (' . Carbon::parse($previous['start'])->format('m/d/Y') . ' - ' . Carbon::parse($previous['end'])->format('m/d/Y') . ")\n" .
                'New LOC: ' . $newLoc['label'] . ' (' . Carbon::parse($newLoc['start'])->format('m/d/Y') . ' - Ongoing)' . "\n\n" .
                'Please update necessary information and discharge the following ' . $previous['label'] . ' authorization(s) in Carelon: ' . $authList;
            $this->sendTemplateNotification(
                'transition',
                $client,
                $emails,
                $subject,
                $body,
                [
                    'previous_level_of_care' => $previous['label'],
                    'previous_level_of_care_start' => $previous['start'],
                    'previous_level_of_care_end' => $previous['end'],
                    'new_level_of_care' => $newLoc['label'],
                    'new_level_of_care_start' => $newLoc['start'],
                    'authorization_numbers' => $authList,
                ]
            );
        }

        $actorName = $request->user()?->name ?? 'System';
        if ($locChanged) {
            if ($previous) {
                AuditLogger::log(
                    'level_of_care_transitioned',
                    sprintf(
                        '%s transitioned client %s %s from %s (%s - %s) to %s (%s - Ongoing).',
                        $actorName,
                        $client->first_name,
                        $client->last_name,
                        $previous['label'],
                        Carbon::parse($previous['start'])->format('m/d/Y'),
                        Carbon::parse($previous['end'])->format('m/d/Y'),
                        $newLoc['label'],
                        Carbon::parse($newLoc['start'])->format('m/d/Y')
                    ),
                    $client
                );
            } else {
                AuditLogger::log(
                    'level_of_care_transitioned',
                    sprintf(
                        '%s transitioned client %s %s to %s (%s - Ongoing).',
                        $actorName,
                        $client->first_name,
                        $client->last_name,
                        $newLoc['label'],
                        Carbon::parse($newLoc['start'])->format('m/d/Y')
                    ),
                    $client
                );
            }
        }

        $client->refresh();
        $newCounselor = $client->getDisplayCounselor($today);
        $newPeer = $client->getDisplayPeer($today);
        $newGroupId = optional($client->getDisplayClientGroup($today))->id;
        $newApartmentId = optional($client->getDisplayApartment($today))->id;

        $this->notifyCounselorCaseloadChange($client, $previousCounselor, $newCounselor);
        $this->notifyPeerCaseloadChange($client, $previousPeer, $newPeer);

        $this->notifyCounselorAssignmentChange(
            $client,
            $newCounselor,
            $locChanged,
            (string) ($previousGroupId ?? '') !== (string) ($newGroupId ?? ''),
            (string) ($previousApartmentId ?? '') !== (string) ($newApartmentId ?? '')
        );

        return redirect()->route('clients.index')->with('success', 'Client transitioned successfully.');
    }

    public function reactivateForm(Client $client)
    {
        $levels = LevelOfCare::orderBy('level_of_care')->get();
        $authorizations = $client->authorizations()->with('levelOfCare')->orderByDesc('auth_starting_date')->get();
        $levelHistory = $client->levelOfCareHistory()->with('levelOfCare')->orderBy('start_date')->get();
        $hospitalizations = $client->hospitalizations()->with('facility')->orderByDesc('start_date')->get();
        $lastAuthInfo = $this->getLastAuthorizationInfo($client);
        $lastLevelId = $lastAuthInfo['last_level_id'];
        $lastServiceDate = $lastAuthInfo['last_service_date']
            ? Carbon::parse($lastAuthInfo['last_service_date'])->toDateString()
            : null;
        $lastLevelEndDate = $lastAuthInfo['last_level_end_date']
            ? Carbon::parse($lastAuthInfo['last_level_end_date'])->toDateString()
            : null;
        $lastLevelStartDate = $lastAuthInfo['last_level_start_date']
            ? Carbon::parse($lastAuthInfo['last_level_start_date'])->toDateString()
            : null;
        $lastLevelLabel = $lastAuthInfo['last_level_label'];

        $counselors = User::whereHas('roles', function ($query) {
            $query->where('name', 'counselor');
        })->withCount('clients')->get();

        $peers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['peer', 'Peer_Support']);
        })->withCount('peerClients')->get();

        $clientGroups = ClientGroup::orderBy('name')->get();
        $peerGroups = PeerGroup::orderBy('name')->get();
        $apartments = Apartment::with('house')->orderBy('apartment_number')->get();

        $today = now()->toDateString();
        $currentCounselor = $client->getDisplayCounselor($today);
        $currentPeer = $client->getDisplayPeer($today);
        $currentPeerGroup = $client->getDisplayPeerGroup($today);
        $currentGroup = $client->getDisplayClientGroup($today);
        $currentApartment = $client->getDisplayApartment($today);

        return view('clients.reactivate', compact(
            'client',
            'authorizations',
            'levels',
            'counselors',
            'peers',
            'clientGroups',
            'peerGroups',
            'apartments',
            'levelHistory',
            'hospitalizations',
            'lastLevelId',
            'lastServiceDate',
            'lastLevelEndDate',
            'lastLevelStartDate',
            'lastLevelLabel',
            'currentCounselor',
            'currentPeer',
            'currentPeerGroup',
            'currentGroup',
            'currentApartment'
        ));
    }

    public function submitReactivate(Request $request, Client $client)
    {
        $today = now()->toDateString();
        $previousCounselor = $client->getDisplayCounselor($today);
        $previousPeer = $client->getDisplayPeer($today);
        $previousGroupId = optional($client->getDisplayClientGroup($today))->id;
        $previousApartmentId = optional($client->getDisplayApartment($today))->id;
        $previousLocId = optional($client->levelOfCareHistory()->whereNull('end_date')->latest('start_date')->first())->level_of_care;

        $validated = $request->validate([
            'new_loc' => ['required', 'exists:level_of_cares,id'],
            'new_loc_start_date' => ['required', 'date'],
            'auth_option' => ['required', Rule::in(['new', 'existing'])],
            'counselor_id' => ['nullable', 'exists:users,id'],
            'peer_id' => ['nullable', 'exists:users,id'],
            'client_group_id' => ['nullable', 'exists:client_groups,id'],
            'peer_group_id' => ['nullable', 'exists:peer_groups,id'],
            'apartment_id' => ['nullable', 'exists:apartments,id'],
            'authorization_id' => [
                'nullable',
                Rule::requiredIf(fn () => $request->auth_option === 'existing'),
                Rule::exists('authorizations', 'id')->where('client_id', $client->id),
            ],
        ]);

        $activeLoc = $client->levelOfCareHistory()
            ->with('levelOfCare')
            ->whereNull('end_date')
            ->latest('start_date')
            ->first();
        $sameLoc = $activeLoc && (int) $activeLoc->level_of_care === (int) $validated['new_loc'];

        $client->update([
            'status' => 'active',
            'discharge_date' => null,
            'reactivation_date' => $validated['new_loc_start_date'],
        ]);

        $this->createInitialHistoryRecords($client, [
            'starting_date' => $validated['new_loc_start_date'],
            'counselor_id' => $validated['counselor_id'] ?? null,
            'peer_id' => $validated['peer_id'] ?? null,
            'peer_group_id' => $validated['peer_group_id'] ?? null,
            'client_group_id' => $validated['client_group_id'] ?? null,
            'apartment_id' => $validated['apartment_id'] ?? null,
        ]);

        $newLevel = LevelOfCare::findOrFail($validated['new_loc']);

        if ($sameLoc && $activeLoc) {
        }

        if (! $sameLoc) {
            if ($validated['auth_option'] === 'existing') {
                return back()->withInput()->withErrors([
                    'auth_option' => 'A new authorization is required when the level of care changes.',
                ]);
            }

            if ($activeLoc) {
                $activeLoc->update(['end_date' => $validated['new_loc_start_date']]);
            }

            $client->levelOfCareHistory()->create([
                'level_of_care' => $validated['new_loc'],
                'start_date' => $validated['new_loc_start_date'],
            ]);

            if ($activeLoc) {
                $client->authorizations()
                    ->where('level_of_care', $activeLoc->level_of_care)
                    ->whereNull('auth_ending_date')
                    ->update(['auth_ending_date' => $validated['new_loc_start_date']]);
            }

            $client->authorizations()->create([
                'level_of_care' => $newLevel->id,
                'auth_starting_date' => $validated['new_loc_start_date'],
            ]);
        } elseif ($validated['auth_option'] === 'new') {
            $client->authorizations()
                ->where('level_of_care', $validated['new_loc'])
                ->whereNull('auth_ending_date')
                ->update(['auth_ending_date' => $validated['new_loc_start_date']]);

            $client->authorizations()->create([
                'level_of_care' => $newLevel->id,
                'auth_starting_date' => $validated['new_loc_start_date'],
            ]);
        } else {
            $authorization = $client->authorizations()
                ->where('id', $validated['authorization_id'])
                ->where('level_of_care', $validated['new_loc'])
                ->first();

            if (! $authorization) {
                return back()->withInput()->withErrors([
                    'authorization_id' => 'Selected authorization not found for the chosen level of care. Please start a new authorization.',
                ]);
            }
        }

        $emails = $this->notificationRecipients($client, 'INTAKE_EMAILS');
        $subject = 'A client has been reactivated';
        $body = 'Client Name: ' . $client->last_name . ', ' . $client->first_name . "\n" .
            'MRN: ' . $client->mrn . "\n" .
            'DOB: ' . (Carbon::parse($client->date_of_birth)->format('m/d/Y')) . "\n" .
            'Carelon ID: ' . ($client->carelon_id ?? '') . "\n" .
            'Medicaid ID: ' . ($client->medicaid_id ?? '') . "\n" .
            'Reactivation Date: ' . Carbon::parse($validated['new_loc_start_date'])->format('m/d/Y') . "\n" .
            'LOC: ' . ($newLevel?->display_name ?? '') . "\n\n" .
            'Account has been reactivated.';
        $this->sendTemplateNotification(
            'reactivation',
            $client,
            $emails,
            $subject,
            $body,
            [
                'reactivation_date' => $validated['new_loc_start_date'],
                'current_level_of_care' => $newLevel?->display_name ?? '',
            ]
        );

        $client->refresh();
        $this->notifyCounselorCaseloadChange($client, $previousCounselor, $client->counselor);

        return redirect()->route('clients.index')->with('success', 'Client reactivated successfully.');
    }

    public function readmitForm(Client $client)
    {
        $levels = LevelOfCare::orderBy('level_of_care')->get();
        $authorizations = $client->authorizations()->with('levelOfCare')->orderByDesc('auth_starting_date')->get();
        $levelHistory = $client->levelOfCareHistory()->with('levelOfCare')->orderBy('start_date')->get();
        $hospitalizations = $client->hospitalizations()->with('facility')->orderByDesc('start_date')->get();
        $lastAuthInfo = $this->getLastAuthorizationInfo($client);
        $lastLevelId = $lastAuthInfo['last_level_id'];
        $lastServiceDate = $lastAuthInfo['last_service_date']
            ? Carbon::parse($lastAuthInfo['last_service_date'])->toDateString()
            : null;

        $counselors = User::whereHas('roles', function ($query) {
            $query->where('name', 'counselor');
        })->withCount('clients')->get();

        $peers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['peer', 'Peer_Support']);
        })->withCount('peerClients')->get();

        $clientGroups = ClientGroup::orderBy('name')->get();
        $peerGroups = PeerGroup::orderBy('name')->get();

        return view('clients.readmit', compact(
            'client',
            'authorizations',
            'levels',
            'counselors',
            'peers',
            'clientGroups',
            'peerGroups',
            'levelHistory',
            'hospitalizations',
            'lastLevelId',
            'lastServiceDate'
        ));
    }

    public function submitReadmit(Request $request, Client $client)
    {
        $today = now()->toDateString();
        $previousCounselor = $client->getDisplayCounselor($today);
        $previousPeer = $client->getDisplayPeer($today);
        $previousGroupId = optional($client->getDisplayClientGroup($today))->id;
        $previousApartmentId = optional($client->getDisplayApartment($today))->id;
        $previousLocId = optional($client->levelOfCareHistory()->whereNull('end_date')->latest('start_date')->first())->level_of_care;
        $lastAuthInfo = $this->getLastAuthorizationInfo($client);
        $lastLevelId= $lastAuthInfo['last_level_id'];
        $validated = $request->validate([
            'new_loc' => ['required', 'exists:level_of_cares,id'],
            'new_loc_start_date' => ['required', 'date'],
            'auth_option' => ['required', Rule::in(['new', 'existing'])],
            'counselor_id' => ['nullable', 'exists:users,id'],
            'peer_id' => ['nullable', 'exists:users,id'],
            'client_group_id' => ['nullable', 'exists:client_groups,id'],
            'peer_group_id' => ['nullable', 'exists:peer_groups,id'],
            'authorization_id' => [
                'nullable',
                Rule::requiredIf(fn () => $request->auth_option === 'existing'),
                Rule::exists('authorizations', 'id')->where('client_id', $client->id),
            ],
        ]);

        $client->update([
            'status' => 'active',
            'reactivation_date' => $validated['new_loc_start_date'],
        ]);

        $this->createInitialHistoryRecords($client, [
            'starting_date' => $validated['new_loc_start_date'],
            'counselor_id' => $validated['counselor_id'] ?? null,
            'peer_id' => $validated['peer_id'] ?? null,
            'peer_group_id' => $validated['peer_group_id'] ?? null,
        ]);

        $client->hospitalizations()
            ->whereNull('end_date')
            ->orderByDesc('start_date')
            ->limit(1)
            ->update(['end_date' => Carbon::parse($validated['new_loc_start_date'])->subDay()]);

        // add level of care if the new loc is not same as last level of care
        if ((int) $lastLevelId !== (int) $validated['new_loc']) {
            $client->levelOfCareHistory()->create([
                'level_of_care' => $validated['new_loc'],
                'start_date' => $validated['new_loc_start_date'],
            ]);
        } else {
            $activeLoc = $client->levelOfCareHistory()
                ->whereNull('end_date')
                ->latest('start_date')
                ->first();

            if ($activeLoc && (int) $activeLoc->level_of_care === (int) $validated['new_loc']) {
                }
        }
        // dd(((int) $lastLevelId !== (int) $validated['new_loc']));
        $newLevel = LevelOfCare::findOrFail($validated['new_loc']);

        if ($validated['auth_option'] === 'existing') {
            $authorization = $client->authorizations()
                ->where('id', $validated['authorization_id'])
                ->where('level_of_care', $validated['new_loc'])
                ->first();

            if (! $authorization) {
                return back()->withInput()->withErrors([
                    'authorization_id' => 'Selected authorization not found for the chosen level of care. Please start a new authorization.',
                ]);
            }

            $previousEnd = $authorization->auth_ending_date;

            $authorization->update([
                'auth_ending_date' => null,
                'auth_starting_date' => $authorization->auth_starting_date ?? $validated['new_loc_start_date'],
            ]);

            $hospitalization = $client->hospitalizations()
                ->orderByDesc('start_date')
                ->first();

            if ($hospitalization?->start_date && $hospitalization?->end_date) {
                $diagnosis = $authorization->lineOfServices()
                    ->latest('starting_date')
                    ->value('diagnosis_code') ?? 'N/A';

                $authorization->lineOfServices()->create([
                    'type' => 'pause',
                    'submission_date' => Carbon::parse($hospitalization->start_date)->toDateString(),
                    'units' => 0,
                    'starting_date' => Carbon::parse($hospitalization->start_date)->toDateString(),
                    'ending_date' => Carbon::parse($hospitalization->end_date)->toDateString(),
                    'status' => 'approved',
                    'diagnosis_code' => $diagnosis,
                    'remarks' => 'Hospitalization / Detox',
                ]);
            }

            if ($previousEnd) {
                $gapStart = Carbon::parse($previousEnd)->addDay();
                $gapEnd = Carbon::parse($validated['new_loc_start_date'])->subDay();

                if ($gapStart->lte($gapEnd)) {
                    $diagnosis = $authorization->lineOfServices()
                        ->latest('starting_date')
                        ->value('diagnosis_code') ?? 'N/A';

                    $authorization->lineOfServices()->create([
                        'type' => 'pause',
                        'submission_date' => $gapStart->toDateString(),
                        'units' => 0,
                        'starting_date' => $gapStart->toDateString(),
                        'ending_date' => $gapEnd->toDateString(),
                        'status' => 'approved',
                        'diagnosis_code' => $diagnosis,
                        'remarks' => 'Gap between authorization periods during readmission.',
                    ]);
                }
            }
        } else {
            $previousAuthEndDate = Carbon::parse($validated['new_loc_start_date'])->subDay()->toDateString();

            $client->authorizations()
                ->whereNull('auth_ending_date')
                ->update(['auth_ending_date' => $previousAuthEndDate]);

            $client->authorizations()->create([
                'level_of_care' => $newLevel->id,
                'auth_starting_date' => $validated['new_loc_start_date'],
            ]);
        }

        $subject = 'Client readmitted';
        $body = 'Client Name: ' . $client->last_name . ', ' . $client->first_name . "\n" .
            'MRN: ' . $client->mrn . "\n" .
            'Readmission Date: ' . Carbon::parse($validated['new_loc_start_date'])->format('m/d/Y') . "\n" .
            'LOC: ' . ($newLevel?->display_name ?? '') . "\n\n" .
            'Client has been readmitted.';
        $recipients = $this->notificationRecipients($client, 'READMISSION_EMAILS');
        $this->sendTemplateNotification(
            'readmission',
            $client,
            $recipients,
            $subject,
            $body,
            [
                'reactivation_date' => $validated['new_loc_start_date'],
                'current_level_of_care' => $newLevel?->display_name ?? '',
                'counselor_name' => $client->counselor?->name ?? '',
                'counselor_email' => $client->counselor?->email ?? '',
            ]
        );

        $client->refresh();
        $this->notifyCounselorCaseloadChange($client, $previousCounselor, $client->counselor);

        return redirect()->route('clients.index')->with('success', 'Client readmitted successfully.');
    }

    public function eligibilityTracker()
    {
        $today = now()->toDateString();

        $clients = Client::active()
            ->whereNull('discharge_date')
            ->with(['hospitalizations', 'authorizations'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->filter(function ($client) use ($today) {
                $hospitalization = $client->hospitalizations
                    ->filter(function ($record) use ($today) {
                        return $record->start_date <= $today
                            && (is_null($record->end_date) || $record->end_date >= $today);
                    })
                    ->first();

                return ! $hospitalization;
            })
            ->values();

        return view('authorizations.eligibility-tracker', compact('clients'));
    }
}
