<?php

namespace App\Controller;

use App\Entity\LicensePlate;
use App\Form\LicensePlate1Type;
use App\Repository\LicensePlateRepository;
use App\Services\ActivityService;
use App\Services\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;


#[Route('/license/plate')]
class LicensePlateController extends AbstractController
{
    #[Route('/', name: 'license_plate_index', methods: ['GET'])]
    public function index(LicensePlateRepository $licensePlateRepository): Response
    {
        return $this->render('license_plate/index.html.twig', [
            'license_plates' => $licensePlateRepository->findBy(['user' => $this->getUser()]),
        ]);
    }

    #[Route('/new', name: 'license_plate_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ActivityService $activity, MailerService $mailer, LicensePlateRepository $repo): Response
    {
        $licensePlate = new LicensePlate();
        $form = $this->createForm(LicensePlate1Type::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           //$licensePlate->setUser(app.user.username);

            $hasUser = $repo->findOneBy(['license_plate'=>$licensePlate->getLicensePlate()]);
            if($hasUser and !$hasUser->getUser())
            {
                $entityManager = $this->getDoctrine()->getManager();
                $hasUser->setUser($this->getUser());
                $entityManager->persist($hasUser);
                $entityManager->flush();
                $blocker = $activity->whoBlockedMe($licensePlate->getLicensePlate());
                $blockee = $activity->iveBlockedSomebody($licensePlate->getLicensePlate());
                if($blocker)
                {
                    $mid = $repo->findOneBy(['license_plate'=>$blocker]);
                    $mailer->sendBlockeeReport($mid->getUser(), $hasUser->getUser(), $mid->getLicensePlate());
                }
                if($blockee)
                {
                    $mid = $repo->findOneBy(['license_plate'=>$blockee]);
                    $mailer->sendBlockerReport($mid->getUser(), $hasUser->getUser(), $mid->getLicensePlate());// blockee, blocker, blockee lp
                }
                return $this->redirectToRoute('license_plate_index');
            }

            $entityManager = $this->getDoctrine()->getManager();
            $licensePlate->setUser($this->getUser());
            $entityManager->persist($licensePlate);
            $entityManager->flush();

            return $this->redirectToRoute('license_plate_index');
        }

        return $this->render('license_plate/new.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'license_plate_show', methods: ['GET'])]
    public function show(LicensePlate $licensePlate): Response
    {
        return $this->render('license_plate/show.html.twig', [
            'license_plate' => $licensePlate,
        ]);
    }

    #[Route('/{id}/edit', name: 'license_plate_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LicensePlate $licensePlate): Response
    {
        $form = $this->createForm(LicensePlate1Type::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('license_plate_index');
        }

        return $this->render('license_plate/edit.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'license_plate_delete', methods: ['POST'])]
    public function delete(Request $request, LicensePlate $licensePlate): Response
    {
        if ($this->isCsrfTokenValid('delete'.$licensePlate->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($licensePlate);
            $entityManager->flush();
        }

        return $this->redirectToRoute('license_plate_index');
    }
}
