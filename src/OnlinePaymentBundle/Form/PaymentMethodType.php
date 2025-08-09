<?php

namespace App\OnlinePaymentBundle\Form;

use App\OnlinePaymentBundle\Entity\PaymentMethod;
use App\OnlinePaymentBundle\Enum\PaymentMethodEnum;
use App\OnlinePaymentBundle\Form\Translation\PaymentMethodTranslationType;
use App\UserBundle\Model\UserInterface;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\MediaBundle\Form\SingleImageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PaymentMethodType extends AbstractType
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('note', TextareaType::class, [
                'required' => false,
            ])
            ->add('image', SingleImageType::class, ["mapped" => false])
            ->add('fees', NumberType::class, [
                'required' => false,
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => PaymentMethodTranslationType::class,
                "label" => false,
                'entry_language_options' => [
                    'en' => [
                        'required' => true,
                    ],
                ],
            ]);
        if ($this->authorizationChecker->isGranted(UserInterface::ROLE_SUPER_ADMIN)) {
            $builder
                ->add('active', CheckboxType::class, [
                    'required' => false,
                ])
                ->add('type', EnumType::class, [
                    'required' => true,
                    "placeholder" => "Choose an option",
                    "attr" => ["class" => "select-search"],
                    "class" => PaymentMethodEnum::class,
                    'choice_label' => function ($choice, $key, $value) {
                        return $choice->name();
                    },
                ]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PaymentMethod::class,
        ]);
    }

}
