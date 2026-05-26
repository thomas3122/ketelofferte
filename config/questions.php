<?php
declare(strict_types=1);

return [
    [
        'step' => 1,
        'key' => 'probleem',
        'label' => 'Wat is het probleem?',
        'help_text' => 'Kies wat het meest lijkt op uw situatie.',
        'type' => 'options',
        'required' => true,
        'options' => [
            [
                'value' => 'storing',
                'label' => 'Storing',
                'icon' => '⚠',
            ],
            [
                'value' => 'geen_warm_water',
                'label' => 'Geen warm water',
                'icon' => '♨',
            ],
            [
                'value' => 'lekkage',
                'label' => 'Lekkage',
                'icon' => '💧',
            ],
            [
                'value' => 'drukverlies',
                'label' => 'Drukverlies',
                'icon' => '↘',
            ],
            [
                'value' => 'ketel_valt_uit',
                'label' => 'Ketel valt uit',
                'icon' => '⏻',
            ],
            [
                'value' => 'maakt_lawaai',
                'label' => 'Maakt lawaai',
                'icon' => '🔊',
            ],
            [
                'value' => 'oude_ketel',
                'label' => 'Oude ketel',
                'icon' => '⌛',
            ],
            [
                'value' => 'anders',
                'label' => 'Anders',
                'icon' => '?',
            ],
        ],
    ],

    [
        'step' => 2,
        'key' => 'gewenste_hulp',
        'label' => 'Wat wilt u laten doen?',
        'help_text' => 'Zo kan de monteur beter inschatten wat nodig is.',
        'type' => 'options',
        'required' => true,
        'options' => [
            [
                'value' => 'reparatie',
                'label' => 'Reparatie',
                'icon' => '🔧',
            ],
            [
                'value' => 'vervanging',
                'label' => 'Vervangen',
                'icon' => '✓',
            ],
            [
                'value' => 'onderhoud',
                'label' => 'Onderhoud',
                'icon' => '⚙',
            ],
            [
                'value' => 'advies',
                'label' => 'Graag advies',
                'icon' => '?',
            ],
            [
                'value' => 'weet_ik_niet',
                'label' => 'Weet ik niet',
                'icon' => '?',
            ],
        ],
    ],

    [
        'step' => 3,
        'key' => 'urgentie',
        'label' => 'Hoe snel hulp nodig?',
        'help_text' => 'Kies hoe dringend uw aanvraag is.',
        'type' => 'options',
        'required' => true,
        'options' => [
            [
                'value' => 'vandaag',
                'label' => 'Vandaag',
                'icon' => '!',
            ],
            [
                'value' => 'binnen_24_uur',
                'label' => 'Binnen 24 uur',
                'icon' => '24',
            ],
            [
                'value' => 'deze_week',
                'label' => 'Deze week',
                'icon' => '7',
            ],
            [
                'value' => 'binnen_2_weken',
                'label' => 'Binnen 2 weken',
                'icon' => '14',
            ],
            [
                'value' => 'geen_haast',
                'label' => 'Geen haast',
                'icon' => '⌛',
            ],
        ],
    ],

    [
        'step' => 4,
        'key' => 'verwarming',
        'label' => 'Werkt de verwarming?',
        'help_text' => 'Dit helpt bij het beoordelen van de storing.',
        'type' => 'options',
        'required' => true,
        'options' => [
            [
                'value' => 'werkt_normaal',
                'label' => 'Werkt normaal',
                'icon' => '✓',
            ],
            [
                'value' => 'werkt_niet',
                'label' => 'Werkt niet',
                'icon' => '✕',
            ],
            [
                'value' => 'werkt_soms',
                'label' => 'Werkt soms',
                'icon' => '~',
            ],
            [
                'value' => 'alleen_warm_water_probleem',
                'label' => 'Alleen warm water probleem',
                'icon' => '♨',
            ],
            [
                'value' => 'weet_ik_niet',
                'label' => 'Weet ik niet',
                'icon' => '?',
            ],
        ],
    ],

    [
        'step' => 5,
        'key' => 'warm_water',
        'label' => 'Heeft u warm water?',
        'help_text' => 'Geef aan of het warme water nog goed werkt.',
        'type' => 'options',
        'required' => true,
        'options' => [
            [
                'value' => 'ja',
                'label' => 'Ja',
                'icon' => '✓',
            ],
            [
                'value' => 'nee',
                'label' => 'Nee',
                'icon' => '✕',
            ],
            [
                'value' => 'soms',
                'label' => 'Soms',
                'icon' => '~',
            ],
            [
                'value' => 'alleen_lauw',
                'label' => 'Alleen lauw',
                'icon' => '♨',
            ],
            [
                'value' => 'weet_ik_niet',
                'label' => 'Weet ik niet',
                'icon' => '?',
            ],
        ],
    ],

    [
        'step' => 6,
        'key' => 'leeftijd',
        'label' => 'Hoe oud is de ketel?',
        'help_text' => 'Een schatting is genoeg.',
        'type' => 'options',
        'required' => true,
        'options' => [
            [
                'value' => '0_5_jaar',
                'label' => '0-5 jaar',
                'icon' => '1',
            ],
            [
                'value' => '5_10_jaar',
                'label' => '5-10 jaar',
                'icon' => '2',
            ],
            [
                'value' => '10_15_jaar',
                'label' => '10-15 jaar',
                'icon' => '3',
            ],
            [
                'value' => '15_plus_jaar',
                'label' => '15+ jaar',
                'icon' => '4',
            ],
            [
                'value' => 'weet_ik_niet',
                'label' => 'Weet ik niet',
                'icon' => '?',
            ],
        ],
    ],

    [
        'step' => 7,
        'key' => 'fotos_toelichting',
        'label' => 'Foto’s en toelichting',
        'help_text' => 'Niet verplicht, maar helpt voor een betere offerte.',
        'type' => 'fields',
        'required' => false,
        'fields' => [
            [
                'key' => 'photos',
                'label' => 'Foto’s van de cv-ketel',
                'type' => 'file',
                'placeholder' => '',
                'autocomplete' => '',
                'required' => false,
                'full_width' => true,
                'multiple' => true,
                'accept' => 'image/jpeg,image/png,image/webp',
                'helper' => 'Foto’s van de ketel, leidingen, typeplaatje, rookgasafvoer of lekkage helpen bij een snellere beoordeling.',
            ],
            [
                'key' => 'message',
                'label' => 'Extra toelichting',
                'type' => 'textarea',
                'placeholder' => 'Bijv. foutcode, lekkage, lawaai of wanneer het probleem begon...',
                'autocomplete' => '',
                'required' => false,
                'full_width' => true,
                'helper' => 'Niet verplicht. Alles wat u invult helpt bij de beoordeling.',
            ],
        ],
    ],

    [
        'step' => 8,
        'key' => 'contact',
        'label' => 'Uw gegevens',
        'help_text' => 'Wij nemen contact op met advies of een offerte.',
        'type' => 'fields',
        'required' => true,
        'fields' => [
            [
                'key' => 'naam',
                'label' => 'Naam',
                'type' => 'text',
                'placeholder' => 'Uw naam',
                'autocomplete' => 'name',
                'required' => true,
                'full_width' => true,
            ],
            [
                'key' => 'telefoon',
                'label' => 'Telefoon',
                'type' => 'tel',
                'placeholder' => '06 12345678',
                'autocomplete' => 'tel',
                'required' => true,
            ],
            [
                'key' => 'email',
                'label' => 'E-mail',
                'type' => 'email',
                'placeholder' => 'uw@email.nl',
                'autocomplete' => 'email',
                'required' => true,
            ],
            [
                'key' => 'postcode',
                'label' => 'Postcode',
                'type' => 'text',
                'placeholder' => 'Bijv. 3011 AA',
                'autocomplete' => 'postal-code',
                'required' => true,
            ],
            [
                'key' => 'plaats',
                'label' => 'Plaats',
                'type' => 'text',
                'placeholder' => 'Bijv. Rotterdam',
                'autocomplete' => 'address-level2',
                'required' => true,
            ],
        ],
    ],
];
