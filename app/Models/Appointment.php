<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Appointment extends Model {
 protected $fillable=['pasien_id','doctor_id','service_name','scheduled_at','status','reason','internal_note'];
 protected $casts=['scheduled_at'=>'datetime'];
 public function patient(){return $this->belongsTo(Pasien::class,'pasien_id');}
 public function doctor(){return $this->belongsTo(Doctor::class);}
 public function medicalRecord(){return $this->hasOne(MedicalRecord::class);}
}
