<?php


namespace App\Services;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;

class ActivityService
{
    /**
     * @var ActivityRepository
     */
    protected $activityRepo;
    private EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->activityRepo = $em->getRepository(Activity::class);
    }

    /**
     * @param string $licensePlate
     * @return array|null
     */
    public function iveBlockedSomebody(string $licensePlate): ?array
    {
        $blockees = $this->activityRepo->findByBlocker($licensePlate);
        return (count($blockees)?$blockees:null);
    }

    /**
     * @param string $licensePlate
     * @return array|null
     */
    public function whoBlockedMe(string $licensePlate): ?array
    {
        $blockers = $this->activityRepo->findByBlockee($licensePlate);
        return (count($blockers)?$blockers:null);
    }
}