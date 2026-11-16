<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MedicalRecord extends Model {
 protected $fillable=['pasien_id','doctor_id','appointment_id','visited_at','complaint','diagnosis','treatment','prescription','blood_pressure','weight_kg','follow_up'];
 protected $casts=['visited_at'=>'datetime','weight_kg'=>'decimal:2'];
 public function patient(){return $this->belongsTo(Pasien::class,'pasien_id');}
 public function doctor(){return $this->belongsTo(Doctor::class);}
 public function appointment(){return $this->belongsTo(Appointment::class);}
}
