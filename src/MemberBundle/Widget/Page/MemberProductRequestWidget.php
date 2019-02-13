<?php

namespace MemberBundle\Widget\Page;

use AppBundle\Widget\AbstractWidget;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use JMS\Serializer\SerializationContext;

class MemberProductRequestWidget extends AbstractWidget
{
    public static function defineDetails(): array
    {
        return [
            'title' => 'Member Product Request List'
        ];
    }

    public static function defineProperties($container): array
    {
        return [
            'limit' => [
                'type' => Type\ChoiceType::class,
                'options' => [
                    'choices' => [
                        1 => 1,
                        2 => 2,
                        3 => 3,
                        4 => 4,
                        5 => 5,
                        6 => 6,
                        7 => 7,
                        8 => 8,
                        9 => 9,
                        10 => 10,
                    ],
                    'attr' => [
                        'class' =>'form-control selectpicker requestLimit'
                    ],
                ],
            ],
        ];
    }

    public function onGetMemberProductRequestList($data)
    {
        $limit = $this->property('limit', 0);
        $page = $this->property('page', 1);
        $offset = ($page - 1) * $limit;

        $memberProductRequests = $this->getMemberProductRepository()->getRequestList($limit, $offset);

        if ($this->container->has('jms_serializer')) {
            $context = new SerializationContext();
            $context->setSerializeNull(true);
            $context->setGroups(['request_list']);

            $json = $this->container->get('jms_serializer')->serialize($memberProductRequests, 'json', $context);

            return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
        }

        return $memberProductRequests;
    }

    protected function getView()
    {
        return 'MemberBundle:Widget:Page\widget.html.twig';
    }

    protected function getBlockName(): string
    {
        return 'memberProductRequest';
    }

    private function getMemberProductRepository(): MemberProductRepository
    {
        return $this->getEntityManager()->getRepository(MemberProduct::class);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getDoctrine()->getEntityManager();
    }

    private function getDoctrine(): RegistryInterface
    {
        return $this->container->get('doctrine');
    }
}
