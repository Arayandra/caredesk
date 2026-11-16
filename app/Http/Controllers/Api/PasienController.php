<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;use App\Models\Pasien;use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;
class PasienController extends Controller {
 public function index(Request $r):JsonResponse{$q=Pasien::query()->withCount(['appointments','medicalRecords'])->latest('id');if($term=$r->get('q'))$q->where(fn($x)=>$x->where('Nama_Pasien','like',"%$term%")->orWhere('No_Hp','like',"%$term%")->orWhere('kode_pasien','like',"%$term%"));$items=$q->get();return response()->json(['data'=>$items,'total'=>$items->count()]);}
 public function store(Request $r):JsonResponse{$data=$this->validated($r);$data['kode_pasien']='CD-'.str_pad((string)(Pasien::max('id')+1),5,'0',STR_PAD_LEFT);$item=Pasien::create($data);return response()->json(['message'=>'Pasien berhasil ditambahkan.','data'=>$item],201);}
 public function show(Pasien $pasien):JsonResponse{return response()->json(['data'=>$pasien->load(['appointments.doctor','medicalRecords.doctor'])]);}
 public function update(Request $r,Pasien $pasien):JsonResponse{$pasien->update($this->validated($r));return response()->json(['message'=>'Profil pasien diperbarui.','data'=>$pasien->fresh()]);}
 public function destroy(Pasien $pasien):JsonResponse{if($pasien->appointments()->exists()||$pasien->medicalRecords()->exists()){return response()->json(['message'=>'Pasien memiliki riwayat janji temu atau konsultasi. Hapus tidak diizinkan demi menjaga riwayat klinis.'],409);}$pasien->delete();return response()->json(['message'=>'Pasien dihapus.']);}
 private function validated(Request $r):array{return $r->validate(['Nama_Pasien'=>'required|string|max:120','No_Hp'=>'required|string|max:30','Alamat_Pasien'=>'required|string|max:255','jenis_kelamin'=>'nullable|in:Laki-laki,Perempuan','tanggal_lahir'=>'nullable|date|before_or_equal:today','golongan_darah'=>'nullable|in:A,B,AB,O','alergi'=>'nullable|string|max:1000']);}
}
