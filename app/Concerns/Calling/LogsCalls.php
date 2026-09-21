<?php

namespace App\Concerns\Calling;

use App\Enums\Calling\CallOutcome;
use App\Models\Calling\Business;
use App\Models\Calling\Call;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;

/**
 * Shared "how did it go?" call-logging behavior for Livewire pages that show
 * businesses (the businesses list and a single business's detail page).
 *
 * Every call attempt is inserted as a new row; nothing already logged is
 * ever updated or overwritten.
 *
 * @phpstan-ignore trait.unused (used by Livewire single-file components)
 */
trait LogsCalls
{
    /**
     * Id of the business currently being logged, while its outcome modal is open.
     */
    public ?int $loggingCallForBusinessId = null;

    /**
     * Id of the phone number selected for the call being logged.
     */
    public ?int $loggingCallPhoneId = null;

    /**
     * The outcome stage selected so far: null, or 'answered'.
     */
    public ?string $callStage = null;

    public ?string $callOutcome = null;

    public string $callNote = '';

    public ?string $callFollowUpAt = null;

    /**
     * Open the "which number?" step if the business has more than one phone,
     * otherwise go straight to the outcome step.
     */
    public function startCall(int $businessId): void
    {
        $business = $this->findBusinessForCall($businessId);

        $this->authorize('create', [Call::class, $business]);

        $this->resetCallForm();
        $this->loggingCallForBusinessId = $business->id;

        $phones = $business->phones;

        if ($phones->count() <= 1) {
            $this->loggingCallPhoneId = $phones->first()?->id;
            Flux::modal('call-outcome')->show();

            return;
        }

        Flux::modal('call-phone-picker')->show();
    }

    /**
     * Choose which phone number is being called, then move to the outcome step.
     */
    public function chooseCallPhone(int $phoneId): void
    {
        $this->loggingCallPhoneId = $phoneId;

        Flux::modal('call-phone-picker')->close();
        Flux::modal('call-outcome')->show();
    }

    /**
     * Record that the call was not answered.
     */
    public function logNotAnswered(): void
    {
        $this->saveCall(CallOutcome::NotAnswered);

        Flux::modal('call-outcome')->close();
        $this->resetCallForm();
        $this->afterCallLogged();
        Flux::toast(variant: 'success', text: __('Marked as not answered.'));
    }

    /**
     * Move to the "what did they say?" step after choosing the call was answered.
     */
    public function markAnswered(): void
    {
        $this->callStage = 'answered';
    }

    /**
     * Record the outcome for an answered call.
     */
    public function logAnswered(): void
    {
        $validated = $this->validate([
            'callOutcome' => ['required', 'in:pending,interested,rejected'],
            'callNote' => ['nullable', 'string', 'max:1000'],
            'callFollowUpAt' => ['nullable', 'date'],
        ]);

        $this->saveCall(
            CallOutcome::from($validated['callOutcome']),
            $validated['callNote'] ?: null,
            $validated['callFollowUpAt'] ?: null,
        );

        Flux::modal('call-outcome')->close();
        $this->resetCallForm();
        $this->afterCallLogged();
        Flux::toast(variant: 'success', text: __('Call logged.'));
    }

    /**
     * Change an already-answered business's status, recorded as a new call.
     */
    public function changeStatus(int $businessId, string $outcome): void
    {
        $business = $this->findBusinessForCall($businessId);

        $this->authorize('create', [Call::class, $business]);

        $this->saveCallFor($business, CallOutcome::from($outcome));

        $this->afterCallLogged();
        Flux::toast(variant: 'success', text: __('Status updated.'));
    }

    /**
     * Persist the call being logged through the modal flow.
     */
    protected function saveCall(CallOutcome $outcome, ?string $note = null, ?string $followUpAt = null): void
    {
        $business = $this->findBusinessForCall($this->loggingCallForBusinessId);

        $this->authorize('create', [Call::class, $business]);

        $this->saveCallFor($business, $outcome, $note, $followUpAt, $this->loggingCallPhoneId);
    }

    /**
     * Insert one call record for the given business.
     */
    protected function saveCallFor(
        Business $business,
        CallOutcome $outcome,
        ?string $note = null,
        ?string $followUpAt = null,
        ?int $phoneId = null,
    ): Call {
        return Call::create([
            'organization_id' => Auth::user()->organization_id,
            'business_id' => $business->id,
            'business_phone_id' => $phoneId ?? $business->primaryPhone?->id ?? $business->phones()->value('id'),
            'user_id' => Auth::id(),
            'outcome' => $outcome,
            'note' => $note,
            'follow_up_at' => $followUpAt,
            'called_at' => now(),
        ]);
    }

    /**
     * Find a business in the current organization for call logging.
     */
    protected function findBusinessForCall(?int $businessId): Business
    {
        return Business::query()->with('phones')->findOrFail($businessId);
    }

    /**
     * Hook for the host component to clear its own cached computed data
     * after a call is logged. No-op by default.
     */
    protected function afterCallLogged(): void
    {
        //
    }

    /**
     * Clear the call-logging form state.
     */
    protected function resetCallForm(): void
    {
        $this->reset(['loggingCallForBusinessId', 'loggingCallPhoneId', 'callStage', 'callOutcome', 'callNote', 'callFollowUpAt']);
        $this->resetValidation();
    }
}
