<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Super Admin';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Identitas Perusahaan')
                            ->schema([
                                // 1. NAMA & SLUG (AUTO)
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Perusahaan')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        $set('slug', Str::slug($state));
                                    }),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->readOnly(),

                                // 2. KODE & REFERRAL (AUTO GENERATE BUTTON)
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('code')
                                        ->label('Kode Perusahaan')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(10)
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('generate_code')
                                                ->icon('heroicon-m-arrow-path')
                                                ->action(function (Set $set) {
                                                    $set('code', strtoupper(Str::random(6)));
                                                })
                                        ),

                                    Forms\Components\TextInput::make('referral')
                                        ->label('Kode Referral')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('generate_ref')
                                                ->icon('heroicon-m-arrow-path')
                                                ->action(function (Set $set) {
                                                    $set('referral', 'REF-' . strtoupper(Str::random(5)));
                                                })
                                        ),
                                ]),

                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->required(),

                                Forms\Components\Textarea::make('address')
                                    ->rows(3)
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Group::make()
                    ->schema([
                        // 3. LOGO UPLOAD
                        Forms\Components\Section::make('Branding')
                            ->schema([
                                Forms\Components\FileUpload::make('logo')
                                    ->image()
                                    ->directory(fn($record) => 'tenants/logos/' . $record->tenant_id)
                                    ->avatar()
                                    ->imageEditor(),
                            ]),

                        // 4. SUBSCRIPTION CONTROL
                        Forms\Components\Section::make('Status Langganan')
                            ->schema([
                                Forms\Components\Select::make('subscription_plan')
                                    ->options([
                                        'free' => 'Free Trial',
                                        'pro' => 'Pro Plan',
                                        'enterprise' => 'Enterprise',
                                    ])
                                    ->default('free')
                                    ->required(),

                                Forms\Components\DateTimePicker::make('subscription_expired_at')
                                    ->label('Berlaku Sampai')
                                    ->native(false)
                                    ->displayFormat('d M Y H:i')
                                    ->timezone('Asia/Jakarta')
                                    ->default(now()->addMonth()),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'suspended' => 'Suspended (Blokir)',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Tenant $record) => $record->code),

                // BADGE PLAN
                Tables\Columns\TextColumn::make('subscription_plan')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'free' => 'gray',
                        'pro' => 'info',
                        'enterprise' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('subscription_expired_at')
                    ->label('Expired')
                    ->date('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->description(fn(Tenant $record) => $record->subscription_expired_at?->diffForHumans())
                    ->color(
                        fn(Tenant $record) =>
                        $record->subscription_expired_at && $record->subscription_expired_at->isPast()
                            ? 'danger'
                            : 'success'
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_plan')
                    ->options([
                        'free' => 'Free',
                        'pro' => 'Pro',
                        'enterprise' => 'Enterprise',
                    ]),
                Tables\Filters\TernaryFilter::make('active_subscription')
                    ->label('Status Langganan')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Expired')
                    ->queries(
                        true: fn($query) => $query->where('subscription_expired_at', '>', now()),
                        false: fn($query) => $query->where('subscription_expired_at', '<=', now()),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // ACTION CEPAT: PERPANJANG 1 BULAN
                Tables\Actions\Action::make('extend')
                    ->label('+1 Bulan')
                    ->icon('heroicon-m-calendar-days')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Tenant $record) {
                        // Logic perpanjang: Dari expired date yg lama atau dari sekarang (kalau udah mati)
                        $baseDate = $record->subscription_expired_at && $record->subscription_expired_at->isFuture()
                            ? $record->subscription_expired_at
                            : now();

                        $record->update([
                            'subscription_expired_at' => $baseDate->addMonth()
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Langganan Diperpanjang')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
