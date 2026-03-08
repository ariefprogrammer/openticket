<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\DokuService;
use App\Models\Payment;
use Filament\Notifications\Notification;
use Filament\Forms\Components\RichEditor;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kendala')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Kendala')
                            ->required()
                            ->disabled(fn () => auth()->user()->role !== 'client'),
                            // ->disabled(fn ($record) => $record !== null),
                        
                        Forms\Components\Select::make('priority')
                            ->label('Prioritas')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                            ])
                            ->required()
                            ->disabled(fn () => auth()->user()->role !== 'client'),
                            // ->disabled(fn ($record) => $record !== null),

                        RichEditor::make('description')
                            ->required()
                            ->columnSpanFull()
                            ->disabled(fn () => auth()->user()->role !== 'client')
                            ->label('Detail Kendala')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('ticket-attachments')
                            ->fileAttachmentsVisibility('public')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link',
                                'h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd',
                                'blockquote', 'codeBlock', 'bulletList', 'orderedList',
                                'table', 'attachFiles',
                                'undo', 'redo'

                            ])
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Admin Review & Status')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Nominal Pembayaran')
                            ->numeric()
                            ->prefix('IDR')
                            // Hanya admin yang bisa mengisi/mengubah
                            ->disabled(fn () => auth()->user()->role !== 'admin')
                            // Tetap kirim data ke database meskipun field di-disable (penting untuk admin)
                            ->dehydrated(fn () => auth()->user()->role === 'admin'), 

                        Forms\Components\Select::make('status')
                            ->label('Status Tiket')
                            ->options([
                                'open' => 'Open',
                                'pending_payment' => 'Menunggu Pembayaran',
                                'in_progress' => 'Sedang Diproses',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('open')
                            ->required()
                            // Hanya admin yang bisa mengubah status
                            ->disabled(fn () => auth()->user()->role !== 'admin')
                            ->dehydrated(fn () => auth()->user()->role === 'admin'),

                        Forms\Components\Textarea::make('admin_note')
                            ->label('Catatan Feedback')
                            ->columnSpanFull()
                            // Hanya admin yang bisa mengisi catatan
                            ->disabled(fn () => auth()->user()->role !== 'admin')
                            ->dehydrated(fn () => auth()->user()->role === 'admin'),
                    ])
                    ->columns(2)
                    // Opsional: Sembunyikan seluruh section ini jika tiket masih baru (saat Create)
                    // agar klien tidak bingung melihat field kosong yang terkunci.
                    ->visible(fn ($livewire) => !($livewire instanceof \Filament\Resources\Pages\CreateRecord)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Klien')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'open',
                        'warning' => 'pending_payment',
                        'success' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Biaya')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'pending_payment' => 'Pending Payment',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // Tables\Actions\Action::make('pay')
                //     ->label('Bayar Sekarang')
                //     ->color('success')
                //     ->icon('heroicon-o-credit-card')
                //     ->visible(fn ($record) => $record->status === 'pending_payment' && $record->amount > 0)
                //     ->action(function ($record, DokuService $dokuService) {
                //         $invoice = 'INV-' . time();
                        
                //         $response = $dokuService->createCheckout([
                //             'amount' => $record->amount,
                //             'invoice' => $invoice,
                //             'customer_name' => $record->user->name,
                //             'customer_email' => $record->user->email,
                //         ]);

                //         if (isset($response['response']['payment']['url'])) {
                //             Payment::create([
                //                 'ticket_id' => $record->id,
                //                 'external_id' => $invoice,
                //                 'amount' => $record->amount,
                //                 'status' => 'pending',
                //                 'checkout_url' => $response['response']['payment']['url'],
                //             ]);

                //             return redirect()->away($response['response']['payment']['url']);
                //         }

                //         Notification::make()->title('Gagal terhubung ke DOKU')->danger()->send();
                //     }),
                Tables\Actions\Action::make('pay')
                    ->label('Bayar Sekarang')
                    ->color('success')
                    ->icon('heroicon-o-credit-card')
                    // Tombol hanya muncul jika status pending_payment dan ada harga
                    ->visible(fn ($record) => $record->status === 'pending_payment' && $record->amount > 0)
                    
                    // Menampilkan Modal Konfirmasi
                    ->requiresConfirmation()
                    ->modalHeading('Instruksi Pembayaran')
                    ->modalDescription('Automatic payment belum siap. Untuk melakukan pembayaran, silahkan melakukan transfer ke beberapa pilihan channel pembayaran berikut :')
                    ->modalContent(view('components.payment-instruction')) // Menggunakan view agar teks lebih rapi
                    ->modalSubmitActionLabel('Saya Sudah Transfer')
                    
                    // Action ini akan dijalankan saat user menekan tombol submit di modal
                    ->action(function ($record) {
                        // Anda bisa memberikan notifikasi atau sekadar menutup modal
                        Notification::make()
                            ->title('Permintaan Terkirim')
                            ->body('Silahkan kirim bukti transfer ke Admin via WhatsApp untuk proses verifikasi.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Jika bukan admin, hanya tampilkan tiket milik sendiri
        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', auth()->id());
        }
        
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
