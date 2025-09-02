<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppointmentResource;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use App\Http\Requests\Api\V1\Admin\StoreAppointmentRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAppointmentRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminAppointmentController extends Controller
{
    /**
     * Pobierz listę wszystkich wizyt z filtrowaniem
     *
     * @param Request $request
     * @return ResourceCollection
     */
    public function index(Request $request): ResourceCollection
    {
        $query = Appointment::query()
            ->with(['patient', 'doctor.user', 'procedure']);

        if ($request->has('doctor_name') && !empty($request->doctor_name)) {
            $doctorNameTerm = '%' . $request->doctor_name . '%';
            $query->whereHas('doctor', function($q) use ($doctorNameTerm) {
                $q->where('first_name', 'ilike', $doctorNameTerm)
                    ->orWhere('last_name', 'ilike', $doctorNameTerm)
                    ->orWhereHas('user', function($userDoctorQuery) use ($doctorNameTerm) {
                        $userDoctorQuery->where('name', 'ilike', $doctorNameTerm)
                            ->orWhere('email', 'ilike', $doctorNameTerm);
                    });
            });
        }

        if ($request->has('patient_name') && !empty($request->patient_name)) {
            $patientNameTerm = '%' . $request->patient_name . '%';
            $query->whereHas('patient', function($q) use ($patientNameTerm) {
                $q->where('name', 'ilike', $patientNameTerm)
                    ->orWhere('email', 'ilike', $patientNameTerm);
            });
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('appointment_datetime', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('appointment_datetime', '<=', $request->date_to);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', 'ilike', $request->status);
        }

        $query->orderBy('id', 'desc');

        $appointments = $query->paginate();

        return AppointmentResource::collection($appointments);
    }

    /**
     * Zapisz nową wizytę
     *
     * @param Request $request
     * @return AppointmentResource
     */
    public function store(StoreAppointmentRequest $request): AppointmentResource
    {
        $validated = $request->validated();
        $appointment = Appointment::create($validated);
        $appointment->load(['patient', 'doctor', 'procedure']);
        return new AppointmentResource($appointment);
    }

    /**
     * Pobierz szczegóły konkretnej wizyty
     *
     * @param Appointment $appointment
     * @return AppointmentResource
     */
    public function show(Appointment $appointment): AppointmentResource
    {
        $appointment->load(['patient', 'doctor', 'procedure']);

        return new AppointmentResource($appointment);
    }

    /**
     * Aktualizuj dane wizyty
     *
     * @param Request $request
     * @param Appointment $appointment
     * @return AppointmentResource
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        $validated = $request->validated();

        $appointment->update($validated);
        $appointment->refresh();

        return new AppointmentResource($appointment);
    }

    /**
     * Usuń wizytę
     *
     * @param Appointment $appointment
     * @return Response
     */
    public function destroy(Appointment $appointment): Response
    {
        $appointment->delete();

        return response()->noContent();
    }

    public function generateAppointmentsReport(Request $request)
    {
        // 1. Walidacja danych wejściowych (konfiguracji i filtrów)
        $validated = $request->validate([
            'config' => 'required|array',
            'filters' => 'nullable|array',
        ]);

        // 2. Pobieranie i filtrowanie danych (logika z Twojej metody index)
        $query = Appointment::query()->with(['patient', 'doctor', 'procedure']);

        $filters = $validated['filters'] ?? [];

        if (!empty($filters['doctor_name'])) {
            $doctorNameTerm = '%' . $filters['doctor_name'] . '%';
            $query->whereHas('doctor', function($q) use ($doctorNameTerm) {
                $q->where('first_name', 'ilike', $doctorNameTerm)
                    ->orWhere('last_name', 'ilike', $doctorNameTerm);
            });
        }
        if (!empty($filters['patient_name'])) {
            $patientNameTerm = '%' . $filters['patient_name'] . '%';
            $query->whereHas('patient', function($q) use ($patientNameTerm) {
                $q->where('name', 'ilike', $patientNameTerm);
            });
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('appointment_datetime', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $query->where('appointment_datetime', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        $appointments = $query->orderBy('appointment_datetime', 'desc')->get();

        // 3. Użycie AppointmentResource do przygotowania danych dla raportu
        $dataForReport = \App\Http\Resources\Api\V1\AppointmentResource::collection($appointments)->resolve();
        $jsonData = json_encode($dataForReport);

        // 4. Przygotowanie payloadu dla serwisu Javy
        $payload = [
            'config' => $validated['config'],
            'jsonData' => $jsonData
        ];

        // 5. Wysyłka do serwisu raportującego i zwrot odpowiedzi
        try {
            $reportServiceUrl = 'http://localhost:8080/api/generate-dynamic-report';

            $response = Http::withBody(json_encode($payload), 'application/json')
                ->timeout(30)
                ->post($reportServiceUrl);

            if ($response->successful()) {
                return response($response->body(), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => $response->header('Content-Disposition') ?: 'attachment; filename="raport_wizyt.pdf"',
                ]);
            }

            Log::error('Błąd serwisu raportującego: ' . $response->body());
            return response()->json(['error' => 'Wystąpił błąd podczas generowania raportu.', 'details' => $response->json() ?? $response->body()], $response->status());

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Błąd połączenia z serwisem raportującym: ' . $e->getMessage());
            return response()->json(['error' => 'Nie można połączyć się z serwisem raportującym.'], 503);
        }
    }


    private function translateStatus(string $status): string
    {
        switch ($status) {
            case 'scheduled':
                return 'Zaplanowana';
            case 'confirmed':
                return 'Potwierdzona';
            case 'completed':
                return 'Zakończona';
            case 'cancelled_by_patient':
                return 'Odwołana przez pacjenta';
            case 'cancelled_by_clinic':
                return 'Odwołana przez klinikę';
            case 'no_show':
                return 'Nieobecność';
            default:
                return ucfirst(str_replace('_', ' ', $status));
        }
    }
}
