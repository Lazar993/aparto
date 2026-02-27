<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Notifications\ReservationConfirmed;
use App\Notifications\ReservationCanceled;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class EditReservation extends EditRecord
{
    protected static string $resource = ReservationResource::class;
    
    protected ?string $originalStatus = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store original status before saving
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
        Log::info('EditReservation afterSave called', [
            'reservation_id' => $this->record->id,
            'current_status' => $this->record->status,
            'original_status' => $this->originalStatus,
            'status_changed' => $this->originalStatus !== $this->record->status,
        ]);

        // Check if status was changed by comparing with original stored before save
        $statusChanged = $this->originalStatus !== null && $this->originalStatus !== $this->record->status;
        
        if ($statusChanged) {
            $notificationClass = null;
            $emailType = '';

            switch ($this->record->status) {
                case 'confirmed':
                    $notificationClass = ReservationConfirmed::class;
                    $emailType = 'confirmation';
                    break;
                case 'canceled':
                    $notificationClass = ReservationCanceled::class;
                    $emailType = 'cancellation';
                    break;
            }

            Log::info('Status changed detected', [
                'old_status' => $this->originalStatus,
                'new_status' => $this->record->status,
                'notification_class' => $notificationClass,
                'email_type' => $emailType,
            ]);

            if ($notificationClass) {
                try {
                    // Send notification email
                    if ($this->record->user_id) {
                        // Send to registered user
                        $user = User::find($this->record->user_id);
                        if ($user) {
                            $user->notify(new $notificationClass($this->record));
                            Log::info("Reservation {$emailType} email sent", [
                                'reservation_id' => $this->record->id,
                                'user_email' => $user->email,
                                'status' => $this->record->status,
                            ]);
                        }
                    } else {
                        // Send to guest email
                        Notification::route('mail', $this->record->email)
                            ->notify(new $notificationClass($this->record));
                        Log::info("Reservation {$emailType} email sent to guest", [
                            'reservation_id' => $this->record->id,
                            'email' => $this->record->email,
                            'status' => $this->record->status,
                        ]);
                    }

                    // Show success notification in Filament
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title(ucfirst($emailType) . ' Email Sent')
                        ->body("The reservation {$emailType} email has been sent to " . $this->record->email)
                        ->send();
                } catch (\Exception $e) {
                    Log::error("Failed to send reservation {$emailType} email", [
                        'error' => $e->getMessage(),
                        'reservation_id' => $this->record->id,
                        'status' => $this->record->status,
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Show error notification in Filament
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title('Email Failed')
                        ->body("Failed to send {$emailType} email: " . $e->getMessage())
                        ->send();
                }
            }
        } else {
            Log::info('No status change detected', [
                'reservation_id' => $this->record->id,
                'current_status' => $this->record->status,
                'original_status' => $this->originalStatus,
            ]);
        }
    }
}
