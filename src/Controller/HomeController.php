<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Form\ActivityType;
use App\Form\BlockeeType;
use App\Form\BlockerType;
use App\Repository\LicensePlateRepository;
use App\Services\MailerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

//    #[Route('/report/blocked', name: 'rep_blocker')]
//    public function blocked(): Response
//    {
//        return $this->render('report/report_blocked.html.twig');
//    }

    #[Route('/blocker', name: 'blocker', methods: ['GET', 'POST'])]
    public function iveBlockedSomeone(Request $request, LicensePlateRepository $licensePlate, MailerService $mailer): Response
    {
        $activity = new Activity();
        $form = $this->createForm(BlockerType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$licensePlate->setUser(app.user.username);
            //$activity->setBlocker($licensePlate->findOneBy(['user'=>$this->getUser()])->getLicensePlate());
            $new = $licensePlate->findOneBy(['license_plate'=>$activity->getBlockee()]);
            if($new)
            {
                $blocker = $licensePlate->findOneBy(['license_plate'=>$activity->getBlocker()]);
                $mailer->sendBlockeeReport($blocker->getUser(),$new->getUser(), $blocker->getLicensePlate());
            }
            else
            {
                $licensePlate = new LicensePlate();
                $entityManager = $this->getDoctrine()->getManager();
                $licensePlate->setLicensePlate($activity->getBlockee());
                $entityManager->persist($licensePlate);
                $entityManager->flush();
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($activity);
            $entityManager->flush();
            return $this->redirectToRoute('home');
        }

        return $this->render('blocker/new.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/blockee', name: 'blockee', methods: ['GET', 'POST'])]
    public function someoneBlcokedMe(Request $request, LicensePlateRepository $licensePlate, MailerService $mailer): Response
    {
        $activity = new Activity();
        $form = $this->createForm(BlockeeType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$licensePlate->setUser(app.user.username);
            //$activity->setBlocker($licensePlate->findOneBy(['user'=>$this->getUser()])->getLicensePlate());
            $new = $licensePlate->findOneBy(['license_plate'=>$activity->getBlocker()]);
            if($new)
            {
                $blockee = $licensePlate->findOneBy(['license_plate'=>$activity->getBlockee()]);
                $mailer->sendBlockerReport($blockee->getUser(),$new->getUser(), $blockee->getLicensePlate()); // blockee, blocker, blockee lp
            }
            else
            {
                $licensePlate = new LicensePlate();
                $entityManager = $this->getDoctrine()->getManager();
                $licensePlate->setLicensePlate($activity->getBlocker());
                $entityManager->persist($licensePlate);
                $entityManager->flush();
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($activity);
            $entityManager->flush();
            return $this->redirectToRoute('home');
        }

        return $this->render('blockee/new.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);
    }

}
