<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCsvRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,text',
                'extensions:csv',
                'max:102400', // 100MB maximum
            ],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Es necesario adjuntar un archivo CSV para la importación.',
            'file.file' => 'El archivo enviado no es válido.',
            'file.mimes' => 'El archivo debe tener formato CSV (.csv).',
            'file.extensions' => 'El archivo debe tener formato y extensión .csv.',
            'file.max' => 'El archivo excede el tamaño máximo permitido de 100MB.',
        ];
    }
}
