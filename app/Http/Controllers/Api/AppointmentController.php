<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['patient', 'doctor'])->orderByDesc('scheduled_at');

        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pasien_id' => 'required|exists:pasiens,id',
            'doctor_id' => 'required|exists:doctors,id',
            'service_name' => 'required|string|max:120',
            'scheduled_at' => 'required|date|after_or_equal:now',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($this->hasScheduleConflict($data['doctor_id'], $data['scheduled_at'])) {
            return $this->scheduleConflictResponse();
        }

        $data['status'] = 'Menunggu';
        $appointment = Appointment::create($data);

        return response()->json([
            'message' => 'Janji temu dibuat.',
            'data' => $appointment->load(['patient', 'doctor']),
        ], 201);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'status' => 'sometimes|required|in:Menunggu,Diperiksa,Selesai,Dibatalkan',
            'pasien_id' => 'sometimes|required|exists:pasiens,id',
            'doctor_id' => 'sometimes|required|exists:doctors,id',
            'service_name' => 'sometimes|required|string|max:120',
            'scheduled_at' => 'sometimes|required|date|after_or_equal:now',
            'reason' => 'nullable|string|max:500',
        ]);

        $doctorId = $data['doctor_id'] ?? $appointment->doctor_id;
        $scheduledAt = $data['scheduled_at'] ?? $appointment->scheduled_at;

        if (($data['status'] ?? $appointment->status) !== 'Dibatalkan'
            && $this->hasScheduleConflict($doctorId, $scheduledAt, $appointment->id)) {
            return $this->scheduleConflictResponse();
        }

        $appointment->update($data);

        return response()->json([
            'message' => 'Janji temu diperbarui.',
            'data' => $appointment->fresh(['patient', 'doctor']),
        ]);
    }

    public function destroy(Appointment $appointment)
    {
        if ($appointment->medicalRecord()->exists()) {
            return response()->json([
                'message' => 'Janji temu memiliki catatan konsultasi dan tidak dapat dihapus.',
            ], 409);
        }

        $appointment->delete();

        return response()->json(['message' => 'Janji temu dihapus.']);
    }

    private function hasScheduleConflict($doctorId, $scheduledAt, ?int $exceptId = null): bool
    {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('scheduled_at', $scheduledAt)
            ->where('status', '!=', 'Dibatalkan')
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    private function scheduleConflictResponse()
    {
        return response()->json([
            'message' => 'Dokter sudah memiliki janji pada waktu tersebut.',
            'errors' => ['scheduled_at' => ['Pilih waktu lain.']],
        ], 422);
    }
}
