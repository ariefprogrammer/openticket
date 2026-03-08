<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'client' => 'Client',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(false),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state)) // Hanya update jika diisi
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])->columns(2)
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status Aktif')
                    ->boolean(),
            ])
            ->actions([
                // Tombol "Accept" untuk menyetujui user
                Tables\Actions\Action::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn (User $record) => $record->is_active) // Sembunyikan jika sudah aktif
                    ->action(fn (User $record) => $record->update(['is_active' => true]))
                    ->requiresConfirmation(),
                    
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
