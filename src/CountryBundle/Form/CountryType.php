<?php

namespace CountryBundle\Form;

use AppBundle\Form\Type\Select2Type;
use AppBundle\Manager\AppManager;
use AppBundle\Manager\SettingManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use DbBundle\Entity\Country;

class CountryType extends AbstractType
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected $doctrine;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected $router;

    /**
     * @var AppManager
     */
    protected $appManager;

    public function __construct(Registry $doctrine, Router $router, AppManager $appManager)
    {
        $this->doctrine = $doctrine;
        $this->router = $router;
        $this->appManager = $appManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $locales = $this->appManager->getAvailableLocales();
        $choicesLocales = [];
        foreach ($locales as $locale) {
            $choicesLocales[$locale['name'] . ' (' . $locale['code'] . ')'] = $locale['code'];
        }

        $builder
            ->add('code', TextType::class, [
                'label' => 'fields.code',
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'label' => 'fields.name',
                'required' => true,
            ])
            ->add('currency', \AppBundle\Form\Type\Select2Type::class, [
                'label' => 'fields.currency',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('currency.list_search'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                    'data-allow-clear' => 'true',
                ],
                'required' => false,
            ])
            ->add('phoneCode', TextType::class, [
                'label' => 'fields.phoneCode',
                'required' => true,
            ])
            ->add('tags', \AppBundle\Form\Type\Select2Type::class, [
                'label' => 'fields.tags',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-tags' => 'true',
                ],
                'multiple' => true,
                'required' => false,
            ])
            ->add('locale', ChoiceType::class, [
                'label' => 'fields.locale',
                'required' => true,
                'choices' => $choicesLocales,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'form.save',
                'translation_domain' => 'AppBundle',
            ])
        ;

        $builder->get('currency')->addViewTransformer(new CallbackTransformer(
            function ($currency) {
                if ($currency && !is_array($currency) && $currency->getId()) {
                    $currency = $this->getCurrencyRepository()->find($currency);

                    return ['id' => $currency->getId(), 'text' => $currency->getName()];
                }

                return $currency;
            },
            function ($currency) {
                if ($currency) {
                    return $this->getCurrencyRepository()->find($currency);
                }

                return null;
            }
        ));

        $builder->get('currency')->addModelTransformer(new CallbackTransformer(
            function ($currency) {
                if ($currency && $currency->getId()) {
                    $currency = $this->getCurrencyRepository()->find($currency);

                    return [['id' => $currency->getId(), 'text' => $currency->getName()]];
                }

                return $currency;
            },
            function ($currency) {
                if ($currency) {
                    return $this->getCurrencyRepository()->find($currency);
                }

                return null;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Country::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'country',
            'validation_groups' => 'default',
            'translation_domain' => 'CountryBundle',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Country';
    }

    /**
     * @return \DbBundle\Repository\CurrencyRepository
     */
    public function getCurrencyRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Currency');
    }
}
