<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->label('الاسم الكامل')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('رقم الجوال')
                    ->tel()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(30),

                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->maxLength(255),

                TextInput::make('city')
                    ->label('المدينة')
                    ->maxLength(100),

                TextInput::make('country')
                    ->label('الدولة')
                    ->default('Saudi Arabia')
                    ->maxLength(100),

                Select::make('service')
                    ->label('الخدمة')
                    ->options([
                        'cv_design' => 'تصميم سيرة ذاتية',
                        'cv_update' => 'تحديث سيرة ذاتية',
                        'translation' => 'ترجمة',
                        'linkedin' => 'LinkedIn',
                        'cover_letter' => 'خطاب تقديم',
                    ])
                    ->searchable(),

                Select::make('source')
                    ->label('مصدر العميل')
                    ->options([
                        'haraj' => 'حراج',
                        'whatsapp' => 'واتساب',
                        'snapchat' => 'سناب شات',
                        'tiktok' => 'تيك توك',
                        'instagram' => 'إنستغرام',
                        'referral' => 'ترشيح',
                        'other' => 'أخرى',
                    ])
                    ->searchable(),

                TextInput::make('price')
                    ->label('السعر')
                    ->numeric()
                    ->prefix('SAR')
                    ->default(0),

                Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'new' => 'جديد',
                        'contacted' => 'تم التواصل',
                        'working' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ])
                    ->default('new')
                    ->required(),

                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}

