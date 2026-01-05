<?php

namespace App\Filament\Admin\Resources\LessonResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LessonDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->emptyStateHeading('No documents available yet')
            ->emptyStateDescription('Documents will be managed here in a future phase.')
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

