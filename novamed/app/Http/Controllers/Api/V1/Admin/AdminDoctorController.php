<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreDoctorRequest;
use App\Http\Requests\Api\V1\Admin\UpdateDoctorRequest;
use App\Http\Resources\Api\V1\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminDoctorController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Doctor::class);

        $query = Doctor::query()->with(['user', 'procedures']);

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('specialization', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', function ($subQ) use ($searchTerm) {
                        $subQ->where('email', 'like', "%{$searchTerm}%");
                    });
            });
        }

        if ($request->has('specialization') && !empty($request->specialization)) {
            $query->where('specialization', $request->specialization);
        }

        $query->orderBy('id')->orderBy('first_name');

        $perPage = $request->input('per_page', 10);
        $doctors = $query->paginate($perPage);

        return DoctorResource::collection($doctors);
    }

    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $this->authorize('create', Doctor::class);
        $validated = $request->validated();

        try {
            $doctor = new Doctor();
            $doctor->first_name = $validated['first_name'];
            $doctor->last_name = $validated['last_name'];
            $doctor->specialization = $validated['specialization'];

            if (isset($validated['bio'])) $doctor->bio = $validated['bio'];

            if (isset($validated['user_id']) && !empty($validated['user_id'])) {
                $user = User::findOrFail($validated['user_id']);
                if ($user->role !== 'doctor') {
                    $user->role = 'doctor';
                    $user->save();
                }
                $doctor->user()->associate($user);
            } else if (isset($validated['email']) && isset($validated['password'])) {
                $user = new User();
                $user->name = $validated['first_name'] . ' ' . $validated['last_name'];
                $user->email = $validated['email'];
                $user->password = Hash::make($validated['password']);
                $user->role = 'doctor';
                $user->save();

                $doctor->user()->associate($user);
            }

            $doctor->save();

            if (isset($validated['procedure_ids']) && is_array($validated['procedure_ids'])) {
                $doctor->procedures()->sync($validated['procedure_ids']);
            }

            return (new DoctorResource($doctor->fresh()->load(['user', 'procedures'])))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Wystąpił błąd podczas dodawania lekarza.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Doctor $doctor): DoctorResource
    {
        $this->authorize('view', $doctor);
        return new DoctorResource($doctor->load(['user', 'procedures']));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorResource
    {
        $this->authorize('update', $doctor);
        $validated = $request->validated();

        $doctor->update(collect($validated)->except(['profile_picture', 'procedure_ids'])->toArray());

        if (isset($validated['procedure_ids'])) {
            $doctor->procedures()->sync($validated['procedure_ids']);
        } elseif ($request->has('procedure_ids') && array_key_exists('procedure_ids', $validated) && $validated['procedure_ids'] === null) {
            $doctor->procedures()->detach();
        }

        return new DoctorResource($doctor->fresh()->load(['user', 'procedures']));
    }

    public function destroy(Doctor $doctor): Response
    {
        $this->authorize('delete', $doctor);

        $user = $doctor->user;
        if ($user) {
            $user->role = 'patient';
            $user->save();
        }

        if ($doctor->profile_picture_path) {
            Storage::disk('public')->delete($doctor->profile_picture_path);
        }

        $doctor->delete();

        return response()->noContent();

    }

    public function updateAvatar(Request $request, Doctor $doctor): JsonResponse
    {
        $this->authorize('update', $doctor);

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            if ($doctor->profile_picture_path) {
                Storage::disk('public')->delete($doctor->profile_picture_path);
            }

            $path = $request->file('avatar')->store('avatars', 'public');

            $doctor->profile_picture_path = $path;
            $doctor->save();

            return response()->json(new DoctorResource($doctor));

        } catch (\Exception $e) {
            Log::error('Błąd podczas przesyłania awatara: ' . $e->getMessage());
            return response()->json([
                'message' => 'Wystąpił nieoczekiwany błąd podczas przesyłania pliku.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Usuń avatar lekarza
     */
    public function deleteAvatar(Doctor $doctor): Response|JsonResponse
    {
        $this->authorize('update', $doctor);
        try {
            if ($doctor->profile_picture_path) {
                Storage::disk('public')->delete($doctor->profile_picture_path);
            }
            $doctor->profile_picture_path = null;
            $doctor->save();
            return response()->noContent();
        } catch (\Exception $e) {
            Log::error('Błąd podczas usuwania awatara: ' . $e->getMessage());
            return response()->json([
                'message' => 'Wystąpił błąd podczas usuwania awatara.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function generateDoctorsReport(Request $request)
    {
        $this->authorize('viewAny', Doctor::class);

        $query = Doctor::query()->with(['user', 'procedures.category'])->orderBy('id', 'asc');

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('email', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        if ($request->filled('specialization')) {
            $query->where('specialization', $request->input('specialization'));
        }

        $doctors = $query->get();

        if ($request->has('config')) {
            try {
                $config = json_decode($request->input('config'), true);


                $dataForReport = $doctors->map(function ($doctor) {
                    $doctorData = [
                        'id' => $doctor->id,
                        'first_name' => $doctor->first_name,
                        'last_name' => $doctor->last_name,
                        'specialization' => $doctor->specialization,
                        'user_email' => $doctor->user ? $doctor->user->email : 'Brak',
                        'bio' => $doctor->bio ?: 'Brak opisu',
                        'created_at' => $doctor->created_at,
                    ];

                    $doctorData['user'] = [
                        'email' => $doctor->user ? $doctor->user->email : 'Brak emaila'
                    ];

                    if ($doctor->procedures && $doctor->procedures->count() > 0) {
                        $doctorData['procedures'] = $doctor->procedures->map(function ($procedure) {
                            return [
                                'item' => $procedure->name,
                                'quantity' => 1,
                                'price' => is_null($procedure->base_price) ? 0.0 : (float)$procedure->base_price,
                                'category' => $procedure->category->name ?? 'Bez kategorii'
                            ];
                        })->toArray();
                    } else {
                        $doctorData['procedures'] = [];
                    }

                    return $doctorData;
                });

                $payload = [
                    'config' => $config,
                    'jsonData' => json_encode($dataForReport->toArray()),
                ];


                $response = Http::withBody(json_encode($payload), 'application/json')
                    ->timeout(30)->post('http://localhost:8080/api/generate-dynamic-report');

                if ($response->successful()) {
                    return response($response->body(), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => $response->header('Content-Disposition') ?: 'attachment; filename="raport_lekarzy.pdf"',
                    ]);
                } else {
                    Log::error('Błąd serwisu raportów: ' . $response->body());
                    return response()->json([
                        'error' => 'Nie udało się wygenerować raportu',
                        'details' => $response->json() ?? $response->body()
                    ], $response->status());
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Błąd połączenia z serwisem raportującym: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Nie można połączyć się z serwisem raportującym.',
                    'details' => $e->getMessage()
                ], 503);
            } catch (\Exception $e) {
                Log::error('Błąd generowania raportu lekarzy: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Błąd podczas generowania raportu',
                    'details' => $e->getMessage()
                ], 500);
            }
        }

        $reportConfig = [
            'title' => 'Raport Lekarzy',
            'companyInfo' => [
                'name' => 'NovaMed',
                'address' => 'ul. Zdrowotna 1, 00-001 Miasto',
                'phone' => '123-456-789',
                'email' => 'kontakt@novamed.pl',
            ],
            'columns' => [
                ['field' => 'id', 'label' => 'ID'],
                ['field' => 'first_name', 'label' => 'Imię'],
                ['field' => 'last_name', 'label' => 'Nazwisko'],
                ['field' => 'specialization', 'label' => 'Specjalizacja'],
                ['field' => 'user_email', 'label' => 'Email'],
            ],
        ];

        $jsonData = DoctorResource::collection($doctors)->toJson();

        $payload = [
            'config' => $reportConfig,
            'jsonData' => $jsonData,
        ];
        return response()->json($payload);
    }
}

