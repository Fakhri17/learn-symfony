<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockDataStagingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockDataStagingRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'IDX_STOCK_DATA_STAGING_CODE', columns: ['code'])]
#[ORM\Index(name: 'IDX_STOCK_DATA_STAGING_SOURCE', columns: ['source'])]
#[ORM\Index(name: 'IDX_STOCK_DATA_STAGING_STATUS', columns: ['status'])]
#[ORM\Index(name: 'IDX_STOCK_DATA_STAGING_FETCHED_AT', columns: ['fetched_at'])]
#[ORM\Table(name: 'stock_data_staging')]
class StockDataStaging
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Company $company = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ticker = null;

    #[ORM\Column(length: 50, options: ['default' => 'yahoo_finance'])]
    private string $source = 'yahoo_finance';

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column(name: 'open_price', nullable: true)]
    private ?float $openPrice = null;

    #[ORM\Column(name: 'high_price', nullable: true)]
    private ?float $highPrice = null;

    #[ORM\Column(name: 'low_price', nullable: true)]
    private ?float $lowPrice = null;

    #[ORM\Column(nullable: true, type: 'bigint')]
    private ?string $volume = null;

    #[ORM\Column(name: 'pe_ratio', nullable: true)]
    private ?float $peRatio = null;

    #[ORM\Column(nullable: true)]
    private ?float $eps = null;

    #[ORM\Column(name: 'market_cap', nullable: true)]
    private ?float $marketCap = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(name: 'market_time', nullable: true)]
    private ?\DateTimeImmutable $marketTime = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_PENDING])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'json', nullable: true, name: 'raw_payload')]
    private ?array $rawPayload = null;

    #[ORM\Column(type: 'json', nullable: true, name: 'missing_fields')]
    private ?array $missingFields = null;

    #[ORM\Column(type: 'text', nullable: true, name: 'error_message')]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'fetched_at')]
    private ?\DateTimeImmutable $fetchedAt = null;

    public function __construct()
    {
        $this->fetchedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->fetchedAt ??= new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getTicker(): ?string
    {
        return $this->ticker;
    }

    public function setTicker(?string $ticker): static
    {
        $this->ticker = $ticker;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getOpenPrice(): ?float
    {
        return $this->openPrice;
    }

    public function setOpenPrice(?float $openPrice): static
    {
        $this->openPrice = $openPrice;

        return $this;
    }

    public function getHighPrice(): ?float
    {
        return $this->highPrice;
    }

    public function setHighPrice(?float $highPrice): static
    {
        $this->highPrice = $highPrice;

        return $this;
    }

    public function getLowPrice(): ?float
    {
        return $this->lowPrice;
    }

    public function setLowPrice(?float $lowPrice): static
    {
        $this->lowPrice = $lowPrice;

        return $this;
    }

    public function getVolume(): ?string
    {
        return $this->volume;
    }

    public function setVolume(?string $volume): static
    {
        $this->volume = $volume;

        return $this;
    }

    public function getPeRatio(): ?float
    {
        return $this->peRatio;
    }

    public function setPeRatio(?float $peRatio): static
    {
        $this->peRatio = $peRatio;

        return $this;
    }

    public function getEps(): ?float
    {
        return $this->eps;
    }

    public function setEps(?float $eps): static
    {
        $this->eps = $eps;

        return $this;
    }

    public function getMarketCap(): ?float
    {
        return $this->marketCap;
    }

    public function setMarketCap(?float $marketCap): static
    {
        $this->marketCap = $marketCap;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getMarketTime(): ?\DateTimeImmutable
    {
        return $this->marketTime;
    }

    public function setMarketTime(?\DateTimeImmutable $marketTime): static
    {
        $this->marketTime = $marketTime;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRawPayload(): ?array
    {
        return $this->rawPayload;
    }

    public function setRawPayload(?array $rawPayload): static
    {
        $this->rawPayload = $rawPayload;

        return $this;
    }

    public function getMissingFields(): ?array
    {
        return $this->missingFields;
    }

    public function setMissingFields(?array $missingFields): static
    {
        $this->missingFields = $missingFields;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getFetchedAt(): ?\DateTimeImmutable
    {
        return $this->fetchedAt;
    }

    public function setFetchedAt(\DateTimeImmutable $fetchedAt): static
    {
        $this->fetchedAt = $fetchedAt;

        return $this;
    }
}