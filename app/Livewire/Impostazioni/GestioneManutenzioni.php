<?php

namespace App\Livewire\Impostazioni;

use App\Http\Requests\MaintenanceRuleRequest;
use App\Models\MaintenanceRule;
use Livewire\Component;

class GestioneManutenzioni extends Component
{
    public bool $showModal = false;
    public ?int $ruleId    = null;
    public string $name    = '';
    public ?int $every_km  = null;
    public ?int $every_months = null;

    protected function rules(): array
    {
        return (new MaintenanceRuleRequest())->rules();
    }

    public function apriNuovo(): void
    {
        $this->reset(['ruleId', 'name', 'every_km', 'every_months']);
        $this->showModal = true;
    }

    public function apriModifica(int $id): void
    {
        $rule            = MaintenanceRule::findOrFail($id);
        $this->ruleId    = $rule->id;
        $this->name      = $rule->name;
        $this->every_km  = $rule->every_km;
        $this->every_months = $rule->every_months;
        $this->showModal = true;
    }

    public function salva(): void
    {
        $this->validate();

        $data = [
            'name'         => $this->name,
            'every_km'     => $this->every_km ?: null,
            'every_months' => $this->every_months ?: null,
        ];

        if ($this->ruleId) {
            MaintenanceRule::findOrFail($this->ruleId)->update($data);
            session()->flash('success', 'Regola aggiornata.');
        } else {
            MaintenanceRule::create($data);
            session()->flash('success', 'Regola creata.');
        }

        $this->showModal = false;
        $this->reset(['ruleId', 'name', 'every_km', 'every_months']);
    }

    public function toggleAttivo(int $id): void
    {
        $rule = MaintenanceRule::findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function elimina(int $id): void
    {
        MaintenanceRule::findOrFail($id)->delete();
        session()->flash('success', 'Regola eliminata.');
    }

    public function render()
    {
        return view('livewire.impostazioni.gestione-manutenzioni', [
            'rules' => MaintenanceRule::orderBy('name')->get(),
        ]);
    }
}
