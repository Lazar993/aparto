<?php

return [
    'greeting' => 'Zdravo :name!',
    'greeting_comma' => 'Zdravo :name,',
    'salutation' => 'Srdačan pozdrav, :app',

    'fields' => [
        'apartment' => 'Stan',
        'check_in' => 'Datum dolaska',
        'check_out' => 'Datum odlaska',
        'nights' => 'Noći',
        'total_price' => 'Ukupna cena',
        'deposit_amount' => 'Iznos depozita',
        'balance_due' => 'Preostalo za uplatu',
        'address' => 'Adresa',
    ],

    'reservation_created' => [
        'subject' => 'Potvrda rezervacije - :apartment',
        'intro' => 'Hvala vam na zahtevu za rezervaciju.',
        'pending_notice' => 'Vaša rezervacija je trenutno na čekanju potvrde. Pregledaćemo zahtev i uskoro vam se javiti.',
        'account_created_title' => 'Dobrodošli! Vaš nalog je kreiran',
        'account_created_intro' => 'Da biste pristupili svom nalogu i pregledali rezervacije, postavite lozinku klikom na dugme ispod:',
        'set_password_action' => 'Postavi lozinku',
        'set_password_expiry' => 'Ovaj link ističe za 60 minuta.',
        'account_created_fallback_title' => 'Nalog je kreiran',
        'account_created_fallback_body' => 'Nalog je kreiran za vas. Molimo koristite opciju "Zaboravljena lozinka" na stranici za prijavu kako biste postavili lozinku.',
        'outro' => 'Ako imate pitanja, kontaktirajte nas.',
    ],

    'reservation_confirmed' => [
        'subject' => 'Rezervacija je potvrđena - :apartment',
        'intro' => 'Sjajne vesti! Vaša rezervacija je potvrđena.',
        'outro_one' => 'Radujemo se vašem dolasku!',
        'outro_two' => 'Ako imate pitanja ili posebne zahteve, slobodno nas kontaktirajte.',
    ],

    'reservation_canceled' => [
        'subject' => 'Rezervacija je otkazana - :apartment',
        'intro' => 'Vaša rezervacija je otkazana.',
        'outro' => 'Ako niste vi zatražili otkazivanje ili imate pitanja, kontaktirajte nas odmah.',
    ],

    'review_reminder' => [
        'subject' => 'Kako je prošao vaš boravak? Ostavite recenziju',
        'intro' => 'Hvala vam što ste boravili kod nas.',
        'stay_ended' => 'Vaš boravak u objektu **:apartment** je završen i voleli bismo da čujemo vaše utiske.',
        'impact' => 'Vaša recenzija pomaže drugim gostima da donesu bolju odluku pri rezervaciji.',
        'action' => 'Ostavite recenziju',
        'outro' => 'Hvala vam na vremenu i podršci.',
    ],
];
