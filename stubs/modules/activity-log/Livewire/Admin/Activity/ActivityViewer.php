<?php

namespace App\Livewire\Admin\Activity;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin-sidebar')]
class ActivityViewer extends Component
{
    use WithPagination;

    #[Url(as: 'log')]
    public string $logName = '';

    #[Url(as: 'causer')]
    public string $causerEmail = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['logName', 'causerEmail', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $activityModel = config('activitylog.activity_model', \Spatie\Activitylog\Models\Activity::class);

        $query = $activityModel::query()->with('causer', 'subject')->latest();

        if ($this->logName !== '') {
            $query->where('log_name', $this->logName);
        }

        if ($this->causerEmail !== '') {
            $userModel = config('auth.providers.users.model', \App\Models\User::class);
            $causerIds = $userModel::where('email', 'like', '%'.$this->causerEmail.'%')->pluck('id');
            $query->whereIn('causer_id', $causerIds)->where('causer_type', $userModel);
        }

        if ($this->dateFrom !== '') {
            $query->where('created_at', '>=', $this->dateFrom.' 00:00:00');
        }

        if ($this->dateTo !== '') {
            $query->where('created_at', '<=', $this->dateTo.' 23:59:59');
        }

        $logNames = $activityModel::query()
            ->select('log_name')
            ->distinct()
            ->whereNotNull('log_name')
            ->pluck('log_name');

        return view('livewire.admin.activity.activity-viewer', [
            'activities' => $query->paginate(25),
            'logNames' => $logNames,
        ]);
    }
}
