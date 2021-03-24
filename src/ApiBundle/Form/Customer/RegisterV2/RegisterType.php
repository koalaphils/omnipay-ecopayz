<?php

namespace ApiBundle\Form\Customer\RegisterV2;

use DbBundle\Entity\Customer as Member;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', Type\EmailType::class);
        $builder->add('fullName', Type\TextType::class);
        $builder->add('birthDate', Type\DateType::class, [
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
        ]);
        $builder->add('contacts', Type\FormType::class);
        $builder->add('country', EntityType::class, [
            'invalid_message' => 'Country is invalid.',
            'class' => 'DbBundle:Country',
            'choice_value' => 'code',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                        ->select('c');
            },
        ]);
        $builder->add('socials', Type\FormType::class);
        $builder->add('currency', EntityType::class, [
            'invalid_message' => 'Currency is invalid.',
            'class' => 'DbBundle:Currency',
            'choice_value' => 'code',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                    ->select('c');
            },
        ]);
        $builder->add('depositMethod', EntityType::class, [
            'invalid_message' => 'Deposit method is invalid.',
            'class' => 'DbBundle:PaymentOption',
            'choice_value' => 'code',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('po')
                    ->select('po');
            },
        ]);
        $builder->add('bookies', Type\CollectionType::class, [
            'entry_type' => \ApiBundle\Form\Customer\RegisterV2\BookieType::class,
            'allow_add' => true,
            'constraints' => [new \Symfony\Component\Validator\Constraints\Valid()],
        ]);
        $builder->add('tag', Type\TextType::class);
        $builder->add('websiteUrl', Type\TextType::class);
        $builder->add('preferredReferralName', Type\TextType::class);
        $builder->add('preferredPaymentGateway', Type\TextType::class);
        $builder->add('affiliate', Type\FormType::class);

        $builder->get('contacts')->add('mobile', Type\TextType::class);
        $builder->get('socials')->add('skype', Type\TextType::class);
        $builder->get('affiliate')->add('code', Type\TextType::class);
        $builder->get('affiliate')->add('promo', Type\TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\RegisterV2\Register::class,
            'csrf_protection' => false,
            'constraints' => [new \Symfony\Component\Validator\Constraints\Valid()],
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                $validationGroups = ['Default'];

                if ($data->getTag() == Member::ACRONYM_MEMBER) {
                    $validationGroups[] = 'member_only';
                }

                return $validationGroups;
            }
        ]);
    }

    public function getBlockPrefix()
    {
        return 'register';
    }
}