<?php

namespace MauticPlugin\MauticWebhookExtensionBundle\EventListener;

use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\WebhookBundle\Event\WebhookQueueEvent;
use Mautic\WebhookBundle\Model\WebhookModel;
use Mautic\WebhookBundle\WebhookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebhookSubscriber implements EventSubscriberInterface
{

	/**
	 * @var WebhookModel
	 */
	private $webhookModel;
	/**
	 * @var LeadModel
	 */
	private $leadModel;

	public function __construct(WebhookModel $webhookModel, LeadModel $leadModel)
	{
		$this->webhookModel = $webhookModel;
		$this->leadModel 	= $leadModel;
	}

	public static function getSubscribedEvents()
	{
		return [
			WebhookEvents::WEBHOOK_QUEUE_ON_ADD => ['onLeadNewUpdate', 100]
		];
	}

	public function onLeadNewUpdate(WebhookQueueEvent $event)
	{
		$queue = $event->getWebhookQueue();
		$payload = json_decode($queue->getPayload(), true);
		if (!isset($payload['contact'])) {
			return;
		}
		$contact = $this->leadModel->getEntity($payload['contact']['id']);
		$changes = $contact->getChanges(true);
		$processFields = ['test', 'clickedlinksms', 'clickedlinkmail'];
		$startQueue = false;
		foreach (array_keys($changes['fields']) as $field) {
			if (in_array($field, $processFields)) {
				$startQueue = true;
				break;
			}
		}
		if (!$startQueue) {
			$event->stopPropagation();
//			$event = null;
//			$event->setWebhookQueue();
//			$queue->setPayload(null);
		}

	}
}
