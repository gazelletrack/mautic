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
use Mautic\EmailBundle\Entity\Copy;
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

        // email_copy initialize
        if (!isset($params['date_created'])) {
            $params['date_created'] = new \DateTime();
        }

        if(!isset($params['subject'])){
          $params['subject'] = '';
        }

        if(!isset($params['body'])){
          $params['body'] = '';
        }

        if (!isset($params['email_open_date'])) {
            $params['email_open_date'] = $params['date_read'];
        }

        if (!isset($params['email_open_details'])) {
            $params['email_open_details'] = [];
        }

        // email_stats initialize
        $params['lead_id'] = $id;
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
            $params['date_read'] = new \DateTime();
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
            $params['last_opened'] = $params['email_open_date'];
        }

        //email_copy table's primary key
        $cp_id = $id."contact".$params['subject'];

        //repositories to get the database
        $rep =  $this->getDoctrine()->getRepository('MauticEmailBundle:Copy');
        $repository =  $this->getDoctrine()->getRepository('MauticEmailBundle:Stat');

        // management of the emails_copy table
        if(!$rep->find($cp_id))
        {
            $copy = new Copy();
            $copy->setId($cp_id);
            $copy->setDateCreated($params['date_created']);
            $copy->setSubject($params['subject']);
            $copy->setBody($params['body']);

            $rep->saveEntity($copy);
        }
        else {
            $copy = $rep->findOneById($cp_id);
        }

        $existing_record =  null;
        $opendetails_array = [];
        $items = $repository->findAll(['copy_id'=> $cp_id]);
        if(!items){
          echo "pass";

          foreach ($items as $key => $record) {
              if($record->getStoredCopy()->getId() == $cp_id && $record->getIsRead() == 1)
              {
                    $existing_record = $record;
                    //var_dump($temp);
                    echo "pass";
                    break;
              }
          }
      }
        if($params['is_read'] == 0){
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
              $stat->setStoredCopy($copy);

              $stat->setSource($params['source']);
              $stat->setSourceId($params['source_id']);
              $stat->setTokens($params['tokens']);

              $repository->saveEntity($stat);
        }
        elseif($params['is_read'] == 1){
            if(!$existing_record)
            {

                $opendetails = array( "begin" => array( 'datetime' => $params['email_open_date'] , 'useragent'=> $params['email_open_details']));
                $opendetails_array = $opendetails;

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
                $stat->setStoredCopy($copy);

                $stat->setSource($params['source']);
                $stat->setSourceId($params['source_id']);
                $stat->setTokens($params['tokens']);

                    $stat->setOpenCount(1);
                    $stat->setLastOpened($params['date_read']);
                    $stat->setOpenDetails($opendetails_array);

                $repository->saveEntity($stat);
            }
            else{
                  //$var =  $existing_record->getId();
                  //echo $var;
                  $index = $existing_record->getOpenCount();
                  $opendetails = array( $index => array( 'datetime' => $params['email_open_date'] , 'useragent'=> $params['email_open_details']));
                  $index++;
                  $old_opendetails = $existing_record->getOpenDetails();

                  $opendetails_array = array_merge($old_opendetails, $opendetails);
                  //var_dump($opendetails_array);
                  //echo "<br>";
                  $existing_record->setOpenCount($index);
                  $existing_record->setOpenDetails($opendetails_array);

                  $em = $this->getDoctrine()->getManager();
                  $em->persist($existing_record);
                  $em->flush();
            }
        }
        return new Response('Contact Event Inserted !');
    }
}
