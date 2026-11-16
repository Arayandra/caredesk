<?php

namespace Database\Seeders;

use App\Models\Pasien;
use Illuminate\Database\Seeder;

class PasienSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            [
                'Nama_Pasien' => 'Andi Pratama',
                'No_Hp' => '081234567890',
                'Alamat_Pasien' => 'Jl. Melati No. 12, Tangerang',
            ],
            [
                'Nama_Pasien' => 'Siti Rahmawati',
                'No_Hp' => '081298765432',
                'Alamat_Pasien' => 'Jl. Mawar No. 8, Serang',
            ],
            [
                'Nama_Pasien' => 'Budi Santoso',
                'No_Hp' => '085712345678',
                'Alamat_Pasien' => 'Perumahan Cendana Blok B2, Cilegon',
            ],
        ];

        foreach ($samples as $sample) {
            Pasien::firstOrCreate(['No_Hp' => $sample['No_Hp']], $sample);
        }
    }
}
