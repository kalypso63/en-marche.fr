<?php

namespace AppBundle\Form;

use AppBundle\Donation\DonationRequest;
use AppBundle\Donation\DonationRequestUtils;
use AppBundle\Entity\Adherent;
use AppBundle\Form\DataTransformer\FloatToStringTransformer;
use AppBundle\Membership\MembershipRegistrationProcess;
use AppBundle\Repository\AdherentRepository;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DonationRequestType extends AbstractType
{
    private $donationRequestUtils;
    private $membershipRegistrationProcess;
    private $tokenStorage;
    private $requestStack;
    private $adherentRepository;

    public function __construct(
        DonationRequestUtils $donationRequestUtils,
        MembershipRegistrationProcess $membershipRegistrationProcess,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage,
        AdherentRepository $adherentRepository
    ) {
        $this->membershipRegistrationProcess = $membershipRegistrationProcess;
        $this->donationRequestUtils = $donationRequestUtils;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->adherentRepository = $adherentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder
                    ->create('amount', HiddenType::class)
                    ->addViewTransformer(new FloatToStringTransformer())
            )
            ->add('duration', HiddenType::class)
        ;

        if ($options['sponsor_form']) {
            $builder
                ->add('gender', GenderType::class)
                ->add('firstName', TextType::class, [
                    'filter_emojis' => true,
                ])
                ->add('lastName', TextType::class, [
                    'filter_emojis' => true,
                ])
                ->add('emailAddress', EmailType::class)
                ->add('address', TextType::class)
                ->add('postalCode', TextType::class)
                ->add('cityName', TextType::class, [
                    'required' => false,
                ])
                ->add('country', UnitedNationsCountryType::class)
                ->add('phone', PhoneNumberType::class, [
                    'required' => false,
                    'widget' => PhoneNumberType::WIDGET_COUNTRY_CHOICE,
                ])
            ;
        }

        if ($options['submit_button']) {
            $builder->add('submit', SubmitType::class, [
                'label' => $options['submit_label'] ?? 'Je donne',
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'createInitialDonationRequest']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['sponsor_form']) {
            $view->vars['sponsor_form'] = true;
        }
    }

    public function createInitialDonationRequest(FormEvent $formEvent): void
    {
        if (!$formEvent->getData()) {
            $user = $this->tokenStorage->getToken()->getUser();

            if (!$user instanceof Adherent) {
                $user = null;
            }

            // The user comes from the registration process
            if (null === $user && $uuid = $this->membershipRegistrationProcess->getAdherentUuid()) {
                $user = $this->adherentRepository->findByUuid($uuid);
            }

            $formEvent->setData($this->donationRequestUtils->createFromRequest($this->requestStack->getCurrentRequest(), $user));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'locale' => 'fr',
                'sponsor_form' => true,
                'submit_button' => true,
                'data_class' => DonationRequest::class,
                'translation_domain' => false,
            ])
            ->setDefined('submit_label')
            ->setAllowedTypes('locale', ['null', 'string'])
            ->setAllowedTypes('sponsor_form', 'bool')
            ->setAllowedTypes('submit_button', 'bool')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'app_donation';
    }
}
