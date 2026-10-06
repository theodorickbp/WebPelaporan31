<?php

namespace App\Http\Requests;

use App\Enums\ReportCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ReportCategory::class)],
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'location' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photos' => ['required', 'array', 'min:1', 'max:'.config('sipasar.max_photos')],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('sipasar.max_photo_kb')],
        ];
    }

    public function messages(): array
    {
        $maxMb = (int) round(config('sipasar.max_photo_kb') / 1024);

        return [
            'photos.required' => 'Tambahkan minimal 1 foto bukti.',
            'photos.min' => 'Tambahkan minimal 1 foto bukti.',
            'photos.max' => 'Maksimal '.config('sipasar.max_photos').' foto per laporan.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'photos.*.max' => "Ukuran tiap foto maksimal {$maxMb} MB.",
            'photos.*.uploaded' => 'Foto gagal diunggah. Coba lagi dengan foto yang lebih kecil.',
            'category.required' => 'Pilih jenis masalahnya.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category' => 'jenis masalah',
            'title' => 'judul',
            'location' => 'lokasi',
            'description' => 'keterangan',
            'photos' => 'foto',
        ];
    }
}
