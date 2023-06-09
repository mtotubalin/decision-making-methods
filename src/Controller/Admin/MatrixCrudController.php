<?php

namespace App\Controller\Admin;

use App\Dto\MatrixDataDto;
use App\Dto\MatrixDto;
use App\Entity\Matrix;
use App\Enum\UserRole;
use App\Form\MatrixCreateForm;
use App\Service\Matrix\MatrixService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SortOrder;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER)]
class MatrixCrudController extends BaseCrudController
{
    public function __construct(
        private AdminUrlGenerator $urlGenerator,
        private Security $security,
    ) {
        parent::__construct($security);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Матрица')
            ->setEntityLabelInPlural('Матрицы')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редактирование матрицы')
            ->setPageTitle(Crud::PAGE_INDEX, 'Матрицы')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление матрицы')
            ->setPageTitle(
                Crud::PAGE_DETAIL,
                fn(Matrix $matrix) => sprintf('Матрица #%s', $matrix->getId()),
            )
            ->setDefaultSort(['id' => SortOrder::DESC])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        $fill = Action::new('fill', 'Таблица', 'fa fa-table')
            ->linkToCrudAction('fillMatrix')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $fill)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->linkToCrudAction('saveMatrix');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->linkToCrudAction('saveMatrix')
                    ->displayIf(fn (Matrix $matrix) => $matrix->allowToEdit() && $matrix->getCreatedBy()->getUserIdentifier() === $this->security->getUser()->getUserIdentifier())
                ;
            })
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) {
                return $action->linkToCrudAction('saveMatrix')
                    ->displayIf(fn (Matrix $matrix) => $matrix->allowToEdit() && $matrix->getCreatedBy()->getUserIdentifier() === $this->security->getUser()->getUserIdentifier())
                ;
            })
        ;
    }

    public function makeDecision(
        AdminContext $context,
    ): Response {
        /** @var Matrix $matrix */
        $matrix = $context->getEntity()->getInstance();

        $dto = new MatrixDto($matrix);

        return $this->render('admin/page/make-decision.html.twig', [
            'title' => sprintf('Решение матрицы "%s"', $matrix),
            'data' => [
                'matrix' => $dto->toArray(),
            ],
        ]);
    }

    public function createTask(
        AdminContext $context,
    ): Response {
        /** @var Matrix $matrix */
        $matrix = $context->getEntity()->getInstance();

        if (
            $context->getUser()->getUserIdentifier() !== $matrix->getCreatedBy()->getUserIdentifier()
            && false === in_array(UserRole::ADMIN, $context->getUser()->getRoles())
        ) {
            throw $this->createAccessDeniedException();
        }

        $dto = new MatrixDto($matrix);

        return $this->render('admin/page/create-task.html.twig', [
            'title' => sprintf('Создание задачи для матрицы "%s"', $matrix),
            'data' => [
                'matrix' => $dto->toArray(),
            ],
        ]);
    }

    public function fillMatrix(
        Request $request,
        AdminContext $context,
    ): Response {
        /** @var Matrix $matrix */
        $matrix = $context->getEntity()->getInstance();

        $dto = new MatrixDto($matrix);

        $next = [];

        if (
            $context->getUser()->getUserIdentifier() === $matrix->getCreatedBy()->getUserIdentifier()
            || in_array(UserRole::ADMIN, $context->getUser()->getRoles())
        ) {
            $next = [
                'url' => $this->urlGenerator
                    ->setController(MatrixCrudController::class)
                    ->setAction('createTask')
                    ->setEntityId($matrix->getId())
                    ->generateUrl(),
                'label' => 'Создать задачу',
            ];
        }

        return $this->render('admin/page/fill-matrix.html.twig', [
            'title' => sprintf('Данные матрицы "%s"', $matrix),
            'data' => [
                'matrix' => $dto->toArray(),
                'allowEdit' => $matrix->allowToEdit() && $matrix->getCreatedBy()->getUserIdentifier() === $this->security->getUser()->getUserIdentifier(),
                'next' => $next,
            ],
        ]);
    }

    public function saveMatrix(
        Request $request,
        AdminContext $context,
        MatrixService $matrixService,
    ): Response {
        /** @var Matrix $matrix */
        $matrix = $context->getEntity()->getInstance() ?? new Matrix();

        $form = $this->createForm(MatrixCreateForm::class, $matrix, [
            'attr' => [
                'id' => 'save-form',
            ],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $dto = new MatrixDataDto(
                $form->get('title')->getData(),
                $form->get('alternatives')->getData(),
                $form->get('characteristics')->getData(),
            );

            $matrixService->save($matrix, $dto);

            $url = $this->urlGenerator->setController(self::class)
                ->setAction('fillMatrix')
                ->setEntityId($matrix->getId())
                ->generateUrl()
            ;

            return $this->redirect($url);
        }

        return $this->render('admin/page/create-new-matrix.html.twig', [
            'form' => $form,
            'matrix' => $matrix,
        ]);
    }

    public static function getEntityFqcn(): string
    {
        return Matrix::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Название'),
            AssociationField::new('matrixAlternative', 'Альтернативы')->hideOnForm(),
            AssociationField::new('matrixCharacteristic', 'Показатели')->hideOnForm(),
        ];
    }
}
