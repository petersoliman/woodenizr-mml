<?php

namespace App\CMSBundle\Form;

use App\CMSBundle\Entity\SiteSetting;
use App\CMSBundle\Enum\SiteSettingTypeEnum;
use App\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SiteSettingType extends AbstractType
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        if ($this->authorizationChecker->isGranted(UserInterface::ROLE_SUPER_ADMIN)) {
            $builder
                ->add('manageBySuperAdminOnly', CheckboxType::class, [
                    'required' => false,
                ])
                ->add('type', EnumType::class, [
                    "class" => SiteSettingTypeEnum::class,
                    "attr" => ["class" => "select-search"],
                    'choice_label' => function ($choice, $key, $value) {
                        return $choice->name();
                    },
                ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $entity = $event->getData();
        if ($entity->getId() != null) {
            $this->addValueField($form, $entity);
        }
    }

    private function addValueField(FormInterface $form, $entity): void
    {
        $inputType = TextType::class;
        $options = [
            "required" => false
        ];

        switch ($entity->getType()) {
            case SiteSettingTypeEnum::HTML_TAG:
            case SiteSettingTypeEnum::SVG_CODE:
                $inputType = TextareaType::class;
                $options["attr"]["rows"] = 10;
                break;
            case SiteSettingTypeEnum::NUMBER:
                $inputType = NumberType::class;
                break;
            case SiteSettingTypeEnum::EMAIL:
                $inputType = EmailType::class;
                break;
            case SiteSettingTypeEnum::URL:
                $inputType = UrlType::class;
                break;
            case SiteSettingTypeEnum::COLOR_CODE:
                $inputType = TextType::class;
                $options["attr"]["class"] = "colorpicker-show-input";
                $options["attr"]["data-preferred-format"] = "hex";
                break;
            case SiteSettingTypeEnum::BOOLEAN:
                $inputType = ChoiceType::class;
                $options["attr"]["class"] = "select-search";
                $options['placeholder'] = null;
                $options['choices'] = [
                    'Yes' => "1",
                    'No' => "0",
                ];
            case SiteSettingTypeEnum::IMAGE:
            case SiteSettingTypeEnum::FAVICON:
                $inputType = FileType::class;
                $options["mapped"] = false;
                $options["attr"]["class"] = "file-styled";
                $options["attr"]["accept"] = ".png,.jpg,.jpeg";
                $options["constraints"] = [
                    new NotBlank(["message" => "Please provide a file"]),
                    new File([
                        "maxSize" => "3M",
                        "mimeTypes" => [
                            'image/jpeg',
                            'image/png',
                        ],
                    ])
                ];
                if ($entity->getType() === SiteSettingTypeEnum::FAVICON) {
                    $options["constraints"][] = new Callback(function ($file, ExecutionContextInterface $context) {
                        if ($file instanceof UploadedFile) {
                            $imageSize = getimagesize($file->getPathname());
                            if ($imageSize[0] !== 32 || $imageSize[1] !== 32) {
                                $context->buildViolation("The Favicon must be 32x32 pixels.")
                                    ->addViolation();
                            }
                        }
                    });
                }
                break;
        }


        $form->add('value', $inputType, $options);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteSetting::class,
        ]);
    }

}
