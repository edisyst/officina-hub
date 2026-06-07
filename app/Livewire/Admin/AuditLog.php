<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class AuditLog extends Component
{
    use WithPagination;

    public string $filtroUtente    = '';
    public string $filtroEntita    = '';
    public string $filtroAzione    = '';
    public string $filtroDal       = '';
    public string $filtroAl        = '';

    protected $queryString = [
        'filtroUtente'  => ['except' => ''],
        'filtroEntita'  => ['except' => ''],
        'filtroAzione'  => ['except' => ''],
        'filtroDal'     => ['except' => ''],
        'filtroAl'      => ['except' => ''],
    ];

    public function updatingFiltro(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroUtente(): void  { $this->resetPage(); }
    public function updatedFiltroEntita(): void  { $this->resetPage(); }
    public function updatedFiltroAzione(): void  { $this->resetPage(); }
    public function updatedFiltroDal(): void     { $this->resetPage(); }
    public function updatedFiltroAl(): void      { $this->resetPage(); }

    public function esportaCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $log = $this->buildQuery()->cursor();

        return Response::streamDownload(function () use ($log) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 per compatibilità Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data/Ora', 'Utente', 'Azione', 'Entità', 'ID Entità', 'Modifiche'], ';');

            foreach ($log as $row) {
                fputcsv($out, [
                    $row->created_at->format('d/m/Y H:i:s'),
                    $row->causer?->name ?? 'Sistema',
                    $row->event ?? $row->description,
                    class_basename($row->subject_type ?? ''),
                    $row->subject_id,
                    json_encode($row->properties->get('attributes', []), JSON_UNESCAPED_UNICODE),
                ], ';');
            }
            fclose($out);
        }, 'audit_log_' . now()->format('Ymd_His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildQuery()
    {
        $query = Activity::with('causer')
            ->orderBy('created_at', 'desc');

        if ($this->filtroUtente) {
            $query->whereHas('causer', fn($q) => $q->where('name', 'like', "%{$this->filtroUtente}%"));
        }

        if ($this->filtroEntita) {
            $query->where('subject_type', 'like', "%{$this->filtroEntita}%");
        }

        if ($this->filtroAzione) {
            $query->where('event', $this->filtroAzione);
        }

        if ($this->filtroDal) {
            $query->whereDate('created_at', '>=', $this->filtroDal);
        }

        if ($this->filtroAl) {
            $query->whereDate('created_at', '<=', $this->filtroAl);
        }

        return $query;
    }

    public function render()
    {
        $attivita = $this->buildQuery()->paginate(25);

        return view('livewire.admin.audit-log', [
            'attivita' => $attivita,
        ]);
    }
}
