<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicalRecordController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => MedicalRecord::with(['patient', 'doctor'])->latest('visited_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pasien_id' => 'required|exists:pasiens,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'visited_at' => 'required|date',
            'complaint' => 'required|string|max:10000',
            'diagnosis' => 'required|string|max:10000',
            'treatment' => 'nullable|string|max:10000',
            'prescription' => 'nullable|string|max:10000',
            'blood_pressure' => 'nullable|string|max:20',
            'weight_kg' => 'nullable|numeric|min:1|max:400',
            'follow_up' => 'nullable|string|max:10000',
        ]);

        if (!empty($data['appointment_id'])) {
            $appointment = Appointment::findOrFail($data['appointment_id']);

            if ((int) $appointment->pasien_id !== (int) $data['pasien_id']
                || (int) $appointment->doctor_id !== (int) $data['doctor_id']) {
                throw ValidationException::withMessages([
                    'appointment_id' => ['Pasien dan dokter harus sesuai dengan janji temu yang dipilih.'],
                ]);
            }

            if ($appointment->status === 'Dibatalkan') {
                throw ValidationException::withMessages([
                    'appointment_id' => ['Janji temu yang dibatalkan tidak dapat ditautkan ke catatan konsultasi.'],
                ]);
            }

            if ($appointment->medicalRecord()->exists()) {
                throw ValidationException::withMessages([
                    'appointment_id' => ['Janji temu ini sudah memiliki catatan konsultasi.'],
                ]);
            }
        }

        $record = DB::transaction(function () use ($data) {
            $record = MedicalRecord::create($data);

            if (!empty($data['appointment_id'])) {
                Appointment::whereKey($data['appointment_id'])
                    ->update(['status' => 'Selesai']);
            }

            return $record;
        });

        return response()->json([
            'message' => 'Catatan konsultasi disimpan.',
            'data' => $record->load(['patient', 'doctor']),
        ], 201);
    }
}
