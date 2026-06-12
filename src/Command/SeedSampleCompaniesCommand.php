<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-sample-companies',
    description: 'Seed sample IDX companies into the company table using upsert by code.',
)]
class SeedSampleCompaniesCommand extends Command
{
    /**
     * @var array<int, array{code: string, name: string, yahooTicker: string, isActive: bool}>
     */
    private const SAMPLE_COMPANIES = [
        ['code' => 'BBCA', 'name' => 'Bank Central Asia Tbk', 'yahooTicker' => 'BBCA.JK', 'isActive' => true],
        ['code' => 'BBRI', 'name' => 'Bank Rakyat Indonesia Tbk', 'yahooTicker' => 'BBRI.JK', 'isActive' => true],
        ['code' => 'BMRI', 'name' => 'Bank Mandiri Tbk', 'yahooTicker' => 'BMRI.JK', 'isActive' => true],
        ['code' => 'TLKM', 'name' => 'Telkom Indonesia Tbk', 'yahooTicker' => 'TLKM.JK', 'isActive' => true],
        ['code' => 'ASII', 'name' => 'Astra International Tbk', 'yahooTicker' => 'ASII.JK', 'isActive' => true],
        ['code' => 'UNVR', 'name' => 'Unilever Indonesia Tbk', 'yahooTicker' => 'UNVR.JK', 'isActive' => true],
        ['code' => 'ICBP', 'name' => 'Indofood CBP Sukses Makmur Tbk', 'yahooTicker' => 'ICBP.JK', 'isActive' => true],
        ['code' => 'INDF', 'name' => 'Indofood Sukses Makmur Tbk', 'yahooTicker' => 'INDF.JK', 'isActive' => true],
        ['code' => 'GOTO', 'name' => 'GoTo Gojek Tokopedia Tbk', 'yahooTicker' => 'GOTO.JK', 'isActive' => true],
        ['code' => 'BREN', 'name' => 'Barito Renewables Energy Tbk', 'yahooTicker' => 'BREN.JK', 'isActive' => true],
        ['code' => 'ANTM', 'name' => 'Aneka Tambang Tbk', 'yahooTicker' => 'ANTM.JK', 'isActive' => true],
        ['code' => 'MDKA', 'name' => 'Merdeka Copper Gold Tbk', 'yahooTicker' => 'MDKA.JK', 'isActive' => true],
        ['code' => 'ADRO', 'name' => 'Alamtri Resources Indonesia Tbk', 'yahooTicker' => 'ADRO.JK', 'isActive' => true],
        ['code' => 'PTBA', 'name' => 'Bukit Asam Tbk', 'yahooTicker' => 'PTBA.JK', 'isActive' => true],
        ['code' => 'BRIS', 'name' => 'Bank Syariah Indonesia Tbk', 'yahooTicker' => 'BRIS.JK', 'isActive' => true],
        ['code' => 'ARTO', 'name' => 'Bank Jago Tbk', 'yahooTicker' => 'ARTO.JK', 'isActive' => true],
        ['code' => 'EXCL', 'name' => 'XL Axiata Tbk', 'yahooTicker' => 'EXCL.JK', 'isActive' => true],
        ['code' => 'ISAT', 'name' => 'Indosat Tbk', 'yahooTicker' => 'ISAT.JK', 'isActive' => true],
        ['code' => 'ERAA', 'name' => 'Erajaya Swasembada Tbk', 'yahooTicker' => 'ERAA.JK', 'isActive' => true],
    ];

    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview create/update actions without saving to database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');

        $created = 0;
        $updated = 0;
        $total = count(self::SAMPLE_COMPANIES);

        $io->title('Seeding Sample Companies');

        if ($isDryRun) {
            $io->warning('Dry-run mode enabled. No database changes will be saved.');
        }

        foreach (self::SAMPLE_COMPANIES as $sample) {
            $company = $this->companyRepository->findOneBy(['code' => $sample['code']]);

            if ($company instanceof Company) {
                $this->applyCompanyData($company, $sample, true);
                ++$updated;

                $io->text(sprintf(
                    '[UPDATE] %s - %s - %s',
                    $sample['code'],
                    $sample['name'],
                    $sample['yahooTicker'],
                ));

                continue;
            }

            $company = new Company();
            $this->applyCompanyData($company, $sample, false);
            ++$created;

            if (!$isDryRun) {
                $this->entityManager->persist($company);
            }

            $io->text(sprintf(
                '[CREATE] %s - %s - %s',
                $sample['code'],
                $sample['name'],
                $sample['yahooTicker'],
            ));
        }

        if (!$isDryRun) {
            $this->entityManager->flush();
        }

        $io->newLine();
        $io->definitionList(
            ['created' => (string) $created],
            ['updated' => (string) $updated],
            ['total' => (string) $total],
        );

        $io->success($isDryRun ? 'Dry-run completed.' : 'Sample companies seeded successfully.');

        return Command::SUCCESS;
    }

    /**
     * @param array{code: string, name: string, yahooTicker: string, isActive: bool} $sample
     */
    private function applyCompanyData(Company $company, array $sample, bool $isUpdate): void
    {
        $company
            ->setCode($sample['code'])
            ->setName($sample['name'])
            ->setYahooTicker($sample['yahooTicker'])
            ->setIsActive($sample['isActive']);

        if ($isUpdate) {
            $company->setUpdatedAt(new \DateTimeImmutable());

            if ($this->entityManager->contains($company)) {
                return;
            }

            $this->entityManager->persist($company);
        }
    }
}