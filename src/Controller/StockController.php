<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StockDataStaging;
use App\Repository\StockDataStagingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stocks', name: 'app_stocks_')]
final class StockController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, StockDataStagingRepository $repository): Response
    {
        $search = strtoupper(trim($request->query->getString('q')));

        $queryBuilder = $repository->createQueryBuilder('s')
            ->andWhere('s.code IS NOT NULL')
            ->andWhere('s.price IS NOT NULL')
            ->orderBy('s.fetchedAt', 'DESC')
            ->setMaxResults(500);

        if ($search !== '') {
            $queryBuilder
                ->andWhere('UPPER(s.code) LIKE :search OR UPPER(s.ticker) LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        /** @var StockDataStaging[] $snapshots */
        $snapshots = $queryBuilder->getQuery()->getResult();
        $stocks = [];

        foreach ($snapshots as $snapshot) {
            $code = $snapshot->getCode();

            if ($code !== null && !isset($stocks[$code])) {
                $stocks[$code] = $snapshot;
            }
        }

        return $this->render('stocks/index.html.twig', [
            'stocks' => array_values($stocks),
            'search' => $search,
        ]);
    }

    #[Route('/{code}', name: 'show', methods: ['GET'], requirements: ['code' => '[A-Za-z0-9._-]+'])]
    public function show(string $code, StockDataStagingRepository $repository): Response
    {
        $normalizedCode = strtoupper(trim($code));

        /** @var StockDataStaging|null $stock */
        $stock = $repository->createQueryBuilder('s')
            ->andWhere('s.code = :code')
            ->andWhere('s.price IS NOT NULL')
            ->setParameter('code', $normalizedCode)
            ->orderBy('s.fetchedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($stock === null) {
            throw new NotFoundHttpException(sprintf('Stock "%s" was not found.', $normalizedCode));
        }

        /** @var StockDataStaging[] $history */
        $history = $repository->createQueryBuilder('s')
            ->andWhere('s.code = :code')
            ->andWhere('s.price IS NOT NULL')
            ->setParameter('code', $normalizedCode)
            ->orderBy('s.fetchedAt', 'DESC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();

        $change = null;
        $changePercentage = null;

        if ($stock->getPrice() !== null && $stock->getOpenPrice() !== null) {
            $change = $stock->getPrice() - $stock->getOpenPrice();

            if ($stock->getOpenPrice() !== 0.0) {
                $changePercentage = ($change / $stock->getOpenPrice()) * 100;
            }
        }

        return $this->render('stocks/show.html.twig', [
            'stock' => $stock,
            'history' => $history,
            'change' => $change,
            'change_percentage' => $changePercentage,
        ]);
    }
}
