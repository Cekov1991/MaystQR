<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbuseReportRequest extends FormRequest
{
    /**
     * The reasons a report can be filed under. A fixed list keeps reports
     * triageable, and mirrors the prohibitions in Terms section 4.
     *
     * @var array<int, string>
     */
    public const REASONS = [
        'phishing',
        'malware',
        'illegal_content',
        'spam',
        'harassment',
        'other',
    ];

    /**
     * Public. The people who need this form are strangers by definition — anyone
     * who scanned a code and found something harmful behind it.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `validated()` returns only the keys that were present in the request, so an
     * omitted optional field disappears from the payload rather than arriving as
     * null. Anything reading the result by key then hits an undefined index.
     * Browsers always submit the input, but a report filed by anything other than
     * our own form does not have to.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reporter_email' => $this->input('reporter_email'),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Deliberately not validated as a URL. Someone reading a printed
            // poster may type a partial reference, and rejecting them loses the
            // report — which is worse than receiving a vague one.
            'code_url' => ['required', 'string', 'max:2048'],
            'reason' => ['required', 'string', Rule::in(self::REASONS)],
            'details' => ['required', 'string', 'min:20', 'max:5000'],
            // Optional on purpose: requiring contact details suppresses reports,
            // and we do not need to reply in order to act.
            'reporter_email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code_url.required' => 'Please tell us which QR code or link you are reporting.',
            'reason.required' => 'Please choose what is wrong with it.',
            'reason.in' => 'Please choose one of the listed reasons.',
            'details.required' => 'Please describe what you found.',
            'details.min' => 'Please give us a little more detail so we can investigate.',
            'reporter_email.email' => 'That does not look like an email address. You can also leave it blank.',
        ];
    }
}
