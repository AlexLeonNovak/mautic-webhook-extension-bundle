<?php
return [
	'name'        => 'Webhook Extension',
	'description' => 'Adds webhook execution depending on changes in specific fields',
	'version'     => '1.0',
	'author'      => 'Alex Leon',

	'services' => [
		'events' => [
//			'mautic.plugin.we.subscriber' => [
//				'class' => \MauticPlugin\MauticWebhookExtensionBundle\EventListener\WebhookSubscriber::class,
//				'arguments' => [
//					'mautic.webhook.model.webhook',
//					'mautic.lead.model.lead',
//				]
//			],
			'mautic.plugin.we.lead_subscriber' => [
				'class' => \MauticPlugin\MauticWebhookExtensionBundle\EventListener\LeadSubscriber::class,
				'arguments' => [
					'mautic.webhook.http.client',
					'mautic.webhook.model.webhook',
					'mautic.lead.model.lead',
				]
			]
		]
	]
];
