<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Services\CvAnalysisService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analyze_cv')
                ->label('تحليل السيرة الذاتية بالذكاء الاصطناعي')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('تحليل السيرة الذاتية')
                ->modalDescription('سيتم تحليل ملف السيرة الذاتية واستخراج البيانات تلقائيًا.')
                ->visible(fn (): bool => filled($this->record->cv_path))
                ->action(function (): void {
                    if (
                        blank($this->record->cv_path)
                        || ! Storage::disk('local')->exists($this->record->cv_path)
                    ) {
                        Notification::make()
                            ->title('ملف السيرة الذاتية غير موجود')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        app(CvAnalysisService::class)->analyze($this->record);

                        $this->record->refresh();

                        Notification::make()
                            ->title('تم تحليل السيرة الذاتية بنجاح')
                            ->body('تم حفظ البيانات المستخرجة وربطها بسجل العميل.')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('فشل تحليل السيرة الذاتية')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }
}
