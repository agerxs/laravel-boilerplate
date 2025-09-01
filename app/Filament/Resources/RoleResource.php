<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Gestion des utilisateurs';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Rôles';
    protected static ?string $modelLabel = 'Rôle';
    protected static ?string $pluralModelLabel = 'Rôles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom du rôle')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('guard_name')
                    ->label('Nom du guard')
                    ->default('web')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Model $record): bool => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Model $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => static::canDeleteAny()),
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
            'index' => \App\Filament\Resources\RoleResource\Pages\ListRoles::route('/'),
            'create' => \App\Filament\Resources\RoleResource\Pages\CreateRole::route('/create'),
            'edit' => \App\Filament\Resources\RoleResource\Pages\EditRole::route('/{record}/edit'),
            'view' => \App\Filament\Resources\RoleResource\Pages\ViewRole::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    // Permissions d'accès - Seul le super admin peut modifier
    public static function canCreate(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user && $user->is_super_admin;
    }

    public static function canEdit(Model $record): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user && $user->is_super_admin;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user && $user->is_super_admin;
    }

    public static function canDeleteAny(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user && $user->is_super_admin;
    }

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user && ($user->is_super_admin || $user->hasRole('admin'));
    }

    public static function canView(Model $record): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user && ($user->is_super_admin || $user->hasRole('admin'));
    }
} 