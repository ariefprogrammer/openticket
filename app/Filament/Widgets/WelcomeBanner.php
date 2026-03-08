<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WelcomeBanner extends Widget
{
    // Arahkan ke file blade yang kita buat tadi
    protected static string $view = 'filament.widgets.welcome-banner';

    // Agar muncul di bawah stats, berikan angka sort lebih besar dari TicketOverview
    protected static ?int $sort = 2;

    // Supaya lebarnya penuh satu baris
    protected int | string | array $columnSpan = 'full';
}
