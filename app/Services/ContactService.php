<?php

namespace App\Services;

use App\Http\Repository\ContactRepository;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    public function __construct(
        private ContactRepository $contactRepository,
    ) {}

    public function createContact(array $data, string $ip, string $userAgent): Contact
    {
        return $this->contactRepository->create([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'email' => $data['email'],
            'message' => $data['message'],
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    public function sendAdminNotificationEmail(Contact $contact, array $data): void
    {
        $fullName = trim($data['name'] . ' ' . $data['surname']);
        $adminEmail = (string) config('website.contact_email');

        $mailBody = implode(PHP_EOL, [
            'New contact request from front website.',
            '',
            'Name: ' . $fullName,
            'Email: ' . $data['email'],
            'Contact ID: ' . $contact->id,
            'Message:',
            $data['message'],
            '',
            'Sent at: ' . now()->toDateTimeString(),
        ]);

        try {
            Mail::raw($mailBody, function ($message) use ($adminEmail, $fullName, $data) {
                $message->to($adminEmail)
                    ->replyTo($data['email'], $fullName)
                    ->subject('Aparto contact form');
            });
        } catch (\Throwable $exception) {
            Log::error('Contact form email send failed', [
                'email' => $data['email'],
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
