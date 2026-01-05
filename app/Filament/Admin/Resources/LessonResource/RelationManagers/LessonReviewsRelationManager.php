<?php

namespace App\Filament\Admin\Resources\LessonResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LessonReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->emptyStateHeading('No reviews available yet')
            ->emptyStateDescription('Reviews will be managed here in a future phase.')
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

