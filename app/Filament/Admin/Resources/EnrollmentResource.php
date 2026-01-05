<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Enrollment\Models\Enrollment;
use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\EnrollmentResource\Pages;
use App\Filament\Admin\Resources\EnrollmentResource\RelationManagers;
use App\Filament\Concerns\HasTableExports;
use App\Support\Helpers\MultilingualHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EnrollmentResource extends Resource
{
    use HasTableExports;
    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'enrollment';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('navigation.enrollments');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.enrollment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.enrollments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.enrollment');
    }

    /**
     * Resolve CoursePrice and update form fields
     *
     * @param Forms\Set $set
     * @param Forms\Get $get
     * @return void
     */
    protected static function resolveAndUpdatePrice(Forms\Set $set, Forms\Get $get): void
    {
        $courseId = $get('course_id');
        $branchId = $get('branch_id');
        $registrationType = $get('registration_type') ?? 'online';

        if (!$courseId) {
            $set('total_amount', 0);
            $set('_allow_installments', false);
            $set('_min_down_payment', null);
            $set('_max_installments', null);
            return;
        }

        // For onsite courses, branch_id is required before resolving price
        if ($registrationType === 'onsite' && !$branchId) {
            // Don't resolve price yet - wait for branch selection
            $set('total_amount', 0);
            $set('_allow_installments', false);
            $set('_min_down_payment', null);
            $set('_max_installments', null);
            return;
        }

        $pricingService = app(\App\Services\PricingService::class);
        $coursePrice = $pricingService->resolveCoursePrice($courseId, $branchId, $registrationType);

        if ($coursePrice) {
            $set('total_amount', (float) $coursePrice->price);
            $allowInstallments = (bool) $coursePrice->allow_installments;
            $set('_allow_installments', $allowInstallments);
            $set('_min_down_payment', $coursePrice->min_down_payment ? (float) $coursePrice->min_down_payment : null);
            $set('_max_installments', $coursePrice->max_installments);

            // Reset pricing_type to 'full' if installment is selected but not allowed
            if ($get('pricing_type') === 'installment' && !$allowInstallments) {
                $set('pricing_type', 'full');
            }
        } else {
            $set('total_amount', 0);
            $set('_allow_installments', false);
            $set('_min_down_payment', null);
            $set('_max_installments', null);
            // Reset to full if price not found
            if ($get('pricing_type') === 'installment') {
                $set('pricing_type', 'full');
            }
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('enrollments.basic_information') ?? 'Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->disabled()
                            ->dehydrated()
                            ->label(__('enrollments.reference'))
                            ->visible(fn ($record) => $record !== null),
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label(__('enrollments.student')),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->label(__('enrollments.user')),
                        Forms\Components\Select::make('course_id')
                            ->relationship(
                                name: 'course',
                                titleAttribute: 'code',
                                modifyQueryUsing: function (Builder $query) {
                                    $user = auth()->user();
                                    $query = $query->where('is_active', true);
                                    if (!$user->isSuperAdmin()) {
                                        $query->where('branch_id', $user->branch_id);
                                    }
                                    return $query;
                                },
                            )
                            ->getOptionLabelUsing(function ($value) {
                                $course = \App\Domain\Training\Models\Course::find($value);
                                if (!$course) return '';
                                $code = $course->code ?? '';
                                $name = is_array($course->name) ? ($course->name[app()->getLocale()] ?? $course->name['ar'] ?? '') : $course->name;
                                return $code . ' - ' . $name;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                // When course changes, reset related fields and set registration_type
                                if ($state) {
                                    $course = \App\Domain\Training\Models\Course::find($state);
                                    if ($course) {
                                        // Set registration_type based on course delivery_type
                                        $registrationType = match ($course->delivery_type) {
                                            \App\Domain\Training\Enums\DeliveryType::Onsite => 'onsite',
                                            \App\Domain\Training\Enums\DeliveryType::Online => 'online',
                                            \App\Domain\Training\Enums\DeliveryType::Virtual => 'online',
                                            \App\Domain\Training\Enums\DeliveryType::Hybrid => 'online', // Default, can be changed
                                            default => 'online',
                                        };
                                        $set('registration_type', $registrationType);

                                        // Clear branch_id if switching from onsite to online
                                        if ($registrationType === 'online') {
                                            $set('branch_id', null);
                                            // For online courses, resolve price immediately
                                            self::resolveAndUpdatePrice($set, $get);
                                        } else {
                                            // For onsite courses, clear branch and pricing fields
                                            // Price will be resolved when branch is selected
                                            $set('branch_id', null);
                                            $set('total_amount', 0);
                                            $set('_allow_installments', false);
                                            $set('_min_down_payment', null);
                                            $set('_max_installments', null);
                                        }
                                    }
                                } else {
                                    $set('registration_type', null);
                                    $set('branch_id', null);
                                    $set('total_amount', 0);
                                    $set('_allow_installments', false);
                                    $set('_min_down_payment', null);
                                    $set('_max_installments', null);
                                }
                            })
                            ->label(__('enrollments.course')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('enrollments.pricing_registration') ?? 'Pricing & Registration')
                    ->schema([
                        Forms\Components\Select::make('registration_type')
                            ->options([
                                'onsite' => __('enrollments.registration_type_options.onsite') ?? 'Onsite',
                                'online' => __('enrollments.registration_type_options.online') ?? 'Online',
                            ])
                            ->default('online')
                            ->required()
                            ->reactive()
                            ->visible(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                if (!$courseId) {
                                    return false;
                                }
                                $course = \App\Domain\Training\Models\Course::find($courseId);
                                if (!$course) {
                                    return false;
                                }
                                // Always show registration type field, but it may be auto-set for non-hybrid courses
                                return true;
                            })
                            ->disabled(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                if (!$courseId) {
                                    return false;
                                }
                                $course = \App\Domain\Training\Models\Course::find($courseId);
                                if (!$course) {
                                    return false;
                                }
                                // Disable for non-hybrid courses (auto-set, not user-selectable)
                                return $course->delivery_type !== \App\Domain\Training\Enums\DeliveryType::Hybrid;
                            })
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $registrationType = $get('registration_type');

                                // Clear branch_id and pricing when switching registration type
                                if ($registrationType === 'online') {
                                    $set('branch_id', null);
                                    // For online, resolve price immediately (branch optional)
                                    self::resolveAndUpdatePrice($set, $get);
                                } else {
                                    // For onsite, clear branch and wait for branch selection
                                    $set('branch_id', null);
                                    $set('total_amount', 0);
                                    $set('_allow_installments', false);
                                    $set('_min_down_payment', null);
                                    $set('_max_installments', null);
                                }
                            })
                            ->label(__('enrollments.registration_type') ?? 'Registration Type')
                            ->helperText(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                if (!$courseId) {
                                    return null;
                                }
                                $course = \App\Domain\Training\Models\Course::find($courseId);
                                if (!$course) {
                                    return null;
                                }

                                // Show helper text for non-hybrid courses
                                if ($course->delivery_type !== \App\Domain\Training\Enums\DeliveryType::Hybrid) {
                                    $type = match($course->delivery_type) {
                                        \App\Domain\Training\Enums\DeliveryType::Onsite => __('enrollments.registration_type_options.onsite') ?? 'Onsite',
                                        \App\Domain\Training\Enums\DeliveryType::Online,
                                        \App\Domain\Training\Enums\DeliveryType::Virtual => __('enrollments.registration_type_options.online') ?? 'Online',
                                        default => 'Online',
                                    };
                                    return __('enrollments.auto_set_registration_type') ?? "Auto-set to: {$type} (based on course delivery type)";
                                }

                                return __('enrollments.select_registration_type_helper') ?? 'Choose whether this enrollment is for onsite or online delivery.';
                            }),
                        Forms\Components\Select::make('branch_id')
                            ->relationship(
                                name: 'branch',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                                    $courseId = $get('course_id');
                                    $registrationType = $get('registration_type') ?? 'online';

                                    // Filter branches based on course and registration type
                                    if ($courseId) {
                                        // Map registration_type to delivery_type for filtering
                                        $deliveryTypeEnum = match($registrationType) {
                                            'onsite' => \App\Domain\Training\Enums\DeliveryType::Onsite,
                                            'online' => null, // online/virtual - will handle separately
                                            default => null,
                                        };

                                        if ($registrationType === 'onsite' && $deliveryTypeEnum) {
                                            // For onsite: only show branches that have prices for this course+delivery_type
                                            $availableBranchIds = \App\Domain\Training\Models\CoursePrice::where('course_id', $courseId)
                                                ->where('delivery_type', $deliveryTypeEnum)
                                                ->where('is_active', true)
                                                ->whereNotNull('branch_id')
                                                ->pluck('branch_id')
                                                ->unique()
                                                ->toArray();

                                            if (!empty($availableBranchIds)) {
                                                $query->whereIn('id', $availableBranchIds);
                                            } else {
                                                // No prices found for onsite - show empty (validation will catch this)
                                                $query->whereRaw('1 = 0');
                                            }
                                        } elseif ($registrationType === 'online') {
                                            // For online: show branches that have prices (branch-specific or global)
                                            // But we don't restrict here - let user choose, price resolution will handle fallback
                                            $availableBranchIds = \App\Domain\Training\Models\CoursePrice::where('course_id', $courseId)
                                                ->whereIn('delivery_type', [
                                                    \App\Domain\Training\Enums\DeliveryType::Online,
                                                    \App\Domain\Training\Enums\DeliveryType::Virtual
                                                ])
                                                ->where('is_active', true)
                                                ->whereNotNull('branch_id')
                                                ->pluck('branch_id')
                                                ->unique()
                                                ->toArray();

                                            // For online, we can show all branches, but optionally filter to ones with prices
                                            // For now, show all branches for online (user can select, price will resolve)
                                        }
                                    }

                                    // Apply user branch restriction if not super admin
                                    $user = auth()->user();
                                    if (!$user->isSuperAdmin()) {
                                        $query->where('id', $user->branch_id);
                                    }

                                    return $query;
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->required(function (Forms\Get $get) {
                                // Required when registration_type is 'onsite'
                                return $get('registration_type') === 'onsite';
                            })
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                // For onsite courses, resolve price when branch is selected
                                // For online courses, resolve price (branch is optional)
                                self::resolveAndUpdatePrice($set, $get);
                            })
                            ->visible(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                if (!$courseId) {
                                    return false;
                                }

                                $registrationType = $get('registration_type') ?? 'online';
                                $isSuperAdmin = auth()->user()->isSuperAdmin();

                                // Always visible for super admin
                                // For onsite: always visible and required
                                // For online: visible for super admin, or if course has branch-specific prices
                                if ($isSuperAdmin) {
                                    return true;
                                }

                                if ($registrationType === 'onsite') {
                                    return true; // Required for onsite
                                }

                                // For online: show if user has a branch (non-super admin)
                                return true; // Show for all cases, but make it optional for online
                            })
                            ->label(__('enrollments.branch'))
                            ->helperText(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                $registrationType = $get('registration_type') ?? 'online';

                                if (!$courseId) {
                                    return null;
                                }

                                if ($registrationType === 'onsite') {
                                    return __('enrollments.branch_required_onsite') ?? 'Branch selection is required for onsite enrollment. Only branches with available pricing for this course are shown.';
                                }

                                if ($registrationType === 'online') {
                                    return __('enrollments.branch_optional_online') ?? 'Branch selection is optional for online enrollment. If not selected, global pricing will be used.';
                                }

                                return null;
                            })
                            ->placeholder(function (Forms\Get $get) {
                                $registrationType = $get('registration_type') ?? 'online';
                                if ($registrationType === 'onsite') {
                                    return __('enrollments.select_branch_for_pricing') ?? 'Select branch to see pricing';
                                }
                                return __('enrollments.select_branch_optional') ?? 'Select branch (optional)';
                            }),
                        Forms\Components\Select::make('pricing_type')
                            ->options([
                                'full' => __('enrollments.pricing_type_options.full'),
                                'installment' => __('enrollments.pricing_type_options.installment'),
                            ])
                            ->default('full')
                            ->required()
                            ->reactive()
                            ->disabled(function (Forms\Get $get) {
                                // Disable installment option if not allowed
                                return $get('_allow_installments') === false;
                            })
                            ->label(__('enrollments.pricing_type')),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->label(__('enrollments.total_amount'))
                            ->helperText(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                $branchId = $get('branch_id');
                                $registrationType = $get('registration_type') ?? 'online';

                                if (!$courseId) {
                                    return null;
                                }

                                $pricingService = app(\App\Services\PricingService::class);
                                $coursePrice = $pricingService->resolveCoursePrice($courseId, $branchId, $registrationType);

                                if (!$coursePrice) {
                                    // Map registration_type to delivery_type for display
                                    $deliveryTypeValues = match ($registrationType) {
                                        'onsite' => ['onsite'],
                                        'online' => ['online', 'virtual'],
                                        default => [],
                                    };
                                    $deliveryTypesStr = implode(' or ', $deliveryTypeValues);

                                    $message = sprintf(
                                        'No active course price found. Searched: course_id=%d, branch_id=%s, registration_type=%s, delivery_type=%s',
                                        $courseId,
                                        $branchId ? $branchId : 'null',
                                        $registrationType,
                                        $deliveryTypesStr
                                    );

                                    return $message;
                                }

                                return null;
                            }),
                        Forms\Components\Hidden::make('_allow_installments')
                            ->default(false)
                            ->dehydrated(false),
                        Forms\Components\Hidden::make('_min_down_payment')
                            ->dehydrated(false),
                        Forms\Components\Hidden::make('_max_installments')
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make(__('enrollments.price_summary') ?? 'Price Summary')
                    ->schema([
                        Forms\Components\Placeholder::make('price_info')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                $branchId = $get('branch_id');
                                $registrationType = $get('registration_type') ?? 'online';

                                if (!$courseId) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500">Select a course to see pricing information.</p>');
                                }

                                // For onsite, branch is required
                                if ($registrationType === 'onsite' && !$branchId) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-yellow-600">Please select a branch to see pricing information for onsite enrollment.</p>');
                                }

                                $pricingService = app(\App\Services\PricingService::class);
                                $coursePrice = $pricingService->resolveCoursePrice($courseId, $branchId, $registrationType);

                                if (!$coursePrice) {
                                    // Map registration_type to delivery_type for display
                                    $deliveryTypeValues = match ($registrationType) {
                                        'onsite' => ['onsite'],
                                        'online' => ['online', 'virtual'],
                                        default => [],
                                    };
                                    $deliveryTypesStr = implode(' or ', $deliveryTypeValues);

                                    $message = sprintf(
                                        'No active course price found. Searched: course_id=%d, branch_id=%s, registration_type=%s, delivery_type=%s. Please configure pricing.',
                                        $courseId,
                                        $branchId ? $branchId : 'null',
                                        $registrationType,
                                        $deliveryTypesStr
                                    );

                                    return new \Illuminate\Support\HtmlString('<p class="text-red-600">' . htmlspecialchars($message) . '</p>');
                                }

                                $html = '<div class="space-y-2">';
                                $html .= '<div class="grid grid-cols-2 gap-4">';
                                $html .= '<div><strong>Price:</strong> ' . number_format((float) $coursePrice->price, 2) . ' SAR</div>';
                                $html .= '<div><strong>Allow Installments:</strong> ' . ($coursePrice->allow_installments ? 'Yes' : 'No') . '</div>';

                                if ($coursePrice->allow_installments) {
                                    if ($coursePrice->min_down_payment) {
                                        $html .= '<div><strong>Min Down Payment:</strong> ' . number_format((float) $coursePrice->min_down_payment, 2) . ' SAR</div>';
                                    }
                                    if ($coursePrice->max_installments) {
                                        $html .= '<div><strong>Max Installments:</strong> ' . $coursePrice->max_installments . '</div>';
                                    }
                                }

                                $html .= '</div>';
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('course_id'))
                    ->collapsible()
                    ->collapsed(false),

                Forms\Components\Section::make(__('enrollments.enrollment_details') ?? 'Enrollment Details')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => __('enrollments.status_options.pending'),
                                'active' => __('enrollments.status_options.active'),
                                'completed' => __('enrollments.status_options.completed'),
                                'cancelled' => __('enrollments.status_options.cancelled'),
                            ])
                            ->default('pending')
                            ->required()
                            ->label(__('enrollments.status')),
                        Forms\Components\TextInput::make('progress_percent')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->label(__('enrollments.progress_percent')),
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label(__('enrollments.started_at')),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label(__('enrollments.completed_at')),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->where('branch_id', $user->branch_id)
                        ->where('user_id', $user->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->label(__('enrollments.reference')),
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('enrollments.student')),
                Tables\Columns\TextColumn::make('course.name')
                    ->formatStateUsing(fn ($state) => MultilingualHelper::formatMultilingualField($state))
                    ->searchable()
                    ->sortable()
                    ->label(__('enrollments.course')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('SAR')
                    ->sortable()
                    ->label(__('enrollments.total_amount')),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('SAR')
                    ->default(function ($record) {
                        return $record->payments()->where('status', 'paid')->sum('amount');
                    })
                    ->label(__('enrollments.paid_amount')),
                Tables\Columns\TextColumn::make('due_amount')
                    ->money('SAR')
                    ->default(function ($record) {
                        $paid = $record->payments()->where('status', 'paid')->sum('amount');
                        return $record->total_amount - $paid;
                    })
                    ->label(__('enrollments.due_amount')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('enrollments.status_options.' . $state->value))
                    ->color(fn ($state) => match($state) {
                        EnrollmentStatus::ACTIVE => 'success',
                        EnrollmentStatus::COMPLETED => 'info',
                        EnrollmentStatus::CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->label(__('enrollments.status')),
                Tables\Columns\TextColumn::make('progress_percent')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                    ->sortable()
                    ->label(__('enrollments.progress_percent')),
                Tables\Columns\TextColumn::make('pricing_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('enrollments.pricing_type_options.' . $state))
                    ->label(__('enrollments.pricing_type')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->label(__('filters.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->label(__('filters.user')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('enrollments.status_options.pending'),
                        'active' => __('enrollments.status_options.active'),
                        'completed' => __('enrollments.status_options.completed'),
                        'cancelled' => __('enrollments.status_options.cancelled'),
                    ])
                    ->label(__('enrollments.status')),
                Tables\Filters\SelectFilter::make('pricing_type')
                    ->options([
                        'full' => __('enrollments.pricing_type_options.full'),
                        'installment' => __('enrollments.pricing_type_options.installment'),
                    ])
                    ->label(__('enrollments.pricing_type')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print')
                    ->label(__('exports.print'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Enrollment $record) => route('enrollments.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('qr_code')
                    ->label(__('enrollments.qr_code'))
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->modalHeading(__('enrollments.qr_code'))
                    ->modalContent(function (Enrollment $record) {
                        $publicUrl = route('public.enrollment.show', ['reference' => $record->reference]);
                        $qrCodeService = app(\App\Services\QrCodeService::class);
                        $qrCodeSvg = $qrCodeService->generateSvg($publicUrl);

                        return new \Illuminate\Support\HtmlString('
                            <div class="p-4">
                                <div class="flex flex-col items-center space-y-4">
                                    <div class="bg-white p-4 rounded-lg border">
                                        ' . $qrCodeSvg . '
                                    </div>
                                    <div class="text-center w-full">
                                        <p class="text-sm font-medium mb-2">' . __('enrollments.public_link') . '</p>
                                        <div class="flex items-center space-x-2">
                                            <input type="text"
                                                   value="' . htmlspecialchars($publicUrl) . '"
                                                   readonly
                                                   class="flex-1 px-3 py-2 border rounded-md text-sm"
                                                   id="public-url-' . $record->reference . '">
                                            <button onclick="navigator.clipboard.writeText(\'' . htmlspecialchars($publicUrl, ENT_QUOTES) . '\').then(() => alert(\'' . htmlspecialchars(__('enrollments.copied'), ENT_QUOTES) . '\'))"
                                                    class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 text-sm">
                                                ' . __('enrollments.copy') . '
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ');
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('enrollments.close')),
                Tables\Actions\Action::make('record_payment')
                    ->label(__('enrollments.actions.record_payment'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('installment_id')
                            ->options(function (Enrollment $record) {
                                if (!$record->arInvoice) {
                                    return [];
                                }
                                return $record->arInvoice->arInstallments()
                                    ->where('status', '!=', 'paid')
                                    ->get()
                                    ->mapWithKeys(function ($installment) {
                                        return [$installment->id => 'Installment #' . $installment->installment_no . ' - ' . number_format($installment->amount, 2) . ' SAR (Due: ' . $installment->due_date->format('Y-m-d') . ')'];
                                    });
                            })
                            ->label(__('payments.installment'))
                            ->placeholder(__('payments.select_installment'))
                            ->searchable(),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->label(__('payments.amount')),
                        Forms\Components\Select::make('method')
                            ->options([
                                'cash' => __('payments.method_options.cash'),
                                'bank' => __('payments.method_options.bank'),
                                'gateway' => __('payments.method_options.gateway'),
                            ])
                            ->default('cash')
                            ->required()
                            ->label(__('payments.method')),
                        Forms\Components\TextInput::make('gateway_ref')
                            ->label(__('payments.gateway_ref')),
                    ])
                    ->action(function (Enrollment $record, array $data) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            // Load relationships
                            $record->loadMissing(['student', 'user']);

                            // Determine user_id with explicit checks
                            $userId = null;

                            // Try enrollment user_id first
                            if (!empty($record->user_id)) {
                                $userId = $record->user_id;
                            }
                            // Try student's user_id
                            elseif ($record->student && !empty($record->student->user_id)) {
                                $userId = $record->student->user_id;
                            }
                            // Fallback to current user
                            else {
                                $userId = auth()->id();
                            }

                            // Final safety check - must have a user_id
                            if (empty($userId)) {
                                throw new \Exception('Unable to determine user for payment. Please ensure enrollment has a user or student has a user.');
                            }

                            // Determine branch_id
                            $branchId = $record->branch_id ?? auth()->user()->branch_id ?? null;

                            // Prepare payment data - ensure all required fields are set
                            $paymentData = [
                                'enrollment_id' => $record->id,
                                'user_id' => $userId, // This MUST be set
                                'branch_id' => $branchId,
                                'installment_id' => $data['installment_id'] ?? null,
                                'amount' => $data['amount'],
                                'method' => $data['method'],
                                'gateway_ref' => $data['gateway_ref'] ?? null,
                                'status' => $data['status'] ?? 'paid',
                                'paid_at' => ($data['status'] ?? 'paid') === 'paid' ? now() : null,
                                'created_by' => auth()->id(),
                            ];

                            // Final validation - user_id must be set
                            if (empty($paymentData['user_id'])) {
                                throw new \Exception('Payment cannot be created: user_id is required but was not determined.');
                            }

                            $payment = \App\Domain\Accounting\Models\Payment::create($paymentData);

                            event(new \App\Domain\Accounting\Events\PaymentPaid($payment));
                        });
                    }),
                Tables\Actions\Action::make('mark_completed')
                    ->label(__('enrollments.actions.mark_completed'))
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (Enrollment $record) {
                        DB::transaction(function () use ($record) {
                            $record->update([
                                'status' => EnrollmentStatus::COMPLETED,
                                'completed_at' => now(),
                                'progress_percent' => 100,
                            ]);

                            event(new \App\Domain\Enrollment\Events\EnrollmentCompleted($record));
                        });
                    })
                    ->visible(fn ($record) => $record->status !== EnrollmentStatus::COMPLETED),
            ])
            ->headerActions(static::getExportActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ArInvoiceRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
            RelationManagers\PdfInvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'view' => Pages\ViewEnrollment::route('/{record}'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}
