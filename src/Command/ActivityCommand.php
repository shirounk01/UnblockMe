<?php

namespace App\Command;

use App\Services\ActivityService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActivityCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:activity-update';
    private $activityService;

    protected function configure(): void
    {
        $this
            ->setDescription('Check an activity.')

            ->setHelp('This command allows you to update an activity...')
        ;
    }

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Activity checker',
            '============',
            '',
        ]);

        $this->activityService->checkActivities();

        return Command::SUCCESS;

    }
}