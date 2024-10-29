<?php

namespace Pantono\Customers;

use Pantono\Customers\Repository\CompaniesRepository;
use Pantono\Hydrator\Hydrator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Customers\Model\Company;
use Pantono\Customers\Model\CompanyStatus;
use Pantono\Customers\Event\PreCompanySaveEvent;
use Pantono\Customers\Event\PostCompanySaveEvent;
use Pantono\Storage\Model\StoredFile;
use Pantono\Customers\Filter\CompanyFilter;

class Companies
{
    private CompaniesRepository $repository;
    private Hydrator $hydrator;
    private EventDispatcher $dispatcher;

    public function __construct(CompaniesRepository $repository, Hydrator $hydrator, EventDispatcher $dispatcher)
    {
        $this->repository = $repository;
        $this->hydrator = $hydrator;
        $this->dispatcher = $dispatcher;
    }

    public function getCompanyById(int $id): ?Company
    {
        return $this->hydrator->hydrate(Company::class, $this->repository->getCompanyById($id));
    }

    public function getCompanyStatusById(int $id): ?CompanyStatus
    {
        return $this->hydrator->hydrate(CompanyStatus::class, $this->repository->getCompanyStatusById($id));
    }

    /**
     * @return StoredFile[]
     */
    public function getFilesForCompany(int $companyId): array
    {
        return $this->hydrator->hydrateSet(StoredFile::class, $this->repository->getFilesForCompany($companyId));
    }

    /**
     * @return Company[]
     */
    public function getCompaniesByFilter(CompanyFilter $filter): array
    {
        return $this->hydrator->hydrateSet(Company::class, $this->repository->getCompaniesByFilter($filter));
    }

    public function saveCompany(Company $company): void
    {
        $previous = $company->getId() ? $this->getCompanyById($company->getId()) : null;
        $event = new PreCompanySaveEvent();
        $event->setCurrent($company);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);

        $this->repository->saveCompany($company);

        $event = new PostCompanySaveEvent();
        $event->setCurrent($company);
        $event->setPrevious($previous);
        $this->dispatcher->dispatch($event);
    }
}
