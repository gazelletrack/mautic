<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Freelancer
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactEventBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\EmailBundle\Controller\Api\EmailApiController;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;

use MauticPlugin\MauticContactEventBundle\EventListener\LeadSubscriber;
use MauticPlugin\MauticContactEventBundle\Entity\StatRepository;
use MauticPlugin\MauticContactEventBundle\Entity\EmailEvent;
use MauticPlugin\MauticContactEventBundle\Model\ContactEventModel;

use FOS\RestBundle\Util\Codes;
use JMS\Serializer\SerializationContext;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;


/**
 * ContactApiController extends EmailApiController, in the end, extends CommonApiController
 */
class ContactApiController extends EmailApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->getModel('lead.lead');
        $this->entityClass      = 'Mautic\LeadBundle\Entity\Lead';
        $this->entityNameOne    = 'contact';
        $this->entityNameMulti  = 'contacts';
        $this->permissionBase   = 'lead:leads';
        $this->serializerGroups = ['leadDetails', 'userList', 'publishDetails', 'ipAddress', 'tagList'];
    }

    public function postEventAction($id)
    {
        $params = $this->request->request->all();

        $this->model = $this->getModel('lead.lead');
        $entity = $this->model->getEntity($id);

        if ($entity === null) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'view')) {
            return $this->accessDenied();
        }

        if (!isset($params['lead_id'])) {
            return new Response('Contact Id is not defined. The Contact Id must be defined.');
        }

        if (!isset($params['email_address'])) {
            $params['email_address'] = '';
        }

        if (!isset($params['date_sent'])) {
            $params['date_sent'] = new \DateTime();
        }

        if (!isset($params['is_read'])) {
            $params['is_read'] = 0;
        }

        if (!isset($params['is_failed'])) {
            $params['is_failed'] = 0;
        }

        if (!isset($params['viewed_in_browser'])) {
            $params['viewed_in_browser'] = 0;
        }

        if (!isset($params['date_read'])) {
            $params['date_read'] = '';
        }

        if (!isset($params['retry_count'])) {
            $params['retry_count'] = 0;
        }

        if (!isset($params['open_count'])) {
            $params['open_count'] = 0;
        }

        if (!isset($params['tracking_hash'])) {
            $params['tracking_hash'] = '';
        }

        if (!isset($params['source'])) {
            $params['source'] = '';
        }

        if (!isset($params['source_id'])) {
            $params['source_id'] = 0;
        }

        if (!isset($params['tokens'])) {
            $params['tokens'] = [];
        }

        if (!isset($params['last_opened'])) {
            $params['last_opened'] = '';
        }

        if (!isset($params['open_details'])) {
            $params['open_details'] = [];
        }

        $stat = new Stat();
        $stat->setLead($entity);
        $stat->setEmailAddress($params['email_address']);
        $stat->setDateSent($params['date_sent']);
        $stat->setIsRead($params['is_read']);
        $stat->setIsFailed($params['is_failed']);
        $stat->setViewedInBrowser($params['viewed_in_browser']);
        $stat->setDateRead($params['date_read']);
        $stat->setTrackingHash($params['tracking_hash']);
        $stat->setRetryCount($params['retry_count']);

        $stat->setSource($params['source']);
        $stat->setSourceId($params['source_id']);
        $stat->setTokens($params['tokens']);
        
        $stat->setOpenCount($params['open_count']);
        $stat->setLastOpened($params['last_opened']);
        $stat->setOpenDetails($params['open_details']);

        $repository =  $this->getDoctrine()->getRepository('MauticEmailBundle:Stat');
 
        $repository->saveEntity($stat);

        return new Response('Email Event is correctly inserted in Mautic.');
    }
}
