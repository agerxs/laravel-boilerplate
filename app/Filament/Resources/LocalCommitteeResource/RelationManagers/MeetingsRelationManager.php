<?php

namespace App\Filament\Resources\LocalCommitteeResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class MeetingsRelationManager extends RelationManager
{
    protected static string $relationship = 'meetings';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $title = 'Réunions';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_date', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('localCommittee.locality'))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('localCommittee.locality.name')
                    ->label('Sous-préfecture')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Date prévue')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'Planifiée',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Planifiée',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée'
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.meetings.view', $record)),
            ])
            ->bulkActions([]);
    }
} 