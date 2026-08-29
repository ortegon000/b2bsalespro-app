<?php

namespace App\Domain\ObjecionCero\Livewire;

use App\Domain\ObjecionCero\Models\Feedback;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FeedbackWidget extends Component
{
    public bool $open = false;

    public bool $sent = false;

    public string $message = '';

    public ?string $page = null;

    public function mount(): void
    {
        $this->page = request()->route()?->getName();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
        $this->sent = false;
    }

    public function send(): void
    {
        $this->validate([
            'message' => 'required|string|min:3|max:2000',
        ]);

        Feedback::create([
            'user_id' => auth()->id(),
            'page' => $this->page,
            'message' => $this->message,
        ]);

        $this->reset('message');
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.objecion-cero.feedback-widget');
    }
}
