<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TicketOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Base Query: User hanya melihat tiket miliknya, Admin melihat semua
        $query = Ticket::query();
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        return [
            Stat::make('Total Tiket', (clone $query)->count())
                ->description('Semua tiket Anda')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('info'),

            Stat::make('Pending / Belum Bayar', (clone $query)->whereIn('status', ['open', 'pending_payment'])->count())
                ->description('Menunggu tindakan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Dalam Proses', (clone $query)->where('status', 'in_progress')->count())
                ->description('Sedang dikerjakan')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('primary'),

            Stat::make('Selesai', (clone $query)->where('status', 'closed')->count())
                ->description('Tiket ditutup')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
