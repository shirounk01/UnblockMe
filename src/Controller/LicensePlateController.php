<?php

namespace App\Controller;

use App\Services\LicensePlateService;
use PharIo\Manifest\License;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\String\ByteString;
use Symfony\Component\String\CodePointString;
use Symfony\Component\String\UnicodeString;

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
    public function new(Request $request, ActivityService $activity, MailerService $mailer, LicensePlateRepository $repo, LicensePlateService $licensePlateService): Response
    {
        $licensePlate = new LicensePlate();
        $form = $this->createForm(LicensePlate1Type::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           //$licensePlate->setUser(app.user.username);
            // todo
            $licensePlate->setLicensePlate($licensePlateService->formatString($licensePlate->getLicensePlate()));
            $hasUser = $repo->findOneBy(['license_plate'=>$licensePlate->getLicensePlate()]);
            if($hasUser and $hasUser->getUser()==$this->getUser())
            {
                $message = "You have already introduced the car ".$licensePlate->getLicensePlate()."!";
                $this->addFlash(
                    'warning',
                    $message
                );
                return $this->redirectToRoute('license_plate_index');
            }
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
                    foreach($blocker as &$item)
                    {
                        $mid = $repo->findOneBy(['license_plate'=>$item->getBlocker()]);
                        $mailer->sendBlockeeReport($mid->getUser(), $hasUser->getUser(), $mid->getLicensePlate());
                        $message = "Your car has been blocked by ".$mid->getLicensePlate()."!";
                        $this->addFlash(
                            'warning',
                            $message
                        );
                        $item->setStatus(1);
                        $entityManager->persist($item);
                        $entityManager->flush();
                    }
                }
                if($blockee)
                {
                    foreach ($blockee as &$item)
                    {
                        $mid = $repo->findOneBy(['license_plate'=>$item->getBlockee()]);
                        $mailer->sendBlockerReport($mid->getUser(), $hasUser->getUser(), $mid->getLicensePlate());// blockee, blocker, blockee lp
                        $message="You blocked the car ".$mid->getLicensePlate()."!";
                        $this->addFlash(
                            'danger',
                            $message
                        );
                        $item->setStatus(1);
                        $entityManager->persist($item);
                        $entityManager->flush();
                    }

                }

                return $this->redirectToRoute('license_plate_index');
            }

            $entityManager = $this->getDoctrine()->getManager();
            $licensePlate->setUser($this->getUser());
            $entityManager->persist($licensePlate);
            $entityManager->flush();

            $message = 'The car ' . $licensePlate->getLicensePlate() . ' has been added to your account!';
            $this->addFlash(
                'success',
                $message
            );

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
    public function edit(Request $request, LicensePlate $licensePlate, LicensePlateService $licensePlateService, ActivityService $activityService): Response
    {
        $message = "Car ".$licensePlate->getLicensePlate()." has been changed to ";

        if($activityService->whoBlockedMe($licensePlate->getLicensePlate()) or $activityService->iveBlockedSomebody($licensePlate->getLicensePlate()))
        {
            $this->addFlash(
                'warning',
                'You cannot edit your car while it either is blocked or is blocking. Please solve the issues in order to complete the process!'
            );
            return $this->redirectToRoute('license_plate_index');
        }

        $form = $this->createForm(LicensePlate1Type::class, $licensePlate);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $licensePlate->setLicensePlate($licensePlateService->formatString($licensePlate->getLicensePlate()));
            $message = $message . $licensePlate->getLicensePlate();
            $this->addFlash(
                'success',
                $message
            );

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('license_plate_index');
        }

        return $this->render('license_plate/edit.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'license_plate_delete', methods: ['POST'])]
    public function delete(Request $request, LicensePlate $licensePlate, ActivityService $activityService): Response
    {
        if($activityService->whoBlockedMe($licensePlate->getLicensePlate()) or $activityService->iveBlockedSomebody($licensePlate->getLicensePlate()))
        {
            $this->addFlash(
                'warning',
                'You cannot delete your car while it either is blocked or is blocking. Please solve the issues in order to complete the process!'
            );
            return $this->redirectToRoute('license_plate_index');
        }
        if ($this->isCsrfTokenValid('delete'.$licensePlate->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($licensePlate);
            $entityManager->flush();
        }

        return $this->redirectToRoute('license_plate_index');
    }
}
