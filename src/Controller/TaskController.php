<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskTypeForm;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/task')]
class TaskController extends AbstractController
{
    private $entityManager;
    private $taskRepository;

    public function __construct(EntityManagerInterface $entityManager, TaskRepository $taskRepository)
    {
        $this->entityManager = $entityManager;
        $this->taskRepository = $taskRepository;
    }

    #[Route('/', name: 'app_task_index')]
    public function index(): Response
    {
        $tasks = $this->taskRepository->findMainTasks();
        
        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/new', name: 'app_task_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskTypeForm::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($task);
            $this->entityManager->flush();

            $this->addFlash('success', 'Zadanie zostało utworzone.');
            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/new.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_task_show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function show(Task $task): Response
    {
        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_task_edit', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Task $task): Response
    {
        $form = $this->createForm(TaskTypeForm::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Zadanie zostało zaktualizowane.');
            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task): Response
    {
        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->getPayload()->getString('_token'))) {
            foreach ($task->getSubtasks() as $subtask) {
                $this->entityManager->remove($subtask);
            }
            $this->entityManager->remove($task);
            $this->entityManager->flush();
            $subtaskCount = $task->getSubtasks()->count();
            if ($subtaskCount > 0) {
                $this->addFlash('success', "Zadanie oraz {$subtaskCount} podzadań zostało usunięte.");
            } else {
                $this->addFlash('success', 'Zadanie zostało usunięte.');
            }
        }

        return $this->redirectToRoute('app_task_index');
    }

    #[Route('/{id}/subtask/new', name: 'app_task_subtask_new', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newSubtask(Request $request, Task $parentTask): Response
    {
        $subtask = new Task();
        $subtask->setParentTask($parentTask);
        
        $form = $this->createForm(TaskTypeForm::class, $subtask);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($subtask);
            $this->entityManager->flush();

            $this->addFlash('success', 'Podzadanie zostało utworzone.');
            return $this->redirectToRoute('app_task_show', ['id' => $parentTask->getId()]);
        }

        return $this->render('task/new_subtask.html.twig', [
            'parentTask' => $parentTask,
            'subtask' => $subtask,
            'form' => $form,
        ]);
    }

    #[Route('/kanban', name: 'app_task_kanban')]
    public function kanban(Request $request): Response
    {
        $showMyTasks = $request->query->get('my', '0') === '1';
        $user = $this->getUser();
        
        if ($showMyTasks && $user) {
            $pendingTasks = $this->taskRepository->findMyTasksByStatus($user, Task::PENDING);
            $inProgressTasks = $this->taskRepository->findMyTasksByStatus($user, Task::IN_PROGRESS);
            $completedTasks = $this->taskRepository->findMyTasksByStatus($user, Task::COMPLETED);
            $rejectedTasks = $this->taskRepository->findMyTasksByStatus($user, Task::REJECTED);
        } else {
            $pendingTasks = $this->taskRepository->findByStatus(Task::PENDING);
            $inProgressTasks = $this->taskRepository->findByStatus(Task::IN_PROGRESS);
            $completedTasks = $this->taskRepository->findByStatus(Task::COMPLETED);
            $rejectedTasks = $this->taskRepository->findByStatus(Task::REJECTED);
        }

        return $this->render('task/kanban.html.twig', [
            'pendingTasks' => $pendingTasks,
            'inProgressTasks' => $inProgressTasks,
            'completedTasks' => $completedTasks,
            'rejectedTasks' => $rejectedTasks,
            'showMyTasks' => $showMyTasks,
        ]);
    }

    #[Route('/update-status', name: 'app_task_update_status', methods: ['POST'])]
    public function updateStatus(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $task = $this->taskRepository->find($data['taskId']);
        if (!$task) {
            return new JsonResponse(['success' => false, 'message' => 'Zadanie nie znalezione'], 404);
        }

        $task->setStatus($data['newStatus']);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Status zadania został zaktualizowany',
            'task' => [
                'id' => (string) $task->getId(),
                'status' => $task->getStatus(),
                'statusLabel' => $task->getStatusLabel()
            ]
        ]);
    }
}