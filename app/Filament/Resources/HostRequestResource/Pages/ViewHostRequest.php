<?php

namespace App\Filament\Resources\HostRequestResource\Pages;

use App\Filament\Resources\HostRequestResource;
use App\Models\HostRequest;
use App\Models\User;
use App\Notifications\HostApproved;
use App\Notifications\HostRejected;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ViewHostRequest extends ViewRecord
{
    protected static string $resource = HostRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve host request')
                ->modalDescription('This will create a host account and send a welcome email with a password reset link. Continue?')
                ->visible(fn (): bool => $this->record->isPending())
                ->action(function (): void {
                    $record = $this->record;
                    $existingUser = User::where('email', $record->email)->first();

                    if ($existingUser) {
                        Notification::make()
                            ->title('A user with this email already exists.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $user = User::create([
                        'name' => $record->name,
                        'email' => $record->email,
                        'password' => Hash::make(Str::random(32)),
                        'user_type' => User::TYPE_HOST,
                    ]);

                    $token = Password::broker()->createToken($user);

                    $record->update([
                        'status' => HostRequest::STATUS_APPROVED,
                        'created_user_id' => $user->id,
                    ]);

                    $user->notify((new HostApproved($token))->locale($record->locale ?? 'sr'));

                    Notification::make()
                        ->title('Host approved and welcome email sent.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'created_user_id']);
                }),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->isPending())
                ->action(function (): void {
                    $this->record->update(['status' => HostRequest::STATUS_REJECTED]);

                    \Illuminate\Support\Facades\Notification::route('mail', $this->record->email)
                        ->notify((new HostRejected($this->record->name))->locale($this->record->locale ?? 'sr'));

                    Notification::make()
                        ->title('Host request rejected.')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
