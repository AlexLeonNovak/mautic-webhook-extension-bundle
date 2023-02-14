<?php

namespace MauticPlugin\MauticWebhookExtensionBundle\EventListener;

use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\WebhookBundle\Http\Client;
use Mautic\WebhookBundle\Model\WebhookModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeadSubscriber implements EventSubscriberInterface
{

	/**
	 * @var Client
	 */
	private $httpClient;

	/**
	 * @var WebhookModel
	 */
	private $webhookModel;

	/**
	 * @var LeadModel
	 */
	private $leadModel;

	public function __construct(Client $httpClient, WebhookModel $webhookModel, LeadModel $leadModel)
	{
		$this->httpClient   = $httpClient;
		$this->webhookModel = $webhookModel;
		$this->leadModel 	= $leadModel;
	}

	public static function getSubscribedEvents()
	{
		return [
			LeadEvents::LEAD_POST_SAVE => ['onLeadSave', 255]
		];
	}

	public function onLeadSave(LeadEvent $event)
	{
		$lead = $event->getLead();
		if ($lead->isNew()) {
			return;
		}
		$this->debug();
		$changes = $lead->getChanges();
		$this->debug($changes);
		$processFields = ['test', 'clickedlinksms', 'clickedlinkmail'];
		$startQueue = false;
		foreach (array_keys($changes['fields']) as $field) {
			if (in_array($field, $processFields)) {
				$startQueue = true;
				break;
			}
		}


		if ($startQueue) {

			$serializationGroups =   [
				'leadDetails',
				'userList',
				'publishDetails',
				'ipAddress',
				'doNotContactList',
				'tagList',
			];
			$lead = $this->leadModel->getEntity($lead->getId());
			$this->debug(['fields' => $lead->getFields()]);
			$queuePayload = json_decode(
				$this->webhookModel->serializeData(
					['contact' => $lead],
					$serializationGroups
				),
				true
			);
			$this->debug(['queuePayload' => $queuePayload]);
			$queuePayload['timestamp'] = (new \DateTime())->format('c');
			$payload[LeadEvents::LEAD_POST_SAVE . '_update'][] = $queuePayload;

//			if (!is_dir(__DIR__ . '/payloads')) {
//				mkdir(__DIR__ . '/payloads');
//			}
//
//			file_put_contents(
//				__DIR__ . '/payloads/' . (new \DateTime())->format('Ymd_His.u') . '_' . $lead->getId() . '.json',
//				json_encode($payload, JSON_PRETTY_PRINT)
//			);

			$response = $this->httpClient->post('https://atomi.wobi.co.il/api/update_atomi', $payload);

			$this->debug([
				'StatusCode' => $response->getStatusCode(),
				'Body' => $response->getBody()->getContents(),
				'Payload' => $payload,
			]);
		}
	}

	public function debug($message = null, $nl = true)
	{
		return;
		if (is_array($message) || is_object($message)) {
			$output = print_r($message, true);
		} elseif (is_bool($message)) {
			$output = '(bool) ' . ($message ? 'true' : 'false');
		} elseif (is_string($message)) {
			if (trim($message)) {
				$output = $message;
			} else {
				$output = '(empty string)';
			}
		} else {
			$output = '=======================';
		}
		if ($nl) {
			$output .= PHP_EOL;
		}
		file_put_contents(__DIR__ . '/debug_' . date('Y-m-d') . '.log', date('Y-m-d H:i:s') . " " . $output, FILE_APPEND);
	}
}
