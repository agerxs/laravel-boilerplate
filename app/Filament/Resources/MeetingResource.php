<?php

namespace App\Filament\Resources;

use App\Models\Meeting;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\MeetingResource\Pages;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationGroup = 'Gestion des réunions';
    
    protected static ?string $modelLabel = 'Réunion';
    
    protected static ?string $pluralModelLabel = 'Réunions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Titre')
                    ->required(),
                    
                Select::make('local_committee_id')
                    ->label('Comité local')
                    ->relationship('localCommittee', 'id')
                    ->searchable()
                    ->preload()
                    ->required(),
                    
                DatePicker::make('scheduled_date')
                    ->label('Date prévue')
                    ->required(),
                    
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'scheduled' => 'Planifiée',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée'
                    ])
                    ->required()
                    ->default('scheduled'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('localCommittee.locality.name')
                    ->label('Sous-préfecture')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('scheduled_date')
                    ->label('Date prévue')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                TextColumn::make('status')
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
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'scheduled' => 'Planifiée',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée'
                    ]),
                    
                SelectFilter::make('local_committee')
                    ->label('Comité local')
                    ->relationship('localCommittee.locality', 'name'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetings::route('/'),
            'create' => Pages\CreateMeeting::route('/create'),
            'edit' => Pages\EditMeeting::route('/{record}/edit'),
        ];
    }
} 