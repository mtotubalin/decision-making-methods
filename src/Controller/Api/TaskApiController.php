<?php

namespace App\Controller\Api;

use App\Dto\CharacteristicDto;
use App\Dto\CellDto;
use App\Dto\ConditionDto;
use App\Dto\ResultDto;
use App\Dto\MatrixRowDto;
use App\Dto\TaskDataDto;
use App\Entity\Cell;
use App\Entity\Task;
use App\Service\Matrix\MatrixService;
use App\Service\Task\TaskService;
use App\Service\TaskSolver\TaskSolverService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/task', 'api_task_')]
class TaskApiController extends BaseApiController
{
    public function __construct(
        private TaskService $taskService,
        private TaskSolverService $taskSolverService,
    ) {
    }

    #[Route(
        path: '/create',
        name: 'create',
        methods: [Request::METHOD_POST],
    )]
    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        // todo: можно добавить ваилидацию)

        $dto = new TaskDataDto(
            $data['name'],
            $data['matrixId'],
            $data['description'],
            $data['alternativeIds'],
            $data['characteristicIds'],
            $data['conditions'],
        );

        $this->taskService->createTask($dto);

        return $this->response(
            data: ['success' => true],
        );
    }

    #[Route(
        path: '/make-decision',
        name: 'make-decision',
        methods: [Request::METHOD_POST],
    )]
    public function makeDecision(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $result = $this->taskSolverService->solve($data['id'], $data['method']);

        $dto = new ResultDto($result);

        return $this->response(
            data: [
                'result' => $dto->toArray(),
            ],
        );
    }
}
