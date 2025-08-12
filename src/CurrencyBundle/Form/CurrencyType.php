<?php

namespace App\CurrencyBundle\Form;

use App\CurrencyBundle\Entity\Currency;
use App\CurrencyBundle\Form\Translation\CurrencyTranslationType;
use PN\LocaleBundle\Form\Type\TranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType as CurrencyFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CurrencyType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', CurrencyFormType::class, [
                "required" => true,
                "placeholder" => "Choose an option",
                "attr" => ["class" => 'select-search'],
                "constraints" => [new NotBlank()],
                "choice_loader" => null,
                "choices" => $this->getCurrenciesChoices(),
                'choice_attr' => function ($choice, $key, $value) {
                    return [
                        'data-title' => Currencies::getName($choice),
                        'data-symbol' => Currencies::getSymbol($choice),
                    ];
                },
            ])
            ->add('title')
            ->add('symbol')
            ->add('translations', TranslationsType::class, [
                'entry_type' => CurrencyTranslationType::class,
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Currency::class,
        ]);
    }

    private function getCurrenciesChoices(): array
    {
        $return = [];
        $currencies = Currencies::getNames();
        foreach ($currencies as $code => $name) {
            $return["{$name} ({$code})"] = $code;
        }

        return $return;
    }
}
