<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditReservation extends EditRecord
{
    protected static string $resource = ReservationResource::class;
    
    protected ?string $originalStatus = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store original status before saving for notification purposes
        $this->originalStatus = $this->record->status;
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Check if status was changed
        $statusChanged = $this->originalStatus !== null && $this->originalStatus !== $this->record->status;
        
        if ($statusChanged) {
            Log::info('Reservation status changed from Filament', [
                'reservation_id' => $this->record->id,
                'old_status' => $this->originalStatus,
                'new_status' => $this->record->status,
                'trigger' => 'filament_ui',
            ]);

            // Show notification that email will be sent (handled by observer)
            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Status Updated')
                ->body("Status changed to '{$this->record->status}'. Email notification will be sent automatically.")
                ->send();
        }
    }
}
