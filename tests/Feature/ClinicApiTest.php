<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Pasien;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_a_patient_can_be_registered_through_the_api(): void
    {
        $response = $this->postJson('/api/pasiens', [
            'Nama_Pasien' => 'Test Patient',
            'No_Hp' => '081234567890',
            'Alamat_Pasien' => 'Jl. Testing No. 1',
            'jenis_kelamin' => 'Laki-laki',
            'tanggal_lahir' => '1995-04-12',
            'golongan_darah' => 'O',
            'alergi' => 'Tidak ada',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.Nama_Pasien', 'Test Patient')
            ->assertJsonPath('data.kode_pasien', 'CD-00001');

        $this->assertDatabaseHas('pasiens', [
            'Nama_Pasien' => 'Test Patient',
            'No_Hp' => '081234567890',
        ]);
    }

    public function test_appointments_reject_a_duplicate_doctor_time_slot(): void
    {
        $patientOne = $this->makePatient('Patient One', '081111111111');
        $patientTwo = $this->makePatient('Patient Two', '082222222222');
        $doctor = $this->makeDoctor();
        $scheduledAt = now()->addDays(2)->setTime(9, 0)->toDateTimeString();

        $payload = [
            'pasien_id' => $patientOne->id,
            'doctor_id' => $doctor->id,
            'service_name' => 'Konsultasi umum',
            'scheduled_at' => $scheduledAt,
            'reason' => 'Pemeriksaan rutin',
        ];

        $this->postJson('/api/appointments', $payload)->assertCreated();

        $payload['pasien_id'] = $patientTwo->id;

        $this->postJson('/api/appointments', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_appointment_updates_reject_a_time_slot_already_used_by_the_doctor(): void
    {
        $patientOne = $this->makePatient('Patient One', '081111111111');
        $patientTwo = $this->makePatient('Patient Two', '082222222222');
        $doctor = $this->makeDoctor();
        $firstTime = now()->addDays(2)->setTime(9, 0);
        $secondTime = now()->addDays(2)->setTime(10, 0);

        $this->makeAppointment($patientOne, $doctor, $firstTime);
        $appointmentToUpdate = $this->makeAppointment($patientTwo, $doctor, $secondTime);

        $this->putJson('/api/appointments/'.$appointmentToUpdate->id, [
            'scheduled_at' => $firstTime->toDateTimeString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentToUpdate->id,
            'scheduled_at' => $secondTime->toDateTimeString(),
        ]);
    }

    public function test_consultation_must_match_its_appointment_and_completes_the_appointment(): void
    {
        $patient = $this->makePatient('Correct Patient', '081111111111');
        $otherPatient = $this->makePatient('Other Patient', '082222222222');
        $doctor = $this->makeDoctor();
        $appointment = $this->makeAppointment(
            $patient,
            $doctor,
            now()->addDay()->setTime(9, 0),
            'Diperiksa'
        );

        $payload = [
            'pasien_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'visited_at' => now()->toDateTimeString(),
            'complaint' => 'Sakit kepala',
            'diagnosis' => 'Catatan pemeriksaan demonstrasi',
            'treatment' => 'Istirahat dan pemantauan',
        ];

        $this->postJson('/api/records', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('appointment_id');

        $payload['pasien_id'] = $patient->id;

        $this->postJson('/api/records', $payload)
            ->assertCreated()
            ->assertJsonPath('data.pasien_id', $patient->id)
            ->assertJsonPath('data.appointment_id', $appointment->id);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'Selesai',
        ]);
        $this->assertDatabaseHas('medical_records', [
            'appointment_id' => $appointment->id,
            'pasien_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);
    }

    public function test_a_patient_with_consultation_history_cannot_be_deleted(): void
    {
        $patient = $this->makePatient('History Patient', '083333333333');
        $doctor = $this->makeDoctor();

        MedicalRecord::create([
            'pasien_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'visited_at' => now(),
            'complaint' => 'Keluhan demonstrasi',
            'diagnosis' => 'Catatan demonstrasi',
        ]);

        $this->deleteJson('/api/pasiens/'.$patient->id)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Pasien memiliki riwayat janji temu atau konsultasi. Hapus tidak diizinkan demi menjaga riwayat klinis.');

        $this->assertDatabaseHas('pasiens', ['id' => $patient->id]);
    }

    public function test_dashboard_and_report_endpoints_return_their_expected_sections(): void
    {
        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'stats' => ['patients', 'today', 'waiting', 'completed', 'records'],
                'week',
                'todayAppointments',
                'recentRecords',
            ]);

        $this->getJson('/api/reports')
            ->assertOk()
            ->assertJsonStructure([
                'month',
                'total',
                'completed',
                'completionRate',
                'byService',
                'daily',
            ]);
    }

    private function makePatient(string $name, string $phone): Pasien
    {
        return Pasien::create([
            'Nama_Pasien' => $name,
            'No_Hp' => $phone,
            'Alamat_Pasien' => 'Jl. Testing No. 1',
        ]);
    }

    private function makeDoctor(): Doctor
    {
        return Doctor::create([
            'name' => 'dr. Test',
            'specialty' => 'Dokter Umum',
            'room' => 'TEST-01',
            'is_active' => true,
        ]);
    }

    private function makeAppointment(
        Pasien $patient,
        Doctor $doctor,
        $scheduledAt,
        string $status = 'Menunggu'
    ): Appointment {
        return Appointment::create([
            'pasien_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'service_name' => 'Konsultasi umum',
            'scheduled_at' => $scheduledAt,
            'status' => $status,
        ]);
    }
}
