<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Doctor extends Model {
 use HasFactory;
 protected $fillable=['name','specialty','license_number','phone','room','is_active'];
 protected $casts=['is_active'=>'boolean'];
 public function appointments(){return $this->hasMany(Appointment::class);}
 public function medicalRecords(){return $this->hasMany(MedicalRecord::class);}
}
