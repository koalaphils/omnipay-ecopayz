<?php

namespace ApiBundle\Form\Member;

use ApiBundle\Request\CreateMemberBannerRequest;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\BannerImage;
use DbBundle\Entity\MemberReferralName;
use DbBundle\Entity\MemberWebsite;
use DbBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MemberBannerType extends AbstractType
{
    private $doctrine;
    private $settingManager;

    public function __construct(Registry $doctrine, SettingManager $settingManager)
    {
        $this->doctrine = $doctrine;
        $this->settingManager = $settingManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('website', Type\TextType::class, [
            'model_transformer' => $this->fieldCallbackTransformer(),
        ]);
        $builder->add('type', Type\ChoiceType::class, [
            'choices' => CreateMemberBannerRequest::getTypes(),
            'model_transformer' => $this->fieldCallbackTransformer(),
        ]);
        $builder->add('language', Type\ChoiceType::class, [
            'choices' => CreateMemberBannerRequest::getLanguages(),
            'model_transformer' => $this->fieldCallbackTransformer(),
        ]);
        $builder->add('size', Type\TextType::class, [
            'model_transformer' => $this->fieldCallbackTransformer(),
        ]);
        $builder->add('campaignName', Type\TextType::class, [
            'model_transformer' => $this->fieldCallbackTransformer(),
        ]);
        $builder->add('trackingCode', Type\TextType::class, [
            'model_transformer' => $this->fieldCallbackTransformer(),
        ]);

        $this->onPostSubmit($builder, $options);
    }

    private function fieldCallbackTransformer(): CallbackTransformer
    {
        return new CallbackTransformer(
            function ($data) {
                return is_null($data) ? '' : $data;
            },
            function ($data) {
                return is_null($data) ? '' : $data;
            }
        );
    }

    private function onPostSubmit(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT,
            function(FormEvent $event) use ($options) {
                $data = $event->getData();
                $form = $event->getForm();
                $user = $options['user'];

                if ($data instanceof CreateMemberBannerRequest) {
                    if (!$this->isImageValid($data)) {
                        $form->addError(new FormError('Banner is invalid.'));
                    }

                    $this->validateWebsite($data, $user, $form);
                    $this->validateReferralName($data, $user, $form);
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('user');
        $resolver->setAllowedTypes('user', [User::class]);
        $resolver->setDefaults([
            'data_class' => CreateMemberBannerRequest::class,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'memberBanner';
    }

    private function isImageValid(CreateMemberBannerRequest $data): bool
    {
        $bannerImage = $this->getBannerImageRepository()->getByTypeLanguageSize(
            $data->getBannerImageType(),
            $data->getLanguage(),
            $data->getSize()
        );

        return is_null($bannerImage) ? false : true;
    }

    private function validateWebsite(CreateMemberBannerRequest $data, User $user, FormInterface $form): void
    {
        $memberWebsite = $this->getMemberWebsiteRepository()->findOneByWebsite($data->getWebsite());

        if ($memberWebsite instanceof MemberWebsite) {
            if ($memberWebsite->getMemberId() !== $user->getMemberId()) {
                $form->get('website')->addError(new FormError('Website is already used.'));
            } else {
                if (!$memberWebsite->isActive()) {
                    $form->get('website')->addError(new FormError('Website is suspended.'));
                }
            }
        } else {
            $memberWebsiteActiveCount = $this->getMemberWebsiteRepository()->getActiveCount($user->getMemberId());

            if ($memberWebsiteActiveCount == $this->getMaxWebsite()) {
                $form->get('website')->addError(
                    new FormError(
                        sprintf('Max of %d active websites only.', $this->getMaxWebsite())
                    )
                );
            }
        }
    }

    private function validateReferralName(CreateMemberBannerRequest $data, User $user, FormInterface $form): void
    {
        $memberReferralName = $this->getMemberReferralNameRepository()->findOneByName($data->getTrackingCode());

        if ($memberReferralName instanceof MemberReferralName) {
            if ($memberReferralName->getMemberId() !== $user->getMemberId()) {
                $form->get('trackingCode')->addError(new FormError('Tracking code is already used.'));
            } else {
                if (!$memberReferralName->isActive()) {
                    $form->get('trackingCode')->addError(new FormError('Tracking code is suspended.'));
                }
            }
        } else {
            $memberReferralNameActiveCount = $this->getMemberReferralNameRepository()->getActiveCount($user->getMemberId());

            if ($memberReferralNameActiveCount == $this->getMaxReferralName()) {
                $form->get('trackingCode')->addError(
                    new FormError(
                        sprintf('Max of %d tracking codes only.', $this->getMaxReferralName())
                    )
                );
            }
        }
    }

    private function getMaxReferralName(): int
    {
        return $this->settingManager->getSetting('member.referralName.max');
    }

    private function getMaxWebsite(): int
    {
        return $this->settingManager->getSetting('member.website.max');
    }

    private function getMemberWebsiteRepository(): \DbBundle\Repository\MemberWebsiteRepository
    {
        return $this->doctrine->getRepository(MemberWebsite::class);
    }

    private function getBannerImageRepository(): \DbBundle\Repository\BannerImageRepository
    {
        return $this->doctrine->getRepository(BannerImage::class);
    }

    private function getMemberReferralNameRepository(): \DbBundle\Repository\MemberReferralNameRepository
    {
        return $this->doctrine->getRepository(MemberReferralName::class);
    }
}