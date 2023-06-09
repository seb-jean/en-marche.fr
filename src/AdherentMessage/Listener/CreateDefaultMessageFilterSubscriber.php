<?php

namespace App\AdherentMessage\Listener;

use App\AdherentMessage\AdherentMessageTypeEnum;
use App\AdherentMessage\Events;
use App\AdherentMessage\Filter\FilterFactory;
use App\AdherentMessage\MessageEvent;
use App\Entity\Adherent;
use App\Entity\AdherentMessage\AdherentMessageInterface;
use App\Entity\AdherentMessage\TransactionalMessageInterface;
use App\Scope\ScopeGeneratorResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateDefaultMessageFilterSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ScopeGeneratorResolver $scopeGeneratorResolver)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::MESSAGE_PRE_CREATE => ['createDefaultMessageFilter', 1000],
        ];
    }

    public function createDefaultMessageFilter(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if (
            (
                AdherentMessageInterface::SOURCE_API === $message->getSource()
                && !$message instanceof TransactionalMessageInterface
            )
            || !\in_array($message->getType(), [
                AdherentMessageTypeEnum::DEPUTY,
                AdherentMessageTypeEnum::REFERENT,
                AdherentMessageTypeEnum::SENATOR,
                AdherentMessageTypeEnum::LRE_MANAGER_ELECTED_REPRESENTATIVE,
                AdherentMessageTypeEnum::CANDIDATE,
                AdherentMessageTypeEnum::CORRESPONDENT,
                AdherentMessageTypeEnum::REGIONAL_COORDINATOR,
                AdherentMessageTypeEnum::STATUTORY,
            ], true)
        ) {
            return;
        }

        if (!($author = $message->getAuthor()) instanceof Adherent) {
            return;
        }

        $message->setFilter(FilterFactory::create(
            $author,
            $message->getType(),
            $this->scopeGeneratorResolver->generate()
        ));
    }
}
