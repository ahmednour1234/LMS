<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Services\EnrollmentPriceCalculator;
use App\Enums\EnrollmentMode;
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
     * Resolve CoursePrice and calculate price using EnrollmentPriceCalculator
     *
     * @param Forms\Set $set
     * @param Forms\Get $get
     * @return void
     */
    protected static function resolveAndUpdatePrice(Forms\Set $set, Forms\Get $get): void
    {
        $courseId = $get('course_id');
        $branchId = $get('branch_id');
        $deliveryType = $get('delivery_type') ?? 'online';
        $enrollmentMode = $get('enrollment_mode');
        $sessionsPurchased = $get('sessions_purchased');

        if (!$courseId) {
            $set('total_amount', 0);
            $set('_course_price', null);
            $set('_allowed_modes', []);
            return;
        }


        $pricingService = app(\App\Services\PricingService::class);
        $coursePrice = $pricingService->resolveCoursePrice($courseId, $branchId, $deliveryType);

        if ($coursePrice) {
            $calculator = app(EnrollmentPriceCalculator::class);
            $allowedModes = $calculator->getAllowedModes($coursePrice);
            $set('_course_price', $coursePrice->id);
            $set('_allowed_modes', array_map(fn($mode) => $mode->value, $allowedModes));
            $set('_sessions_count', $coursePrice->sessions_count);

            // Calculate price if enrollment mode is set
            if ($enrollmentMode) {
                try {
                    $enrollmentModeEnum = EnrollmentMode::from($enrollmentMode);
                    $result = $calculator->calculate($coursePrice, $enrollmentModeEnum, $sessionsPurchased);
                    $set('total_amount', $result['total_amount']);
                    $set('currency_code', $result['currency_code']);
                } catch (\Exception $e) {
                    $set('total_amount', 0);
                }
            } else {
                $set('total_amount', 0);
            }
        } else {
            $set('total_amount', 0);
            $set('_course_price', null);
            $set('_allowed_modes', []);
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
                                // When course changes, reset related fields
                                if ($state) {
                                    $course = \App\Domain\Training\Models\Course::find($state);
                                    if ($course) {
                                        // Set delivery_type based on course delivery_type
                                        $deliveryType = match ($course->delivery_type) {
                                            \App\Domain\Training\Enums\DeliveryType::Onsite => 'onsite',
                                            \App\Domain\Training\Enums\DeliveryType::Online => 'online',
                                            \App\Domain\Training\Enums\DeliveryType::Hybrid => 'online', // Default, can be changed
                                            default => 'online',
                                        };
                                        $set('delivery_type', $deliveryType);
                                        $set('branch_id', null);
                                        $set('enrollment_mode', null);
                                        $set('sessions_purchased', null);
                                        $set('total_amount', 0);
                                    }
                                } else {
                                    $set('delivery_type', null);
                                    $set('branch_id', null);
                                    $set('enrollment_mode', null);
                                    $set('sessions_purchased', null);
                                    $set('total_amount', 0);
                                    $set('_course_price', null);
                                    $set('_allowed_modes', []);
                                }
                            })
                            ->label(__('enrollments.course')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('enrollments.delivery_type') ?? 'Delivery Type')
                    ->schema([
                        Forms\Components\Select::make('delivery_type')
                            ->options([
                                'online' => __('enrollments.delivery_type_options.online') ?? 'Online',
                                'onsite' => __('enrollments.delivery_type_options.onsite') ?? 'On-site',
                            ])
                            ->default('online')
                            ->required()
                            ->reactive()
                            ->visible(fn (Forms\Get $get) => !empty($get('course_id')))
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
                                $deliveryType = $get('delivery_type');
                                if ($deliveryType === 'online') {
                                    $set('branch_id', null);
                                }
                                self::resolveAndUpdatePrice($set, $get);
                            })
                            ->label(__('enrollments.delivery_type') ?? 'Delivery Type')
                            ->helperText(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                if (!$courseId) {
                                    return null;
                                }
                                $course = \App\Domain\Training\Models\Course::find($courseId);
                                if (!$course) {
                                    return null;
                                }
                                if ($course->delivery_type !== \App\Domain\Training\Enums\DeliveryType::Hybrid) {
                                    $type = match($course->delivery_type) {
                                        \App\Domain\Training\Enums\DeliveryType::Onsite => __('enrollments.delivery_type_options.onsite') ?? 'On-site',
                                        \App\Domain\Training\Enums\DeliveryType::Online => __('enrollments.delivery_type_options.online') ?? 'Online',
                                        default => 'Online',
                                    };
                                    return __('enrollments.auto_set_delivery_type') ?? "Auto-set to: {$type} (based on course delivery type)";
                                }
                                return __('enrollments.select_delivery_type_helper') ?? 'Choose whether this enrollment is for onsite or online delivery.';
                            }),
                    ])
                    ->visible(fn (Forms\Get $get) => !empty($get('course_id')))
                    ->collapsible(),

                Forms\Components\Section::make(__('enrollments.branch') ?? 'Branch')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->relationship(
                                name: 'branch',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                                    $courseId = $get('course_id');
                                    $deliveryType = $get('delivery_type') ?? 'online';

                                    // Filter branches based on course and delivery type
                                    if ($courseId) {
                                        // Map delivery_type to delivery_type enum for filtering
                                        $deliveryTypeEnum = match($deliveryType) {
                                            'onsite' => \App\Domain\Training\Enums\DeliveryType::Onsite,
                                            'online' => null, // online - will handle separately
                                            default => null,
                                        };

                                        if ($deliveryType === 'onsite' && $deliveryTypeEnum) {
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
                            ->nullable()
                            ->disabled(function (Forms\Get $get) {
                                // Disable when delivery_type is 'online'
                                return $get('delivery_type') === 'online';
                            })
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                self::resolveAndUpdatePrice($set, $get);
                            })
                            ->visible(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                if (!$courseId) {
                                    return false;
                                }
                                $deliveryType = $get('delivery_type') ?? 'online';
                                // Only visible when delivery_type is 'onsite'
                                return $deliveryType === 'onsite';
                            })
                            ->label(__('enrollments.branch')),
                    ])
                    ->visible(function (Forms\Get $get) {
                        $courseId = $get('course_id');
                        if (!$courseId) {
                            return false;
                        }
                        $deliveryType = $get('delivery_type') ?? 'online';
                        // Only visible when delivery_type is 'onsite'
                        return $deliveryType === 'onsite';
                    })
                    ->collapsible(),

                Forms\Components\Section::make(__('enrollments.enrollment_mode') ?? 'Enrollment Mode')
                    ->schema([
                        Forms\Components\Select::make('enrollment_mode')
                            ->options([
                                'course_full' => __('enrollments.enrollment_mode_options.course_full') ?? 'Full Course',
                                'per_session' => __('enrollments.enrollment_mode_options.per_session') ?? 'Per Session',
                                'trial' => __('enrollments.enrollment_mode_options.trial') ?? 'Trial',
                            ])
                            ->required()
                            ->reactive()
                            ->visible(fn (Forms\Get $get) => !empty($get('course_id')) && !empty($get('_allowed_modes')))
                            ->options(function (Forms\Get $get) {
                                $allowedModes = $get('_allowed_modes') ?? [];
                                $allOptions = [
                                    'course_full' => __('enrollments.enrollment_mode_options.course_full') ?? 'Full Course',
                                    'per_session' => __('enrollments.enrollment_mode_options.per_session') ?? 'Per Session',
                                    'trial' => __('enrollments.enrollment_mode_options.trial') ?? 'Trial',
                                ];
                                return array_intersect_key($allOptions, array_flip($allowedModes));
                            })
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if ($state === 'trial') {
                                    $set('sessions_purchased', 1);
                                } elseif ($state === 'course_full') {
                                    $set('sessions_purchased', null);
                                }
                                self::resolveAndUpdatePrice($set, $get);
                            })
                            ->label(__('enrollments.enrollment_mode') ?? 'Enrollment Mode')
                            ->helperText(__('enrollments.enrollment_mode_helper') ?? 'Select how the student will enroll in this course.'),
                    ])
                    ->visible(fn (Forms\Get $get) => !empty($get('course_id')))
                    ->collapsible(),

                Forms\Components\Section::make(__('enrollments.sessions') ?? 'Sessions')
                    ->schema([
                        Forms\Components\TextInput::make('sessions_purchased')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->reactive()
                            ->visible(function (Forms\Get $get) {
                                $mode = $get('enrollment_mode');
                                return in_array($mode, ['per_session', 'trial']);
                            })
                            ->disabled(function (Forms\Get $get) {
                                return $get('enrollment_mode') === 'trial';
                            })
                            ->maxValue(function (Forms\Get $get) {
                                return $get('_sessions_count') ?? 1;
                            })
                            ->default(function (Forms\Get $get) {
                                if ($get('enrollment_mode') === 'trial') {
                                    return 1;
                                }
                                return null;
                            })
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                self::resolveAndUpdatePrice($set, $get);
                            })
                            ->label(__('enrollments.sessions_purchased') ?? 'Sessions Purchased')
                            ->helperText(function (Forms\Get $get) {
                                $mode = $get('enrollment_mode');
                                $sessionsCount = $get('_sessions_count') ?? 1;
                                if ($mode === 'trial') {
                                    return __('enrollments.trial_sessions_helper') ?? 'Trial enrollment is locked to 1 session.';
                                }
                                return __('enrollments.per_session_helper') ?? "Select number of sessions (1 to {$sessionsCount}).";
                            }),
                    ])
                    ->visible(function (Forms\Get $get) {
                        $mode = $get('enrollment_mode');
                        return in_array($mode, ['per_session', 'trial']);
                    })
                    ->collapsible(),

                Forms\Components\Section::make(__('enrollments.pricing_registration') ?? 'Pricing')
                    ->schema([
                        Forms\Components\Select::make('pricing_type')
                            ->options([
                                'full' => __('enrollments.pricing_type_options.full'),
                                'installment' => __('enrollments.pricing_type_options.installment'),
                            ])
                            ->default('full')
                            ->required()
                            ->reactive()
                            ->disabled(function (Forms\Get $get) {
                                // Disable installment option if not allowed or for per-session/trial
                                $mode = $get('enrollment_mode');
                                if (in_array($mode, ['per_session', 'trial'])) {
                                    return true; // Installments not allowed for per-session/trial
                                }
                                return $get('_allow_installments') === false;
                            })
                            ->visible(function (Forms\Get $get) {
                                $mode = $get('enrollment_mode');
                                return $mode === 'course_full'; // Only show for full course
                            })
                            ->label(__('enrollments.pricing_type')),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->dehydrated()
                            ->label(__('enrollments.total_amount'))
                            ->suffix('OMR'),
                        Forms\Components\Hidden::make('currency_code')
                            ->default('OMR')
                            ->dehydrated(),
                        Forms\Components\Hidden::make('_allow_installments')
                            ->default(false)
                            ->dehydrated(false),
                        Forms\Components\Hidden::make('_min_down_payment')
                            ->dehydrated(false),
                        Forms\Components\Hidden::make('_max_installments')
                            ->dehydrated(false),
                        Forms\Components\Hidden::make('_course_price')
                            ->dehydrated(false),
                        Forms\Components\Hidden::make('_allowed_modes')
                            ->dehydrated(false),
                        Forms\Components\Hidden::make('_sessions_count')
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
                                $deliveryType = $get('delivery_type') ?? 'online';
                                $enrollmentMode = $get('enrollment_mode');
                                $sessionsPurchased = $get('sessions_purchased');
                                $totalAmount = $get('total_amount') ?? 0;

                                if (!$courseId) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500">Select a course to see pricing information.</p>');
                                }


                                if (!$enrollmentMode) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500">Select enrollment mode to see pricing information.</p>');
                                }

                                $pricingService = app(\App\Services\PricingService::class);
                                $coursePrice = $pricingService->resolveCoursePrice($courseId, $branchId, $deliveryType);

                                if (!$coursePrice) {
                                    $message = sprintf(
                                        'No active course price found. Searched: course_id=%d, branch_id=%s, delivery_type=%s. Please configure pricing.',
                                        $courseId,
                                        $branchId ? $branchId : 'null',
                                        $deliveryType
                                    );
                                    return new \Illuminate\Support\HtmlString('<p class="text-red-600">' . htmlspecialchars($message) . '</p>');
                                }

                                $precision = config('money.precision', 3);
                                $symbol = 'OMR';
                                
                                $html = '<div class="space-y-2">';
                                $html .= '<div class="grid grid-cols-2 gap-4">';
                                
                                // Show enrollment mode
                                $modeLabel = match($enrollmentMode) {
                                    'course_full' => 'Full Course',
                                    'per_session' => 'Per Session',
                                    'trial' => 'Trial',
                                    default => $enrollmentMode,
                                };
                                $html .= '<div><strong>Enrollment Mode:</strong> ' . htmlspecialchars($modeLabel) . '</div>';
                                
                                // Show sessions if applicable
                                if (in_array($enrollmentMode, ['per_session', 'trial'])) {
                                    $html .= '<div><strong>Sessions:</strong> ' . ($sessionsPurchased ?? 1) . '</div>';
                                }
                                
                                // Show total amount
                                $html .= '<div><strong>Total Amount:</strong> ' . number_format((float) $totalAmount, $precision) . ' ' . $symbol . '</div>';
                                
                                // Show course price details
                                if ($enrollmentMode === 'course_full') {
                                    $html .= '<div><strong>Course Price:</strong> ' . number_format((float) $coursePrice->price, $precision) . ' ' . $symbol . '</div>';
                                    $html .= '<div><strong>Allow Installments:</strong> ' . ($coursePrice->allow_installments ? 'Yes' : 'No') . '</div>';

                                    if ($coursePrice->allow_installments) {
                                        if ($coursePrice->min_down_payment) {
                                            $html .= '<div><strong>Min Down Payment:</strong> ' . number_format((float) $coursePrice->min_down_payment, $precision) . ' ' . $symbol . '</div>';
                                        }
                                        if ($coursePrice->max_installments) {
                                            $html .= '<div><strong>Max Installments:</strong> ' . $coursePrice->max_installments . '</div>';
                                        }
                                    }
                                } else {
                                    $html .= '<div><strong>Session Price:</strong> ' . number_format((float) $coursePrice->session_price, $precision) . ' ' . $symbol . '</div>';
                                    $html .= '<div><strong>Total Sessions:</strong> ' . ($coursePrice->sessions_count ?? 'N/A') . '</div>';
                                }

                                $html .= '</div>';
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ])
                    ->visible(function (Forms\Get $get) {
                        return !empty($get('course_id')) && !empty($get('enrollment_mode'));
                    })
                    ->collapsible()
                    ->collapsed(false),

                Forms\Components\Section::make(__('enrollments.enrollment_details') ?? 'Enrollment Details')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => __('enrollments.status_options.pending'),
                                'pending_payment' => __('enrollments.status_options.pending_payment'),
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
                    ->money('OMR')
                    ->sortable()
                    ->label(__('enrollments.total_amount')),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('OMR')
                    ->default(function ($record) {
                        return $record->payments()->where('status', 'paid')->sum('amount');
                    })
                    ->label(__('enrollments.paid_amount')),
                Tables\Columns\TextColumn::make('due_amount')
                    ->money('OMR')
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
                        EnrollmentStatus::PENDING_PAYMENT => 'warning',
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
                        'pending_payment' => __('enrollments.status_options.pending_payment'),
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
                                        return [$installment->id => 'Installment #' . $installment->installment_no . ' - ' . number_format($installment->amount, 2) . ' OMR (Due: ' . $installment->due_date->format('Y-m-d') . ')'];
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
                Tables\Actions\Action::make('generate_ar_invoice')
                    ->label(__('ar_invoices.generate_invoice') ?? 'Generate AR Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('ar_invoices.generate_invoice') ?? 'Generate AR Invoice')
                    ->modalDescription(__('ar_invoices.generate_invoice_description') ?? 'This will create an AR invoice for this enrollment if one does not exist.')
                    ->visible(fn (Enrollment $record) => !$record->arInvoice)
                    ->action(function (Enrollment $record) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            // Check if AR invoice already exists
                            $existingInvoice = \App\Domain\Accounting\Models\ArInvoice::where('enrollment_id', $record->id)->first();
                            
                            if ($existingInvoice) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('ar_invoices.invoice_already_exists') ?? 'AR Invoice already exists')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Ensure enrollment has user_id
                            if (empty($record->user_id)) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('ar_invoices.missing_user_id') ?? 'Cannot create AR invoice')
                                    ->body(__('ar_invoices.missing_user_id_description') ?? 'Enrollment is missing user_id. Please update the enrollment with a user.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Create AR invoice
                            // Note: due_amount is guarded, so we use unguarded to set it initially
                            $invoice = \App\Domain\Accounting\Models\ArInvoice::unguarded(function () use ($record) {
                                return \App\Domain\Accounting\Models\ArInvoice::create([
                                    'enrollment_id' => $record->id,
                                    'user_id' => $record->user_id,
                                    'branch_id' => $record->branch_id,
                                    'total_amount' => $record->total_amount,
                                    'due_amount' => $record->total_amount, // Initially equals total_amount
                                    'status' => 'open',
                                    'issued_at' => now(),
                                    'created_by' => $record->created_by ?? auth()->id(),
                                ]);
                            });

                            // Fire event for audit logging
                            event(new \App\Domain\Accounting\Events\InvoiceGenerated($invoice));

                            \Filament\Notifications\Notification::make()
                                ->title(__('ar_invoices.invoice_created') ?? 'AR Invoice Created')
                                ->body(__('ar_invoices.invoice_created_description', ['id' => $invoice->id]) ?? "AR Invoice #{$invoice->id} has been created successfully.")
                                ->success()
                                ->send();
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
