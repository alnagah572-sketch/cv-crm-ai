<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Services\CvAnalysisService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Throwable;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function afterCreate(): void
    {
        if (blank($this->record->cv_path)) {
            return;
        }

        try {
            app(CvAnalysisService::class)->analyze($this->record);

            $this->record->refresh();

            Notification::make()
                ->title('تم تحليل السيرة الذاتية')
                ->body('تم استخراج بيانات العميل من ملف السيرة الذاتية وحفظها تلقائيًا.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('تم إنشاء العميل لكن تعذر تحليل السيرة الذاتية')
                ->body($e->getMessage())
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
