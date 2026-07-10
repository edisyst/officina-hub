<table class="table table-sm mb-0">
    <tbody>
        @forelse($activities as $activity)
        <tr>
            <td>{{ $feedService->humanize($activity) }}</td>
            <td class="text-right text-muted" style="white-space:nowrap">
                {{ $activity->created_at->diffForHumans() }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="2" class="text-center text-muted py-2">Nessuna attività recente.</td>
        </tr>
        @endforelse
    </tbody>
</table>
