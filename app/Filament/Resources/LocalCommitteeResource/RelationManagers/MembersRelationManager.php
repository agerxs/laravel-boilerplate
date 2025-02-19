<?php

namespace App\Filament\Resources\LocalCommitteeResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Membres';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('role')
                    ->options([
                        'president' => 'Président',
                        'vice_president' => 'Vice-président',
                        'treasurer' => 'Trésorier',
                        'secretary' => 'Secrétaire',
                        'member' => 'Membre'
                    ])
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Actif',
                        'inactive' => 'Inactif'
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('member_name')
                    ->label('Nom')
                    ->formatStateUsing(function ($record) {
                        if ($record->user_id) {
                            return $record->user->name;
                        }
                        return $record->first_name . ' ' . $record->last_name;
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query
                            ->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rôle')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'president' => 'Président',
                        'vice_president' => 'Vice-président',
                        'treasurer' => 'Trésorier',
                        'secretary' => 'Secrétaire',
                        'member' => 'Membre',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Actif',
                        'inactive' => 'Inactif',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'president' => 'Président',
                        'vice_president' => 'Vice-président',
                        'treasurer' => 'Trésorier',
                        'secretary' => 'Secrétaire',
                        'member' => 'Membre'
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Actif',
                        'inactive' => 'Inactif'
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Utilisateur')
                            ->required(),

                        Forms\Components\Select::make('role')
                            ->options([
                                'president' => 'Président',
                                'vice_president' => 'Vice-président',
                                'treasurer' => 'Trésorier',
                                'secretary' => 'Secrétaire',
                                'member' => 'Membre'
                            ])
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Actif',
                                'inactive' => 'Inactif'
                            ])
                            ->default('active')
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Utilisateur')
                            ->required(),

                        Forms\Components\Select::make('role')
                            ->options([
                                'president' => 'Président',
                                'vice_president' => 'Vice-président',
                                'treasurer' => 'Trésorier',
                                'secretary' => 'Secrétaire',
                                'member' => 'Membre'
                            ])
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Actif',
                                'inactive' => 'Inactif'
                            ])
                            ->required(),
                    ]),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 