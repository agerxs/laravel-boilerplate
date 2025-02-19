<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Models\Locality;
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
use App\Filament\Resources\SubPrefectResource\Pages;
use Illuminate\Support\Collection;

class SubPrefectResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Gestion des utilisateurs';
    
    protected static ?string $modelLabel = 'Sous-préfet';
    
    protected static ?string $pluralModelLabel = 'Sous-préfets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nom et Prénom')
                    ->required(),
                    
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                    
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel()
                    ->required()
                    ->unique(ignoreRecord: true),
                    
                TextInput::make('whatsapp')
                    ->label('WhatsApp')
                    ->tel(),

                // Sélection en cascade pour les localités
                Select::make('region_id')
                    ->label('Région')
                    ->options(function () {
                        return Locality::whereHas('type', fn ($query) => 
                            $query->where('name', 'region')
                        )->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null)),

                Select::make('department_id')
                    ->label('Département')
                    ->options(function (callable $get) {
                        $regionId = $get('region_id');
                        if (!$regionId) return Collection::make();
                        
                        return Locality::whereHas('type', fn ($query) => 
                            $query->where('name', 'department')
                        )
                        ->where('parent_id', $regionId)
                        ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn (callable $set) => $set('locality_id', null))
                    ->visible(fn (callable $get) => filled($get('region_id'))),

                Select::make('locality_id')
                    ->label('Sous-préfecture')
                    ->options(function (callable $get) {
                        $departmentId = $get('department_id');
                        if (!$departmentId) return Collection::make();
                        
                        return Locality::whereHas('type', fn ($query) => 
                            $query->where('name', 'sub_prefecture')
                        )
                        ->where('parent_id', $departmentId)
                        ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn (callable $get) => filled($get('department_id')))
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $locality = Locality::find($state);
                            if ($locality) {
                                $set('region_id', $locality->parent->parent->id);
                                $set('department_id', $locality->parent->id);
                            }
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom et Prénom')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                    
                TextColumn::make('locality.parent.parent.name')
                    ->label('Région')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('locality.parent.name')
                    ->label('Département')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('locality.name')
                    ->label('Sous-préfecture')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('region')
                    ->label('Région')
                    ->options(
                        Locality::whereHas('type', fn ($query) => 
                            $query->where('name', 'region')
                        )->pluck('name', 'id')
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $regionId): Builder =>
                                $query->whereHas('locality.parent.parent', fn ($q) => 
                                    $q->where('id', $regionId)
                                )
                        );
                    }),

                SelectFilter::make('department')
                    ->label('Département')
                    ->options(
                        Locality::whereHas('type', fn ($query) => 
                            $query->where('name', 'department')
                        )->pluck('name', 'id')
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $departmentId): Builder =>
                                $query->whereHas('locality.parent', fn ($q) => 
                                    $q->where('id', $departmentId)
                                )
                        );
                    }),
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
            'index' => Pages\ListSubPrefects::route('/'),
            'create' => Pages\CreateSubPrefect::route('/create'),
            'edit' => Pages\EditSubPrefect::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->role('sous-prefet');
    }
} 