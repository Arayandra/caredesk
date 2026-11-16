<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Pasien extends Model {
 use HasFactory;
 protected $table='pasiens';
 protected $fillable=['Nama_Pasien','No_Hp','Alamat_Pasien','kode_pasien','jenis_kelamin','tanggal_lahir','golongan_darah','alergi'];
 protected $casts=['tanggal_lahir'=>'date'];
 public function appointments(){return $this->hasMany(Appointment::class,'pasien_id');}
 public function medicalRecords(){return $this->hasMany(MedicalRecord::class,'pasien_id');}
}
