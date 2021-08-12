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

    /**
     * @param Activity $activity
     * @return float
     */
    public function getDurationBetweenUpdates(Activity $activity): float
    {
        return abs(strtotime(date('Y-m-d H:i:s')) - strtotime($activity->getCreatedAt()));
    }

    public function checkActivities()
    {
        $allActivities = $this->activityRepo->findAll();
        //dd($allActivities);
        foreach ($allActivities as &$activity)
        {
            $diff = $this->getDurationBetweenUpdates($activity);
            if($diff > 10)
            {
                $activity->setStatus(2);
            }
        }
        $this->em->flush();
    }

}