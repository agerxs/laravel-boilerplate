<?php

namespace App\Filament\Resources;

use App\Models\LocalCommittee;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\LocalCommitteeResource\Pages;
use App\Filament\Resources\LocalCommitteeResource\RelationManagers;

class LocalCommitteeResource extends Resource
{
    protected static ?string $model = LocalCommittee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Gestion des comités';
    
    protected static ?string $modelLabel = 'Comité local';
    
    protected static ?string $pluralModelLabel = 'Comités locaux';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nom du comité')
                    ->required()
                    ->maxLength(255),

                Select::make('locality_id')
                    ->label('Sous-préfecture')
                    ->relationship('locality', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => 
                        $set('name', "Comité Local de " . 
                            ($state ? \App\Models\Locality::find($state)?->name : ''))),

                Select::make('president_id')
                    ->label('Président')
                    ->relationship('president', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('villages_count')
                    ->label('Nombre de villages')
                    ->numeric()
                    ->nullable(),

                TextInput::make('population_rgph')
                    ->label('Population RGPH')
                    ->numeric()
                    ->nullable(),

                TextInput::make('population_to_enroll')
                    ->label('Population à enrôler')
                    ->numeric()
                    ->nullable(),

                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'pending' => 'En attente',
                        'inactive' => 'Inactif'
                    ])
                    ->required()
                    ->default('active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('locality.name')
                    ->label('Sous-préfecture')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('president.name')
                    ->label('Président')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('villages_count')
                    ->label('Nombre de villages')
                    ->sortable(),

                TextColumn::make('population_rgph')
                    ->label('Population RGPH')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'inactive' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Actif',
                        'pending' => 'En attente',
                        'inactive' => 'Inactif',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'pending' => 'En attente',
                        'inactive' => 'Inactif'
                    ]),

                SelectFilter::make('locality')
                    ->label('Sous-préfecture')
                    ->relationship('locality', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\MembersRelationManager::make(),
            RelationManagers\MeetingsRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocalCommittees::route('/'),
            'create' => Pages\CreateLocalCommittee::route('/create'),
            'edit' => Pages\EditLocalCommittee::route('/{record}/edit'),
            'view' => Pages\ViewLocalCommittee::route('/{record}'),
        ];
    }
} 