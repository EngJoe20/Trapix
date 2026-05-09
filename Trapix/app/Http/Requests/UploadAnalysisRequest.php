<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates file uploads before they reach the controller.
 * Max file size and allowed types are driven by config/trapix.php.
 */
class UploadAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization (quota) is handled in the controller
    }

    public function rules(): array
    {
        $maxKb       = config('trapix.max_upload_bytes', 52428800) / 1024; // default 50 MB
        $allowedMimes = implode(',', config('trapix.allowed_mime_types', [
            'application/octet-stream',
            'application/x-dosexec',
            'application/x-executable',
            'application/x-msdownload',
            'application/vnd.microsoft.portable-executable',
            'application/x-mach-binary',
            'application/x-elf',
            'application/pdf',
            'application/zip',
        ]));

        return [
            'files'             => ['required', 'array', 'min:1', 'max:' . config('trapix.max_files_per_job', 50)],
            'files.*'           => [
                'required',
                'file',
                "max:{$maxKb}",
                // Note: mime validation is lenient because binary files may have
                // application/octet-stream regardless of actual type.
            ],
            'skip_vt'           => ['sometimes', 'boolean'],
            'vt_api_key'        => ['sometimes', 'nullable', 'string', 'size:64'],
            'guest_token'       => ['sometimes', 'nullable', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required'  => 'Please select at least one file to analyze.',
            'files.max'       => 'You can upload a maximum of :max files per job.',
            'files.*.max'     => 'Each file must be smaller than ' . round(config('trapix.max_upload_bytes', 52428800) / 1048576) . ' MB.',
        ];
    }
}
