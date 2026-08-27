<?php

namespace App\Filament\Pages\Auth;

use App\Enums\SignupSource;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament's registration page, plus the one thing it cannot know: which part of
 * the site sent this person here.
 *
 * The offer on the homepage links to `?ref=static-offer`. That parameter is on
 * the URL when the page is first opened and gone by the time the form submits —
 * Livewire posts to its own endpoint — so it has to be captured at mount and
 * carried on the component. Anything not named in SignupSource resolves to null
 * rather than being stored, which is what stops a public query parameter from
 * becoming a free-text column.
 *
 * The property is public, which means Livewire lets the browser set it to any
 * string it likes — so it is resolved through the enum again on the way to the
 * column, not just at mount. Validating only at mount would have left the
 * allowlist as decoration, since the value that actually gets stored is whatever
 * the component holds when the form submits.
 *
 * What remains after that is unavoidable and harmless: someone can claim an arm
 * they did not arrive through. Attribution is self-reported by construction, and
 * a visitor lying about which of our own links they followed costs us a slightly
 * wrong count. Anything worth protecting would not live behind a query parameter.
 *
 * Note that $fillable is not the protection here and cannot be: AppServiceProvider
 * calls Model::unguard(), so mass-assignment guarding is off application-wide. The
 * protection is that this column has exactly one write site, and it re-validates.
 */
class Register extends BaseRegister
{
    /**
     * The resolved arm, or null when the ref was absent, stale or invented.
     * Stored as the backing value rather than the enum so Livewire can carry it
     * across the request that submits the form.
     */
    public ?string $signupSource = null;

    public function mount(): void
    {
        parent::mount();

        $this->signupSource = SignupSource::fromRef(request()->query('ref'))?->value;
    }

    /**
     * Registration, with the source written in the same insert.
     *
     * This overrides a one-line parent (`create($data)`) rather than following
     * it with a second save, because `signup_source` is not fillable and never
     * should be: the whole point is that it comes from an allowlisted parameter
     * and not from whatever the form posted. `make()` still honours $fillable
     * for the form data, so the column is set beside that rather than through it.
     */
    protected function handleRegistration(array $data): Model
    {
        $user = $this->getUserModel()::make($data);

        $user->signup_source = SignupSource::fromRef($this->signupSource);

        $user->save();

        return $user;
    }
}
