<?php

namespace MauticPlugin\MauticContactEventBundle\Model;

use Mautic\EmailBundle\Model\EmailModel;

use Symfony\Component\EventDispatcher\Event;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\LeadTimelineEvent;

class ContactEventModel extends EmailModel
{
    public function getEngagements(Lead $lead, $filters = null, array $orderBy = null, $page = 1, $limit = 25)
    {
     
        $event = $this->dispatcher->dispatch(
            LeadEvents::TIMELINE_ON_GENERATE,
            new LeadTimelineEvent($lead, $filters, $orderBy, $page, $limit)
        );
        return [
            'events'   => $event->getEvents(),
            'filters'  => $filters,
            'order'    => $orderBy,
            'types'    => $event->getEventTypes(),
            'total'    => $event->getEventCounter()['total'],
            'page'     => $page,
            'limit'    => $limit,
            'maxPages' => $event->getMaxPage(),
        ];

    }
}