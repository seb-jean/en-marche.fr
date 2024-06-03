<?php

namespace App\JeMarche\Handler;

use App\Entity\Action\Action;
use App\Firebase\JeMarcheMessaging;
use App\JeMarche\Command\ActionCreatedNotificationCommand;
use App\JeMarche\Notification\ActionCreatedNotification;
use App\Repository\Action\ActionRepository;
use App\Repository\PushTokenRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ActionCreatedNotificationCommandHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ActionRepository $actionRepository,
        private readonly JeMarcheMessaging $messaging,
        private readonly PushTokenRepository $pushTokenRepository,
        private readonly string $voxHost,
    ) {
    }

    public function __invoke(ActionCreatedNotificationCommand $command): void
    {
        /** @var Action $action */
        if (!$action = $this->actionRepository->findOneByUuid($command->getUuid()->toString())) {
            return;
        }

        if (!$zone = $action->getParisBoroughOrDepartment()) {
            return;
        }

        if ($tokens = $this->pushTokenRepository->findAllForZone($zone)) {
            $notification = ActionCreatedNotification::create($action, $tokens);
            $notification->setDeepLink('https://'.$this->voxHost.'/actions?uuid='.$action->getUuid()->toString());
            $this->messaging->send($notification);
        }
    }
}
