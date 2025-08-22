<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductTransactionResource\Pages;
use App\Models\ProductTransaction;
use App\Models\PromoCode;
use App\Models\Shoe;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductTransactionResource extends Resource
{
    protected static ?string $model = ProductTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Product and Prize')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('shoe_id')
                                        ->relationship('shoe', 'name')
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $get, callable $set, $component) {
                                            $shoe = Shoe::find($state);
                                            $price = $shoe ? $shoe->price : 0;
                                            $quantity = $get('quantity') ?? 1;
                                            $subTotalAmount = $price * $quantity;

                                            $set('price', $price);
                                            $set('sub_total_amount', $subTotalAmount);

                                            $discount = $get('discount_amount') ?? 0;
                                            $grandTotalAmount = $subTotalAmount - $discount;
                                            $set('grand_total_amount', $grandTotalAmount);

                                            $size = $shoe ? $shoe->sizes->pluck('size', 'id')->toArray() : [];
                                            $set('shoe_sizes', $size);

                                            // Only generate booking_trx_id if creating a new record
                                            $livewire = $component->getLivewire();
                                            if (blank($livewire->record)) {
                                                $set('booking_trx_id', ProductTransaction::generateUniqueTrxId());
                                            }
                                        })
                                        ->afterStateHydrated(function (callable $get, callable $set, $state) {
                                            $shoeId = $state;
                                            if($shoeId) {
                                                $shoe = Shoe::find($shoeId);
                                                $sizes = $shoe ? $shoe->sizes->pluck('size', 'id')->toArray() : [];
                                                $set('shoe_sizes', $sizes);
                                            }
                                        }),

                                    Select::make('shoe_size')
                                        ->label('Shoe Size')
                                        ->options(function (callable $get) {
                                            $sizes = $get('shoe_sizes');
                                            return is_array($sizes) ? $sizes : [];
                                        })
                                        ->required()
                                        ->live(),

                                    TextInput::make('quantity')
                                        ->required()
                                        ->numeric()
                                        ->prefix('Qty')
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                            $price = $get('price');
                                            $quantity = $state;
                                            $subTotalAmount = $price * $quantity;
                                            $set('sub_total_amount', $subTotalAmount);

                                            $discount = $get('discount_amount') ?? 0;
                                            $grandTotalAmount = $subTotalAmount - $discount;
                                            $set('grand_total_amount', $grandTotalAmount);
                                        }),

                                    Select::make('promo_code_id')
                                        ->relationship('promoCode', 'code')
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                            $subTotalAmount = $get('sub_total_amount');
                                            $promoCode = PromoCode::find($state);
                                            $discount = $promoCode ? $promoCode->discount_amount : 0;

                                            $set('discount_amount', $discount);

                                            $grandTotalAmount = $subTotalAmount - $discount;
                                            $set('grand_total_amount', $grandTotalAmount);
                                        }),

                                    TextInput::make('sub_total_amount')
                                        ->required()
                                        ->readOnly()
                                        ->numeric()
                                        ->prefix('IDR'),

                                    TextInput::make('grand_total_amount')
                                        ->required()
                                        ->readOnly()
                                        ->numeric()
                                        ->prefix('IDR'),

                                    TextInput::make('discount_amount')
                                        ->required()
                                        ->numeric()
                                        ->prefix('IDR'),
                                ]),
                        ]),

                    Step::make('Customer Information')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('phone')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('email')
                                        ->required()
                                        ->maxLength(255),

                                    Textarea::make('address')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('city')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('post_code')
                                        ->required()
                                        ->maxLength(255),
                                ]),
                        ]),

                    Step::make('Payment Information')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('booking_trx_id')
                                        ->required()
                                        ->readOnly()
                                        ->maxLength(255),

                                    ToggleButtons::make('is_paid')
                                        ->label('Apakah sudah membayar?')
                                        ->boolean()
                                        ->grouped()
                                        ->icons([
                                            true => 'heroicon-o-pencil',
                                            false => 'heroicon-o-clock',
                                        ])
                                        ->required(),

                                    FileUpload::make('proof')
                                        ->image()
                                        ->required(),
                                ]),
                        ]),
                ])
                ->columnSpan('full')
                ->columns(1)
                ->skippable()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('shoe.thumbnail'),

                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('booking_trx_id')
                    ->searchable(),

                IconColumn::make('is_paid')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Terverifikasi')
            ])
            ->filters([
                SelectFilter::make('shoe_id')
                    ->label('Shoe')
                    ->relationship('shoe', 'name')
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ProductTransaction $record) => !$record->is_paid)
                    ->action(function (ProductTransaction $record) {
                        $record->is_paid = true;
                        $record->save();

                        Notification::make()
                            ->title('Order Approved')
                            ->success()
                            ->body('The order has been successfully approved.')
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListProductTransactions::route('/'),
            'create' => Pages\CreateProductTransaction::route('/create'),
            'edit' => Pages\EditProductTransaction::route('/{record}/edit'),
        ];
    }
}
