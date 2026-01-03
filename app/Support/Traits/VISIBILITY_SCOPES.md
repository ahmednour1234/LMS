# Visibility Scopes Documentation

## Column Requirements

All models using `HasVisibilityScope` trait **must** have the following columns:

- `branch_id` (nullable): Foreign key to `branches` table
- `created_by` (nullable): Foreign key to `users` table

### Verified Models

- **Journal**: ✓ Has `branch_id` and `created_by`
- **Account**: ✓ Has `branch_id`, but missing `created_by` (if needed for personal visibility)
- **Payment**: Not yet created - must include both columns
- **Enrollment**: Not yet created - must include both columns
- **AR Invoice**: Not yet created - must include both columns

## Policy Integration

Policies must check permissions before scopes apply query filters. The pattern follows this flow:

### 1. Policy `viewAny()` Method

```php
public function viewAny(User $user): bool
{
    // Check base permission first
    return $user->hasPermissionTo('{resource}.view');
}
```

Policies authorize the action, but **do not filter queries**. Query filtering is handled by scopes in Filament Resources.

### 2. Policy `view()` Method for Individual Records

```php
public function view(User $user, Model $model): bool
{
    if (!$user->hasPermissionTo('{resource}.view')) {
        return false;
    }

    // Super admin or global permission = access all
    if ($user->isSuperAdmin() || $user->hasPermissionTo('{resource}.view.global')) {
        return true;
    }

    // Branch permission = check branch_id match
    if ($user->hasPermissionTo('{resource}.view.branch')) {
        return $user->branch_id === $model->branch_id;
    }

    // Personal permission = check created_by match
    if ($user->hasPermissionTo('{resource}.view.personal')) {
        return $model->created_by === $user->id;
    }

    return false;
}
```

### 3. Scope Usage in Queries

The `visibleTo()` scope checks the same permission hierarchy and applies appropriate query filters:

- **GLOBAL**: No filtering applied
- **BRANCH**: `WHERE branch_id = user->branch_id`
- **PERSONAL**: `WHERE created_by = user->id`
- **NO PERMISSION**: `WHERE 1 = 0` (empty result)

### Permission Format

- Base: `{resource}.view`
- Global: `{resource}.view.global`
- Branch: `{resource}.view.branch`
- Personal: `{resource}.view.personal`

Examples:
- `journals.view`, `journals.view.global`, `journals.view.branch`, `journals.view.personal`
- `payments.view`, `payments.view.global`, `payments.view.branch`, `payments.view.personal`

## Filament Resource Integration

Filament Resources must use visibility scopes in the table query to enforce data filtering.

### Pattern: Using `modifyQueryUsing()`

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(function (Builder $query) {
            $user = auth()->user();
            $query->visibleTo($user, 'journals');
        })
        ->columns([
            // ... columns
        ]);
}
```

### Pattern: Using `getEloquentQuery()` (Alternative)

If you need to apply scopes at the Resource level (for all queries, not just table):

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->visibleTo(auth()->user(), 'journals');
}
```

### Important Notes

- **Never put visibility logic inside Filament Resource form/table definitions**
- Scopes handle all query-level filtering
- Policies handle authorization checks for individual record access
- Filament automatically uses policies for `canView()`, `canEdit()`, `canDelete()` checks

### Complete Example: Journal Resource

```php
use App\Domain\Accounting\Models\Journal;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->visibleTo(auth()->user(), 'journals');
            })
            ->columns([
                // ... your columns
            ]);
    }
}
```

## Usage in Reports

For report queries that need visibility filtering, apply scopes directly:

```php
$journals = Journal::visibleTo($user, 'journals')
    ->where('status', 'posted')
    ->get();
```

