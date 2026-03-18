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
        'already_reviewed' => 'Hvala vam što ste ponovo izabrali :apartment i što ste već ostavili recenziju.',
        'already_reviewed_outro' => 'Nadamo se da ćemo vas uskoro ponovo ugostiti.',
        'outro' => 'Hvala vam na vremenu i podršci.',
    ],

    'host_approved' => [
        'subject' => 'Dobrodošli na :app — Vaš nalog domaćina je spreman',
        'intro' => 'Čestitamo! Vaš zahtev da postanete domaćin na :app je odobren.',
        'account_ready' => 'Vaš nalog domaćina je kreiran. Da biste počeli, postavite lozinku klikom na dugme ispod:',
        'set_password_action' => 'Postavite lozinku',
        'set_password_expiry' => 'Ovaj link ističe za 60 minuta. Ako istekne, možete koristiti opciju "Zaboravljena lozinka" na stranici za prijavu.',
        'outro' => 'Kada postavite lozinku, možete se prijaviti u admin panel i početi sa upravljanjem vaših stanova.',
    ],

    'host_rejected' => [
        'subject' => 'Vaš zahtev za domaćina na :app',
        'intro' => 'Hvala vam na interesovanju da postanete domaćin na :app.',
        'rejection_notice' => 'Nažalost, nakon pažljivog razmatranja, nismo u mogućnosti da odobrimo vaš zahtev da postanete domaćin na našoj platformi. Ova odluka nije laka i ne odražava nužno kvalitet vašeg zahteva, već je rezultat usklađivanja sa našim trenutnim potrebama i standardima.',
        'outro' => 'Cenimo vaše razumevanje i želimo vam sve najbolje u vašim budućim poduhvatima.',
    ],

    'host_request_admin' => [
        'subject' => 'Novi zahtev za domaćina od :name',
        'greeting' => 'Zdravo Admin!',
        'intro' => 'Novi zahtev za domaćina je poslat.',
        'fields' => [
            'name' => 'Ime',
            'email' => 'Email',
            'phone' => 'Telefon',
            'city' => 'Grad',
            'listing_url' => 'Link ka oglasu',
            'apartments' => 'Broj stanova',
        ],
        'action' => 'Pregledaj u admin panelu',
        'outro' => 'Molimo pregledajte i odobrite ili odbijte ovaj zahtev.',
    ],
];
