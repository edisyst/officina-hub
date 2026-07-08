<?php

namespace App\Livewire\Communications;

use App\Models\Cliente;
use App\Models\Commessa;
use App\Models\Communication;
use App\Services\Communications\CommunicationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Timeline extends Component
{
    use WithPagination;

    // Context: one of these will be set
    public ?int $customerId   = null;
    public ?int $workOrderId  = null;

    // Filters
    public string  $search         = '';
    public string  $filterChannel  = '';

    // New annotation form
    public bool   $showAnnotaModal = false;
    public string $annoChannel     = 'phone';
    public string $annoDirection   = 'inbound';
    public string $annoSubject     = '';
    public string $annoBody        = '';
    public string $annoOccurredAt  = '';
    public ?int   $annoWorkOrderId = null;

    protected $queryString = ['search', 'filterChannel'];

    public function mount(?int $customerId = null, ?int $workOrderId = null): void
    {
        $this->customerId  = $customerId;
        $this->workOrderId = $workOrderId;

        if ($workOrderId && ! $customerId) {
            $wo = Commessa::findOrFail($workOrderId);
            $this->customerId = $wo->cliente_id;
        }

        $this->annoWorkOrderId = $workOrderId;
        $this->annoOccurredAt  = now()->format('Y-m-d\TH:i');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterChannel(): void
    {
        $this->resetPage();
    }

    public function apriAnnota(): void
    {
        $this->showAnnotaModal = true;
    }

    public function salvaAnnotazione(): void
    {
        $this->validate([
            'annoChannel'    => 'required|in:whatsapp,sms,email,phone,note',
            'annoDirection'  => 'required|in:outbound,inbound',
            'annoBody'       => 'required|string|max:10000',
            'annoOccurredAt' => 'nullable|date',
        ]);

        app(CommunicationService::class)->logManual([
            'customer_id'   => $this->customerId,
            'work_order_id' => $this->annoWorkOrderId ?: null,
            'channel'       => $this->annoChannel,
            'direction'     => $this->annoDirection,
            'subject'       => $this->annoSubject ?: null,
            'body'          => $this->annoBody,
            'occurred_at'   => $this->annoOccurredAt ? \Carbon\Carbon::parse($this->annoOccurredAt) : now(),
        ], Auth::user());

        $this->reset(['annoSubject', 'annoBody', 'showAnnotaModal']);
        $this->annoOccurredAt = now()->format('Y-m-d\TH:i');
        $this->dispatch('communication-added');
    }

    public function render()
    {
        $commessa = $this->workOrderId ? Commessa::find($this->workOrderId) : null;

        $query = Communication::with(['user', 'workOrder'])
            ->when($commessa, fn($q) => $q->forWorkOrderContext($commessa), fn($q) => $q->where('customer_id', $this->customerId))
            ->when($this->filterChannel, fn($q, $ch) => $q->where('channel', $ch))
            ->orderByDesc('occurred_at');

        if ($this->search) {
            if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
                $query->where('body', 'like', "%{$this->search}%");
            } else {
                $query->whereFullText('body', $this->search);
            }
        }

        $communications = $query->paginate(20);
        $totalCount     = Communication::when($commessa, fn($q) => $q->forWorkOrderContext($commessa), fn($q) => $q->where('customer_id', $this->customerId))
            ->count();

        return view('livewire.communications.timeline', compact('communications', 'totalCount'));
    }
}
