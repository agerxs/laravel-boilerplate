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
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Utilisateurs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations personnelles')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('position')
                            ->label('Fonction')
                            ->maxLength(100),

                        Forms\Components\Select::make('department')
                            ->label('Service')
                            ->options([
                                'direction' => 'Direction',
                                'rh' => 'Ressources Humaines',
                                'finance' => 'Finance',
                                'it' => 'Informatique',
                                'marketing' => 'Marketing',
                                'operations' => 'Opérations',
                                'other' => 'Autre'
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('Adresse')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Rue')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('postal_code')
                            ->label('Code postal')
                            ->maxLength(20),
                    ])->columns(2),

                Forms\Components\Section::make('Sécurité')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),

                        Forms\Components\Select::make('roles')
                            ->label('Rôles')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('position')
                    ->label('Fonction')
                    ->searchable(),

                Tables\Columns\TextColumn::make('department')
                    ->label('Service')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'direction' => 'danger',
                        'rh' => 'warning',
                        'finance' => 'success',
                        'it' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->label('Service')
                    ->options([
                        'direction' => 'Direction',
                        'rh' => 'Ressources Humaines',
                        'finance' => 'Finance',
                        'it' => 'Informatique',
                        'marketing' => 'Marketing',
                        'operations' => 'Opérations',
                        'other' => 'Autre'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
