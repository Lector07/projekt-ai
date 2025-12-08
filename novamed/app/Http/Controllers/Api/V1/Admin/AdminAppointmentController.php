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
            $doctorNameTerm = '%' . strtolower($request->doctor_name) . '%';
            $query->whereHas('doctor', function($q) use ($doctorNameTerm) {
                $q->whereRaw('LOWER(first_name) LIKE ?', [$doctorNameTerm])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$doctorNameTerm])
                    ->orWhereHas('user', function($userDoctorQuery) use ($doctorNameTerm) {
                        $userDoctorQuery->whereRaw('LOWER(name) LIKE ?', [$doctorNameTerm])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$doctorNameTerm]);
                    });
            });
        }

        if ($request->has('patient_name') && !empty($request->patient_name)) {
            $patientNameTerm = '%' . strtolower($request->patient_name) . '%';
            $query->whereHas('patient', function($q) use ($patientNameTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', [$patientNameTerm])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$patientNameTerm]);
            });
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('appointment_datetime', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('appointment_datetime', '<=', $request->date_to);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->whereRaw('LOWER(status) = ?', [strtolower($request->status)]);
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
        $appointment->load(['patient', 'doctor', 'procedure']);

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
        $validated = $request->validate([
            'config' => 'required|array',
            'filters' => 'nullable|array',
        ]);

        Log::info('--- KONTROLER LARAVELA - OTRZYMANA KONFIGURACJA ---');
        Log::info(json_encode($validated['config'], JSON_PRETTY_PRINT));

        $query = \App\Models\Appointment::query()->with(['patient', 'doctor', 'procedure']);
        $filters = $validated['filters'] ?? [];

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['patient_name'])) {
            $query->whereHas('patient', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['patient_name'] . '%');
            });
        }

        if (!empty($filters['doctor_name'])) {
            $query->whereHas('doctor', function ($q) use ($filters) {
                $name = $filters['doctor_name'];
                $q->where('first_name', 'like', '%' . $name . '%')
                    ->orWhere('last_name', 'like', '%' . $name . '%');
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('appointment_datetime', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('appointment_datetime', '<=', $filters['date_to']);
        }

        $appointments = $query->get();

        $dataForReport = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'appointment_datetime' => $appointment->appointment_datetime,
                'status_translated' => $this->translateStatus($appointment->status),
                'patient_name' => $appointment->patient ? $appointment->patient->name : '',
                'doctor_full_name' => $appointment->doctor ? ($appointment->doctor->first_name . ' ' . $appointment->doctor->last_name) : '',
                'procedure_name' => $appointment->procedure ? $appointment->procedure->name : '',
                'procedure_base_price' => $appointment->procedure ? (float) str_replace(',', '.', (string) $appointment->procedure->base_price) : 0.0,
                'patient_notes' => $appointment->patient_notes,
                'admin_notes' => $appointment->clinic_notes ?? '',
                'created_at' => $appointment->created_at,
                'updated_at' => $appointment->updated_at,
            ];
        });

        $dataForReport = $appointments->map(function ($appointment) {
            $billing_details = [
                ['item' => 'Konsultacja medyczna', 'quantity' => 1, 'price' => 250.00],
                ['item' => 'Materiały opatrunkowe', 'quantity' => 5, 'price' => 75.50],
                ['item' => 'Podane leki', 'quantity' => 2, 'price' => 45.00],
            ];

            return [
                'id' => $appointment->id,
                'appointment_datetime' => $appointment->appointment_datetime,
                'status_translated' => $this->translateStatus($appointment->status),
                'patient_name' => $appointment->patient ? $appointment->patient->name : '',
                'doctor_full_name' => $appointment->doctor ? ($appointment->doctor->first_name . ' ' . $appointment->doctor->last_name) : '',
                'procedure_name' => $appointment->procedure ? $appointment->procedure->name : '',
                'procedure_base_price' => $appointment->procedure ? (float) $appointment->procedure->base_price : 0.0,
                'patient_notes' => $appointment->patient_notes,

                'billing_details' => $billing_details
            ];
        });

        $jsonData = $dataForReport->toJson();

        if (isset($validated['config']['subreportConfigs']) && is_array($validated['config']['subreportConfigs'])) {
            $validated['config']['subreportConfigs'] = (object) $validated['config']['subreportConfigs'];
        }

        if (isset($validated['config']['fieldTypes'])) {
            unset($validated['config']['fieldTypes']);
        }

        $payload = [
            'config' => $validated['config'],
            'jsonData' => $jsonData
        ];

        $payloadJson = json_encode($payload);
        $payloadSize = strlen($payloadJson);

        Log::info('--- AdminAppointmentController - PAYLOAD WYSYŁANY DO JAVY ---');
        Log::info('Config keys: ' . json_encode(array_keys($validated['config'])));
        Log::info('JsonData length: ' . strlen($jsonData));
        Log::info('Total payload size: ' . number_format($payloadSize / 1024, 2) . ' KB');
        Log::info('Number of appointments in report: ' . $dataForReport->count());

        // Sprawdź czy serwis raportów jest włączony
        $reportServiceEnabled = config('services.report.enabled', true);
        $reportServiceUrl = config('services.jrxml.url', 'https://jrxml-service-1.onrender.com') . '/api/generate-dynamic-report';

        if (!$reportServiceEnabled) {
            Log::warning('Serwis raportów jest wyłączony w konfiguracji');
            return response()->json([
                'error' => 'Generowanie raportów jest tymczasowo niedostępne.',
                'message' => 'Funkcja raportów nie jest dostępna w tym środowisku.'
            ], 503);
        }

        try {
            $response = Http::withBody($payloadJson, 'application/json')
                ->timeout(60)
                ->withOptions([
                    'verify' => false,
                    'http_errors' => false,
                ])
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
            case 'cancelled':
                return 'Anulowana';
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
