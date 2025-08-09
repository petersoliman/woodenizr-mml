<?php

namespace App\VendorBundle\Form;

use App\VendorBundle\Entity\Vendor;
use App\VendorBundle\Form\Translation\VendorTranslationType;
use PN\ContentBundle\Form\Model\PostTypeModel;
use PN\ContentBundle\Form\PostType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use PN\MediaBundle\Form\SingleImageType;
use PN\SeoBundle\Form\SeoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VendorType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $postTypeModel = new PostTypeModel();
        $postTypeModel->add("description", "Description");
        $builder
            ->add('title')
            ->add('publish')
            ->add('commissionPercentage', TextType::class, [
                "label" => "Commission Percentage",
                "attr" => [
                    "placeholder" => "0.00",
                    "class" => "form-control",
                ],
            ])
            ->add('email')
            ->add('bankName')
            ->add('bankBranch')
            ->add('bankAccountHolderName')
            ->add('bankAccountNo', TextType::class, [
                "label" => "Bank Account Number",
            ])
            ->add('bankAccountIBAN', TextType::class, [
                "label" => "Bank Account IBAN",
            ])
            ->add('bankAccountSwiftCode')
            ->add('image', SingleImageType::class, ["mapped" => false, "label" => "Logo"])
            ->add('seo', SeoType::class)
            ->add('post', PostType::class, [
                "attributes" => $postTypeModel,
            ])
            ->add('translations', TranslationsType::class, [
                'entry_type' => VendorTranslationType::class,
                "label" => false,
                'entry_language_options' => [
                    'en' => [
                        'required' => true,
                    ],
                ],
            ]);
    }


    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => Vendor::class,
        ));
    }

}
