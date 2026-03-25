<?php

namespace App\Http\Repository;

use App\Models\Contact;

class ContactRepository
{
    public function create(array $data): Contact
    {
        return Contact::create($data);
    }
}
