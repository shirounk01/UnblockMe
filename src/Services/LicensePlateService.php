<?php


namespace App\Services;

use App\Entity\LicensePlate;
use App\Entity\User;
use App\Repository\LicensePlateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class LicensePlateService
{
    /**
     * @var LicensePlateRepository
     */
    protected $licensePlateRepo;
    private EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->licensePlateRepo = $em->getRepository(LicensePlate::class);
    }

    /**
     * @param string $licensePlate
     * @return string
     */
    public function formatString(string $licensePlate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $licensePlate));
    }

    /**
     * @param User $user
     * @return int
     */
    public function countLicensePlates(User $user): int
    {
        return count($this->licensePlateRepo->findBy(['user' => $user]));
    }

    /**
     * @param User $user
     * @return string|null
     */
    public function getFirstLicensePlate(User $user): ?string
    {
        $isValid = $this->licensePlateRepo->findOneBy(['user' => $user]);
        return ($isValid==null?null:$isValid->getLicensePlate());
    }

    public function getIntervalSeconds(LicensePlate $licensePlate): float
    {
        return abs(strtotime(date('Y-m-d H:i:s')) - strtotime($licensePlate->getUpdatedAt()));
    }

    /**
     * @param User $user
     */
    public function  removeUser(User $user)
    {
        $cars = $this->licensePlateRepo->findBy(['user' => $user]);
        foreach ($cars as &$car)
        {
            $car->setUser(null);
            $this->em->flush();
        }
    }

}