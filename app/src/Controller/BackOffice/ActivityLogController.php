<?php

namespace App\Controller\BackOffice;

use App\Document\ActivityLog;
use App\Presenter\ActivityLogPresenter;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(
    '/back-office/activity-logs',
    name: 'app_back_office_activity_log_',
)]
#[IsGranted('ROLE_ADMIN')]
final class ActivityLogController extends AbstractController
{
    private const LOGS_PER_PAGE = 25;

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly ActivityLogPresenter $activityLogPresenter,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(
            1,
            $request->query->getInt('page', 1),
        );

        $totalLogs = (int) $this->documentManager
            ->createQueryBuilder(ActivityLog::class)
            ->count()
            ->getQuery()
            ->execute();

        $totalPages = max(
            1,
            (int) ceil($totalLogs / self::LOGS_PER_PAGE),
        );

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $activityLogs = $this->documentManager
            ->createQueryBuilder(ActivityLog::class)
            ->sort('createdAt', 'DESC')
            ->skip(($page - 1) * self::LOGS_PER_PAGE)
            ->limit(self::LOGS_PER_PAGE)
            ->getQuery()
            ->execute();

        $logs = [];

        foreach ($activityLogs as $activityLog) {
            if (!$activityLog instanceof ActivityLog) {
                continue;
            }

            $logs[] = $this->activityLogPresenter->present(
                $activityLog,
            );
        }

        return $this->render(
            'back_office/activity_log/index.html.twig',
            [
                'logs' => $logs,
                'totalLogs' => $totalLogs,
                'currentPage' => $page,
                'totalPages' => $totalPages,
            ],
        );
    }
}
