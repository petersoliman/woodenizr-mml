<?php

namespace App\UserBundle\Form;

use App\UserBundle\Entity\User;
use ReCaptcha\ReCaptcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
    private ?Request $request;

    public function __construct(RequestStack $requestStack, ReCaptcha $reCaptcha, FlashBagInterface $flashBag)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->reCaptcha = $reCaptcha;
        $this->flashBag = $requestStack->getSession()->getFlashBag();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $authLogin = false;
        $defaultName = null;
        $defaultEmail = null;
        if ($this->request instanceof Request and $this->request->getSession()->has("AOuthData")) {
            $authLogin = true;
            $AOuthData = $this->request->getSession()->get("AOuthData");
            $defaultName = $AOuthData['name'];
            $defaultEmail = $AOuthData['email'];
        }

        $builder
            ->add('fullName', TextType::class, [
                "data" => $defaultName,
                "label" => "name_txt",
                "attr" => [
                    "class" => "input",
                    "data-stack-input-input" => "true",
                    "autocomplete" => "name",
                    "data-validate-input" => "name",
                    "data-form-validate-input-rules" => "required",
                    "placeholder" => "name_txt",
                ]
            ])
            ->add('email', EmailType::class, [
                "data" => $defaultEmail,
                "label" => "email_txt",
                'attr' => [
                    'placeholder' => 'email_txt',
                    "data-stack-input-input" => "true",
                    'class' => 'input',
                    'autocomplete' => 'email',
                    'data-validate-input' => 'email',
                    'data-form-validate-input-rules' => 'required|email',
                ],
            ])
            /*->add('gender', ChoiceType::class, [
                'choices' => [
                    'Male' => User::GENDER_MALE,
                    'Female' => User::GENDER_FEMALE,
                ],
            ])*/
            ->add('phone', TelType::class, [
                "label" => "phone_txt",
                'attr' => [
                    'placeholder' => 'phone_txt',
                    "data-stack-input-input" => "true",
                    'class' => 'input',
                    'autocomplete' => 'tel',
                    'data-validate-input' => 'phone',
                    'data-form-validate-input-rules' => 'required|phone',
                ],
            ]);

        if (!$authLogin) {
            $builder->add('plainPassword', PasswordType::class, [
                'required' => true,
                "label" => "password_txt",
                'attr' => [
                    "placeholder" => "password_txt",
                    'autocomplete' => 'new-password',
                    "class" => "input",
                    "data-stack-input-input" => "true",
                    "data-validate-input" => "password",
                    "data-form-validate-input-rules" => "required",
                    "data-ui-form-password" => "sign-password",

                ],
                "label_attr" => [
                    "class" => "label",
                    "data-stack-input-label" => "true"
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ]);
        }
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $request = Request::createFromGlobals();
        $result = $this->reCaptcha
            ->setExpectedHostname($request->getHost())
            ->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());

        if (!$result->isSuccess()) {
            $this->flashBag->add("error", 'The captcha is invalid. Please try again.');
            $event->getForm()->get('email')->addError(new FormError('The captcha is invalid. Please try again.'));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ["Default"],
        ]);
    }
}
