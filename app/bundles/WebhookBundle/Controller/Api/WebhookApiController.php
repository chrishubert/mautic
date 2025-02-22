<?php

namespace Mautic\WebhookBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\WebhookBundle\Entity\Webhook;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class WebhookApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(ControllerEvent $event)
    {
        $this->model            = $this->getModel('webhook');
        $this->entityClass      = Webhook::class;
        $this->entityNameOne    = 'hook';
        $this->entityNameMulti  = 'hooks';
        $this->serializerGroups = ['hookDetails', 'categoryList', 'publishDetails'];

        parent::initialize($event);
    }

    /**
     * Gives child controllers opportunity to analyze and do whatever to an entity before going through serializer.
     *
     * @param string $action
     *
     * @return mixed
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
        // We have to use this hack to have a simple array instead of the one the serializer gives us
        $entity->buildTriggers();
    }

    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        $eventsToKeep = [];

        // Build webhook events from the triggers
        if (isset($parameters['triggers']) && is_array($parameters['triggers'])) {
            $entity->setTriggers($parameters['triggers']);
            $eventsToKeep = $parameters['triggers'];
        }

        // Remove events missing in the PUT request
        if ('PUT' === $this->request->getMethod()) {
            foreach ($entity->getEvents() as $event) {
                if (!in_array($event->getEventType(), $eventsToKeep)) {
                    $entity->removeEvent($event);
                }
            }
        }
    }

    public function getTriggersAction()
    {
        return $this->handleView(
            $this->view(
                [
                    'triggers' => $this->model->getEvents(),
                ]
            )
        );
    }
}
