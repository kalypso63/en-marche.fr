<?php

namespace AppBundle\Controller\EnMarche;

use AppBundle\Entity\NewsletterSubscription;
use AppBundle\Form\NewsletterInvitationType;
use AppBundle\Form\NewsletterSubscriptionType;
use AppBundle\Form\NewsletterUnsubscribeType;
use AppBundle\Newsletter\Invitation;
use AppBundle\Newsletter\NewsletterSubscriptionProcess;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class NewsletterController extends Controller
{
    /**
     * @Route("/newsletter", name="newsletter_subscription")
     * @Method({"GET", "POST"})
     */
    public function subscriptionAction(
        Request $request,
        TranslatorInterface $translator,
        NewsletterSubscriptionProcess $newsletterSubscriptionProcess
    ): Response {
        $subscription = new NewsletterSubscription();
        $subscription->setEmail($request->query->get('mail'));

        $form = $this->createForm(NewsletterSubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('app.newsletter_subscription.handler')->subscribe($subscription);

            if ($path = $newsletterSubscriptionProcess->getSuccessRedirectPath() ?? null) {
                $newsletterSubscriptionProcess->terminate();

                $this->addFlash('info', $translator->trans('newsletter.subscription.success'));

                return $this->redirect($path);
            }

            return $this->redirectToRoute('newsletter_subscription_subscribed');
        }

        return $this->render('newsletter/subscription.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/newsletter/merci", name="newsletter_subscription_subscribed")
     * @Method("GET")
     */
    public function subscribedAction()
    {
        return $this->render('newsletter/subscribed.html.twig');
    }

    /**
     * @Route("/newsletter/desinscription", name="newsletter_unsubscribe")
     * @Method({"GET", "POST"})
     */
    public function unsubscribeAction(Request $request)
    {
        $form = $this->createForm(NewsletterUnsubscribeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('app.newsletter_subscription.handler')->unsubscribe((string) $form->getData()['email']);

            return $this->redirectToRoute('newsletter_unsubscribe_unsubscribed');
        }

        return $this->render('newsletter/unsubscribe.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/newsletter/desinscription/desinscrit", name="newsletter_unsubscribe_unsubscribed")
     * @Method("GET")
     */
    public function unsubscribedAction()
    {
        return $this->render('newsletter/unsubscribed.html.twig');
    }

    /**
     * @Route("/newsletter/invitation", name="newsletter_invitation")
     * @Method("GET|POST")
     */
    public function invitationAction(Request $request)
    {
        $form = $this->createForm(NewsletterInvitationType::class)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Invitation $invitation */
            $invitation = $form->getData();
            $this->get('app.newsletter_invitation.handler')->handle($invitation, $request->getClientIp());
            $request->getSession()->set('newsletter_invitations_count', count($invitation->guests));

            return $this->redirectToRoute('newsletter_invitation_sent');
        }

        return $this->render('newsletter/invitation.html.twig', [
            'invitation_form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/newsletter/invitation/merci", name="newsletter_invitation_sent")
     * @Method("GET")
     */
    public function invitationSentAction(Request $request)
    {
        if (!$invitationsCount = $request->getSession()->remove('newsletter_invitations_count')) {
            throw new PreconditionFailedHttpException('The invitations count is missing from the session.');
        }

        return $this->render('newsletter/invitation_sent.html.twig', [
            'invitations_count' => $invitationsCount,
        ]);
    }

    public function renderNewsletterFormAction(
        NewsletterSubscriptionProcess $newsletterSubscriptionProcess,
        array $options
    ): Response {
        $newsletterSubscriptionProcess->init($options['success_redirect_path'] ?? null);

        return $this->render('newsletter/form.html.twig', [
            'newsletter_form' => $this->createForm(NewsletterSubscriptionType::class, null, [
                'action' => $this->generateUrl('newsletter_subscription'),
            ])->createView(),
            'options' => $options,
        ]);
    }
}
