<?php

namespace App\Filament\Admin\Resources\LessonResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LessonTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->emptyStateHeading('No tasks available yet')
            ->emptyStateDescription('Tasks will be managed here in a future phase.')
            ->headerActions([
                // Create action disabled - will be implemented in future phase
            ])
            ->actions([
                // Edit/Delete actions disabled - will be implemented in future phase
            ])
            ->bulkActions([
                // Bulk actions disabled - will be implemented in future phase
            ]);
    }
}

