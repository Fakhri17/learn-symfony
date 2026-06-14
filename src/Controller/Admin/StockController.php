<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\StockDataStaging;
use App\Repository\StockDataStagingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/stocks', name: 'admin_stocks_')]
#[IsGranted('ROLE_ADMIN')]
final class StockController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, StockDataStagingRepository $repository): Response
    {
        $code = $this->normalizeFilter($request->query->get('code'));
        $status = $this->normalizeFilter($request->query->get('status'));
        $source = $this->normalizeFilter($request->query->get('source'));

        $queryBuilder = $repository->createQueryBuilder('s')
            ->orderBy('s.fetchedAt', 'DESC')
            ->setMaxResults(100);

        if ($code !== null) {
            $queryBuilder->andWhere('s.code = :code')->setParameter('code', strtoupper($code));
        }

        if ($status !== null) {
            $queryBuilder->andWhere('s.status = :status')->setParameter('status', strtolower($status));
        }

        if ($source !== null) {
            $queryBuilder->andWhere('s.source = :source')->setParameter('source', $source);
        }

        /** @var StockDataStaging[] $rows */
        $rows = $queryBuilder->getQuery()->getResult();

        return $this->render('admin/stocks/index.html.twig', [
            'rows' => $rows,
            'filters' => ['code' => $code, 'status' => $status, 'source' => $source],
        ]);
    }

    #[Route('/{code}', name: 'show', methods: ['GET'], requirements: ['code' => '[A-Za-z0-9._-]+'])]
    public function show(string $code, Request $request, StockDataStagingRepository $repository): Response
    {
        $normalizedCode = strtoupper(trim($code));
        $status = $this->normalizeFilter($request->query->get('status'));
        $source = $this->normalizeFilter($request->query->get('source'));

        $latestQueryBuilder = $repository->createQueryBuilder('s')
            ->andWhere('s.code = :code')
            ->setParameter('code', $normalizedCode)
            ->orderBy('s.fetchedAt', 'DESC')
            ->setMaxResults(1);

        if ($status !== null) {
            $latestQueryBuilder->andWhere('s.status = :status')->setParameter('status', strtolower($status));
        }

        if ($source !== null) {
            $latestQueryBuilder->andWhere('s.source = :source')->setParameter('source', $source);
        }

        /** @var StockDataStaging|null $latestRow */
        $latestRow = $latestQueryBuilder->getQuery()->getOneOrNullResult();

        if ($latestRow === null) {
            throw new NotFoundHttpException(sprintf('Stock data for code "%s" was not found.', $normalizedCode));
        }

        $historyQueryBuilder = $repository->createQueryBuilder('s')
            ->andWhere('s.code = :code')
            ->setParameter('code', $normalizedCode)
            ->orderBy('s.fetchedAt', 'DESC')
            ->setMaxResults(100);

        if ($status !== null) {
            $historyQueryBuilder->andWhere('s.status = :status')->setParameter('status', strtolower($status));
        }

        if ($source !== null) {
            $historyQueryBuilder->andWhere('s.source = :source')->setParameter('source', $source);
        }

        /** @var StockDataStaging[] $historyRows */
        $historyRows = $historyQueryBuilder->getQuery()->getResult();

        return $this->render('admin/stocks/show.html.twig', [
            'code' => $normalizedCode,
            'row' => $latestRow,
            'history_rows' => $historyRows,
            'filters' => ['status' => $status, 'source' => $source],
            'raw_payload_json' => json_encode(
                $latestRow->getRawPayload(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ) ?: '{}',
        ]);
    }

    private function normalizeFilter(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
