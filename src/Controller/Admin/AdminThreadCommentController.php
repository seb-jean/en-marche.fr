<?php

namespace App\Controller\Admin;

use App\Entity\IdeasWorkshop\ThreadComment;
use App\IdeasWorkshop\Events;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route("/ideasworkshop-threadcomment/{uuid}")
 * @Security("has_role('ROLE_ADMIN_IDEAS_WORKSHOP')")
 * @Entity("comment", expr="repository.findOneByUuid(uuid, true)")
 */
class AdminThreadCommentController extends AbstractController
{
    use RedirectToTargetTrait;

    /**
     * Moderates a thread comment.
     *
     * @Route("/disable", methods={"GET"}, name="app_admin_thread_comment_disable")
     */
    public function disableAction(
        Request $request,
        ThreadComment $comment,
        ObjectManager $manager,
        EventDispatcherInterface $dispatcher
    ): Response {
        $comment->disable();

        $dispatcher->dispatch(new GenericEvent($comment), Events::THREAD_COMMENT_DISABLE);

        $manager->flush();
        $this->addFlash('sonata_flash_success', sprintf('Le commentaire « %s » a été modéré avec succès.', $comment->getUuid()));

        return $this->prepareRedirectFromRequest($request)
            ?? $this->redirectToRoute('admin_app_ideasworkshop_thread_show', ['id' => $comment->getThread()->getId()]);
    }

    /**
     * Enable a thread comment.
     *
     * @Route("/enable", methods={"GET"}, name="app_admin_thread_comment_enable")
     */
    public function enableAction(
        Request $request,
        ThreadComment $comment,
        ObjectManager $manager,
        EventDispatcherInterface $dispatcher
    ): Response {
        $comment->enable();

        $dispatcher->dispatch(new GenericEvent($comment), Events::THREAD_COMMENT_ENABLE);

        $manager->flush();
        $this->addFlash('sonata_flash_success', sprintf('Le commentaire « %s » a été activé avec succès.', $comment->getUuid()));

        return $this->prepareRedirectFromRequest($request)
            ?? $this->redirectToRoute('admin_app_ideasworkshop_thread_show', ['id' => $comment->getThread()->getId()]);
    }
}
