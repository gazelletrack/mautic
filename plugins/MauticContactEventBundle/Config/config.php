<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Freelancer
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'ContactEvent',
    'description' => 'Enables integration with Mautic supported CRMs.',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'routes'      => [
        'api' => [
            'mautic_api_postcontactevents' => [
                'path'       => '/contacts/{id}/events/new',
                'controller' => 'MauticContactEventBundle:ContactApi:postEvent',
                'method'     => 'POST',
            ],
        ],

    ],
    'services' => [
        'events' => [
        ],
        'models' => [
            'mautic.email.contactmodel.email' => [
                'class'     => 'MauticPlugin\MauticContactEventBundle\Model\ContactEventModel',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.helper.theme',
                    'mautic.helper.mailbox',
                    'mautic.helper.mailer',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.page.model.trackable',
                    'mautic.user.model.user',
                    'mautic.helper.core_parameters',
                    'mautic.core.model.messagequeue',
                ],
            ],
        ],
    ],
];
