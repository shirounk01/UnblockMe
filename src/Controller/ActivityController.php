<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NewPasswordType;
use App\Form\UserType;
use App\Repository\ActivityRepository;
use App\Repository\UserRepository;
use App\Services\ActivityService;
use App\Services\LicensePlateService;
use Doctrine\ORM\EntityRepository;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\String\UnicodeString;
use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Form\BlockeeType;
use App\Form\BlockerType;
use App\Repository\LicensePlateRepository;
use App\Services\MailerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity')]
class ActivityController extends AbstractController
{

    #[Route('/', name: 'activity_index', methods: ['GET'])]
    public function index(ActivityRepository $activityRepository, LicensePlateRepository $licensePlateRepository): Response
    {
        $entry = $licensePlateRepository->findBy(['user'=>$this->getUser()]);
        foreach ($entry as &$it)
        {
            $it = $it->getLicensePlate();
        }
        //dd($activityRepository->findBy(['blocker'=>$entry]), $activityRepository->findBy(['blockee'=>$entry]));
        return $this->render('activity/index.html.twig', [
            'blocker' => $activityRepository->findBy(['blocker'=>$entry, 'status' => [0,1,2]]),
            'blockee' => $activityRepository->findBy(['blockee'=>$entry, 'status' => [0,1,2]])
        ]);
    }

    #[Route('/{blocker}/{blockee}', name: 'activity_delete', methods: ['POST'])]
    public function delete(Request $request, Activity $activity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activity->getBlocker(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $activity->setStatus(3);
            //$entityManager->remove($activity);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'The issue has been solved!'
            );

        }

        return $this->redirectToRoute('activity_index');
    }

    #[Route('/blocker', name: 'blocker', methods: ['GET', 'POST'])]
    public function iveBlockedSomeone(Request $request, LicensePlateRepository $licensePlate, MailerService $mailer, LicensePlateService $licensePlateService): Response
    {
        $activity = new Activity();
        $form = $this->createForm(BlockerType::class, $activity);
        $entry = $licensePlateService->getFirstLicensePlate($this->getUser());
        if($entry)
        {
            $activity->setBlocker($entry);
        }
        else
        {
            $this->addFlash(
                'warning',
                'No cars have been found for your account!'
            );
            return $this->redirectToRoute('home');
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$licensePlate->setUser(app.user.username);
            //$activity->setBlocker($licensePlate->findOneBy(['user'=>$this->getUser()])->getLicensePlate());
            //todo
            $entityManager = $this->getDoctrine()->getManager();
            $activity->setBlockee($licensePlateService->formatString($activity->getBlockee()));
            $activity->setBlocker($licensePlateService->formatString($activity->getBlocker()));
            $entityManager->persist($activity);
            try{
                $entityManager->flush();
            }catch (\Exception $e)
            {
                $this->addFlash(
                    'warning',
                    'This issue is already registered.'
                );
                return $this->redirectToRoute('home');
            }
            $new = $licensePlate->findOneBy(['license_plate'=>$activity->getBlockee()]);
            if($new)
            {
                if($new->getUser())
                {

                    $blocker = $licensePlate->findOneBy(['license_plate'=>$activity->getBlocker()]);
                    $mailer->sendBlockeeReport($blocker->getUser(),$new->getUser(), $blocker->getLicensePlate());
                    $activity->setStatus(1);
                    $message = "The owner of the car ".$activity->getBlockee()." has been emailed!";
                    $this->addFlash(
                        'success',
                        $message
                    );
                }
                else
                {
                    $message = "The owner of the car ".$activity->getBlockee()." is not registered! They will be contacted as soon as they are registered!";
                    $this->addFlash(
                        'warning',
                        $message
                    );
                }
            }
            else
            {
                $licensePlate = new LicensePlate();
                $entityManager = $this->getDoctrine()->getManager();
                $licensePlate->setLicensePlate($activity->getBlockee());
                $entityManager->persist($licensePlate);
                $entityManager->flush();
                $message = "The owner of the car ".$activity->getBlockee()." is not registered! They will be contacted as soon as they are registered!";
                $this->addFlash(
                    'warning',
                    $message
                );
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
    public function someoneBlcokedMe(Request $request, LicensePlateRepository $licensePlate, MailerService $mailer, LicensePlateService $licensePlateService): Response
    {
        $activity = new Activity();
        $form = $this->createForm(BlockeeType::class, $activity);
        $entry = $licensePlateService->getFirstLicensePlate($this->getUser());

        if($entry)
        {
            $activity->setBlockee($entry);
        }
        else
        {
            $this->addFlash(
                'warning',
                'No cars have been found for your account!'
            );
            return $this->redirectToRoute('home');
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$licensePlate->setUser(app.user.username);
            //$activity->setBlocker($licensePlate->findOneBy(['user'=>$this->getUser()])->getLicensePlate());

            //todo
            $entityManager = $this->getDoctrine()->getManager();
            $activity->setBlockee($licensePlateService->formatString($activity->getBlockee()));
            $activity->setBlocker($licensePlateService->formatString($activity->getBlocker()));

            $entityManager->persist($activity);
            try {
                $entityManager->flush();
            }catch (\Exception $e)
            {
                $this->addFlash(
                    'warning',
                    'This issue is already registered.'
                );
                return $this->redirectToRoute('home');
            }
            $new = $licensePlate->findOneBy(['license_plate'=>$activity->getBlocker()]);

            if($new)
            {
                if($new->getUser())
                {
                    $blockee = $licensePlate->findOneBy(['license_plate'=>$activity->getBlockee()]);
                    $mailer->sendBlockerReport($blockee->getUser(),$new->getUser(), $blockee->getLicensePlate()); // blockee, blocker, blockee lp
                    $activity->setStatus(1);
                    $message = "The owner of the car ".$activity->getBlocker()." has been emailed!";
                    $this->addFlash(
                        'success',
                        $message
                    );
                }
                else
                {
                    $message = "The owner of the car ".$activity->getBlocker()." is not registered! They will be contacted as soon as they are registered!";
                    $this->addFlash(
                        'warning',
                        $message
                    );
                }
            }
            else
            {
                $licensePlate = new LicensePlate();
                $entityManager = $this->getDoctrine()->getManager();
                $licensePlate->setLicensePlate($activity->getBlocker());
                $entityManager->persist($licensePlate);
                $entityManager->flush();
                $message = "The owner of the car ".$activity->getBlocker()." is not registered! They will be contacted as soon as they are registered!";
                $this->addFlash(
                    'warning',
                    $message
                );
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
