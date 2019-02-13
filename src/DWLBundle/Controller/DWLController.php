<?php

namespace DWLBundle\Controller;

use AppBundle\Controller\AbstractController;
use DbBundle\Entity\DWL;
use Doctrine\ORM\OptimisticLockException;
use DWLBundle\Form\DWLUploadType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use DWLBundle\Entity\DWLItem;
use DWLBundle\Form\DWLItemType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DWLController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_DWL_VIEW']);
        $form = $this->createForm(DWLUploadType::class, new DWL());

        return $this->render('DWLBundle:DWL:index.html.twig', ['form' => $form->createView()]);
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_DWL_VIEW']);
        $this->getSession()->save();
        $results = $this->getManager()->getList($request);

        $context = $this->createSerializationContext([
            'Search',
            '_link',
            'Default',
        ]);

        return $this->jsonResponse($results, JsonResponse::HTTP_OK, [], $context);
    }

    public function createAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_DWL_CREATE']);
        $form = $this->createForm(DWLUploadType::class, new DWL());

        return $this->render('DWLBundle:DWL:create.html.twig', ['form' => $form->createView()]);
    }

    public function getDWLAction(Request $request, $id)
    {
        $dwl = $this->getManager()->getRepository()->find($id);
        $this->getSession()->set(sprintf('dwl_u_%s', $dwl->getId()), $dwl->getUpdatedAt());

        return $this->response($request, $dwl, ['groups' => ['Default']]);
    }

    public function updateAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_DWL_UPDATE']);
        $dwl = $this->getManager()->getRepository()->find($id);
        $this->getSession()->set(sprintf('dwl_u_%s', $dwl->getId()), $dwl->getUpdatedAt());
        $form = $this->createForm(
            DWLUploadType::class,
            $dwl,
            ['action' => $this->getRouter()->generate('dwl.save', ['id' => $dwl->getId()])]
        );
        $form->remove('product')->remove('currency')->remove('date');
        $dwlItem = new DWLItem();
        $dwlItem->setUpdatedAt($dwl->getUpdatedAt());
        $itemForm = $this->createForm(DWLItemType::class, $dwlItem, ['validation_groups' => ['default']]);
        $lastUpdateEncoded = base64_encode($dwl->getUpdatedAt()->format('Y-m-d H:i:s'));
        $isSkypeBettingProduct = $dwl->getProduct()->getBetadminToSync();

        return $this->render('DWLBundle:DWL:update.html.twig', [
            'form' => $form->createView(),
            'itemForm' => $itemForm->createView(),
            'dwl' => $dwl,
            'lastUpdateEncoded' => $lastUpdateEncoded,
            'isSkypeBettingProduct' => $isSkypeBettingProduct,
        ]);
    }

    public function submitAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_DWL_UPDATE']);
        $dwl = $this->getManager()->getRepository()->find($id);
        $submitType = $request->get('submit', 'restrict');
        if ($dwl !== null) {
            $result = $this->getManager()->submit($dwl, $submitType);
            $notifications = [];
            $status = 200;
            if ($result['success'] === true) {
                $notifications = [
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans('notifications.submit.success.title', [], 'DWLBundle'),
                    'message' => $this->getTranslator()->trans(
                        'notifications.submit.success.message',
                        [
                            '%product%' => $dwl->getProduct()->getName(),
                            '%currency%' => $dwl->getCurrency()->getName(),
                        ],
                        'DWLBundle'
                    ),
                ];
            } else {
                $status = 422;
                $notifications = [
                    'type' => 'error',
                    'title' => $this->getTranslator()->trans('notifications.submit.error.title', [], 'DWLBundle'),
                    'message' => $this->getTranslator()->trans(
                        $result['error'],
                        [
                            '%product%' => $dwl->getProduct()->getName(),
                            '%currency%' => $dwl->getCurrency()->getName(),
                        ],
                        'DWLBundle'
                    ),
                ];
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['__notifications' => [$notifications]], $status);
            }
            $this->getSession()->getFlashBag()->add('notifications', $notifications);

            return $this->redirectToRoute('dwl.update_page', ['id' => $dwl->getId()]);
        } else {
            throw $this->createNotFoundException();
        }
    }

    public function saveAction(Request $request, $id = 'new')
    {
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_DWL_CREATE']);
            $dwlSubmited = $request->get('DWLUpload', []);
            $dwlInfo = [
                'product' => $dwlSubmited['product'],
                'currency' => $dwlSubmited['currency'],
                'date' => \DateTime::createFromFormat('n/j/Y', $dwlSubmited['date']),
            ];
            $dwl = $this->getManager()->getRepository()->findOneBy($dwlInfo);
            if ($dwl === null) {
                $dwl = new DWL();
            } else {
                $dwlPath = $this->getRouter()->generate('dwl.update_page', ['id' => $dwl->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

                return $this->json(
                    [
                        'data' => ['path' => $dwlPath],
                        'errors' => ['DWLUpload' => ['DWL already exists']],
                    ],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        } else {
            $this->denyAccessUnlessGranted(['ROLE_DWL_UPDATE']);
            $dwl = $this->getManager()->getRepository()->find($id);
        }

        $form = $this->createForm(DWLUploadType::class, $dwl, [
            'validation_groups' => $id !== 'new' ? 'newVersion' : 'default',
        ]);

        if ($id !== 'new') {
            $form
                ->remove('product')
                ->remove('currency')
                ->remove('date')
            ;
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                if ($data->getId() !== null) {
                    $data->setVersion($data->getVersion() + 1);
                }
                $data->setStatus(DWL::DWL_STATUS_UPLOADED);
                $this->getManager()->save($data);

                $file = $form->get('file')->getData();
                $this->getManager()->upload($file, $data);

                $notifications = [
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans(
                        'notifications.uploaded.title',
                        [
                            '%product%' => $data->getProduct()->getName(),
                            '%currency%' => $data->getCurrency()->getName(),
                        ],
                        'DWLBundle'
                    ),
                    'message' => $this->getTranslator()->trans(
                        'notifications.uploaded.message',
                        [
                            '%product%' => $data->getProduct()->getName(),
                            '%currency%' => $data->getCurrency()->getName(),
                        ],
                        'DWLBundle'
                    ),
                ];

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(
                        [
                            '__notifications' => [$notifications],
                            'data' => [
                                'id' => $data->getId(),
                            ],
                        ],
                        200
                    );
                }

                $this->getSession()->getFlashBag()->add('notifications', $notifications);

                return $this->redirectToRoute('dwl.update_page', ['id' => $data->getId()]);
            } catch (OptimisticLockException $e) {
                $notifications = [
                    'type' => 'error',
                    'title' => $this->getTranslator()->trans('notifications.notUpdatedForm.title', [], 'DWLBundle'),
                    'message' => $this->getTranslator()->trans('notifications.notUpdatedForm.message', [], 'DWLBundle'),
                ];
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['__notifications' => [$notifications]], 422);
                }
                $this->getSession()->getFlashBag()->add('notifications', $notifications);

                return $this->redirectToRoute('dwl.update_page', ['id' => $data->getId()]);
            } catch (\Exception $e) {
                throw $e;
            }
        } else {
            /* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
            $file = $form->get('file')->getData();
            $this->getMediaManager()->deleteFile($file->getRealPath(), true);
            $errors = $this->getManager()->getErrorMessages($form);
            $errors = ['errors' => array_dot($errors, '_', $form->getName() . '_', true)];

            return new JsonResponse($errors, 422);
        }
    }

    public function saveItemAction(Request $request, $id, $version, $username)
    {
        $this->denyAccessUnlessGranted(['ROLE_DWL_UPDATE']);
        /* @var $dwl \DbBundle\Entity\DWL */
        $dwl = $this->getManager()->getRepository()->find($id);
        if ($dwl === null) {
            throw new \Doctrine\ORM\NoResultException();
        }

        $dwlItem = $this->getManager()->getItem($dwl, $version, $username, $data);
        $form = $this->createForm(DWLItemType::class, $dwlItem, ['validation_groups' => ['default']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dwlItem = $form->getData();
            $item = $this->getManager()->saveItem($dwl, $dwlItem, $data);
            $encodedUpdateDate = base64_encode($dwl->getUpdatedAt()->format('Y-m-d H:i:s'));
            $item['_v'] = $encodedUpdateDate;

            return new JsonResponse($item);
        }
        $errors = $this->getManager()->getErrorMessages($form);
        $errors = ['errors' => array_dot($errors, '_', $form->getName() . '_', true)];

        return new JsonResponse($errors, 422);
    }

    public function exportToCsvDWLAction($id)
    {
        $this->denyAccessUnlessGranted(['ROLE_DWL_VIEW']);
        /* @var $dwl \DbBundle\Entity\DWL */
        $dwl = $this->getManager()->getRepository()->find($id);

        if ($dwl === null) {
            throw $this->createNotFoundException(sprintf("DWL %s was not found", $id));
        }

        $product = $dwl->getProduct();
        $csvOutput = $this->getManager()->exportToCsv((int) $id, $product->getBetadminToSync());

        return new Response($csvOutput, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => sprintf(
                'attachment; filename="%s_%s_%s.csv"',
                $dwl->getProduct()->getName(),
                $dwl->getCurrency()->getCode(),
                $dwl->getDate()->format('Ymd')
            ),
        ]);
    }

    public function getItemListAction(int $dwlId): Response
    {
        $response = $this->getManager()->getListOfDwlItems($dwlId);

        return $this->jsonResponse($response);
    }

    /**
     * Get media manager.
     *
     * @return \MediaBundle\Manager\MediaManager
     */
    protected function getMediaManager()
    {
        return $this->getContainer()->get('media.manager');
    }

    /**
     * @return \DWLBundle\Manager\DWLManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('dwl.manager');
    }
}
