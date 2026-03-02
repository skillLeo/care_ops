<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateType extends Model
{
    protected $fillable = [
        'name',
        'template_path',
        'name_x',
        'name_y',
        'date_x',
        'date_y',
        'name_font',
    ];

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public static function fontOptions(): array
    {
        $options = [];

        foreach (self::fontFiles() as $file) {
            $label = pathinfo($file, PATHINFO_FILENAME);
            $options[self::fontKey($label)] = $label;
        }

        ksort($options);

        return $options;
    }

    public static function fontData(): array
    {
        $data = [];

        foreach (self::fontFiles() as $file) {
            $label = pathinfo($file, PATHINFO_FILENAME);
            $data[self::fontKey($label)] = ['R' => $file];
        }

        return $data;
    }

    private static function fontFiles(): array
    {
        $paths = glob(resource_path('fonts/*.{ttf,otf,TTF,OTF}'), GLOB_BRACE);

        return array_map('basename', $paths ?: []);
    }

    private static function fontKey(string $label): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($label));
    }
}
