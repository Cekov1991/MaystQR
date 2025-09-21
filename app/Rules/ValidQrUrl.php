<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\QrCode;

class ValidQrUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validation = QrCode::validateUrl($value);
        
        if (!$validation['valid']) {
            $fail($validation['message']);
        }
    }
}
