<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentRateResource\Pages;
use App\Models\PaymentRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class PaymentRateResource extends Resource
{
    protected static ?string $model = PaymentRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Taux de paiement';

    protected static ?string $modelLabel = 'Taux de paiement';

    protected static ?string $pluralModelLabel = 'Taux de paiement';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('role')
                    ->label('Rôle')
                    ->options([
                        'sous_prefet' => 'Président',
                        'secretaire' => 'Secrétaire',
                        'representant' => 'Représentant',
                    ])
                    ->required(),
                
                TextInput::make('meeting_rate')
                    ->label('Taux par réunion (FCFA)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpan('full'),
                
                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true)
                    ->helperText('Un seul taux peut être actif par rôle. Activer ce taux désactivera automatiquement les autres taux pour ce rôle.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('role')
                    ->label('Rôle')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sous_prefet' => 'Président',
                        'secretaire' => 'Secrétaire',
                        'representant' => 'Représentant',
                        default => $state,
                    })
                    ->sortable(),
                
                TextColumn::make('meeting_rate')
                    ->label('Taux par réunion')
                    ->money('XOF')
                    ->sortable(),
                
                ToggleColumn::make('is_active')
                    ->label('Actif'),
                
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rôle')
                    ->options([
                        'sous_prefet' => 'Président',
                        'secretaire' => 'Secrétaire',
                        'representant' => 'Représentant',
                    ]),
                
                SelectFilter::make('is_active')
                    ->label('Statut')
                    ->options([
                        '1' => 'Actif',
                        '0' => 'Inactif',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListPaymentRates::route('/'),
            'create' => Pages\CreatePaymentRate::route('/create'),
            'edit' => Pages\EditPaymentRate::route('/{record}/edit'),
        ];
    }    
} 