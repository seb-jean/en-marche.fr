<?php

namespace App\Controller\Api\Action;

use App\Action\RegisterManager;
use App\Entity\Action\Action;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class RegisterController extends AbstractController
{
    public function __invoke(Request $request, Action $action, UserInterface $adherent, RegisterManager $registerManager): Response
    {
        if ($request->isMethod(Request::METHOD_DELETE)) {
            $registerManager->unregister($action, $adherent);
        } else {
            $registerManager->register($action, $adherent);
        }

        return $this->json('OK');
    }
}
