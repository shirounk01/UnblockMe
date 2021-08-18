<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NewPasswordType;
use App\Form\UserType;
use App\Repository\ActivityRepository;
use App\Repository\UserRepository;
use App\Services\ActivityService;
use App\Services\LicensePlateService;
use App\Services\UserService;
use Doctrine\ORM\EntityRepository;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
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

#[Route('/user')]
class UserController extends AbstractController
{

    #[Route('/password', name: 'password_new', methods: ['GET', 'POST'])]
    public function passwordChange(Request $request, UserPasswordHasherInterface $passwordHasher, SecurityController $security) : Response
    {
        // todo
        $user = new User();
        $form = $this->createForm(NewPasswordType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $newPassword = $form->get('new_password')->getData();

            $entityManager = $this->getDoctrine()->getManager();

            $this->getUser()->setPassword($passwordHasher->hashPassword($this->getUser(), $newPassword));

            $entityManager->persist($this->getUser());
            $entityManager->flush();

            $this->addFlash(
                'success',
                "The password has been successfully changed!"
            );

            return $this->redirectToRoute('home');
        }

        return $this->render('password/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, LicensePlateService $licensePlateService): Response
    {
        //
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            //dd($user);
            $licensePlateService->removeUser($user);
            //dd($user);
            $entityManager->remove($user);

            $session = $this->get('session');
            $session = new Session();
            $session->invalidate();

            $entityManager->flush();

            $message = 'The account was deleted!';
            $this->addFlash(
                'success',
                $message
            );
        }

        //dd($user);
        return $this->redirectToRoute('app_login');
    }

    #[Route('/export/data', name: 'export_data')]
    public function exportData(Request $request, LicensePlateRepository $licensePlateRepository, ActivityRepository $activityRepository, LicensePlateService $licensePlateService): Response
    {
        $encoders = [new XmlEncoder(), new CsvEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $csvContent = $serializer->serialize($this->getUser(), 'csv', [AbstractNormalizer::ATTRIBUTES => ['email', 'roles']]);
        $filesystem = new Filesystem();
        $filesystem->dumpFile('export.txt', $csvContent);
        $csvContent = $serializer->serialize($licensePlateRepository->findBy(['user'=>$this->getUser()]), 'csv', [AbstractNormalizer::ATTRIBUTES => ['licensePlate']]);
        $filesystem->appendToFile('export.txt', $csvContent);

        //dd($licensePlateRepository->findBy(['user'=>$this->getUser()]),$activityRepository->findBy(['blocker'=>$licensePlateRepository->findBy(['user'=>$this->getUser()])]));

        $licensePlates = $licensePlateService->getLicensePlates($this->getUser());
        $csvContent = $serializer->serialize($activityRepository->findBy(['blocker'=>$licensePlates]), 'csv', [AbstractNormalizer::ATTRIBUTES => ['blocker', 'blockee']]);
        $filesystem->appendToFile('export.txt', $csvContent);
        $csvContent = $serializer->serialize($activityRepository->findBy(['blockee'=>$licensePlates]), 'csv', [AbstractNormalizer::ATTRIBUTES => ['blockee', 'blocker']]);
        $filesystem->appendToFile('export.txt', $csvContent);

        $file = 'export.txt';
        $response = new BinaryFileResponse($file);
        $dlFile = $this->getUser()->getUserIdentifier().'.txt';
        $response->headers->set('Content-Type', 'text/plain');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $dlFile
        );
        return $response;
    }

    #[Route('/profile', name: 'profile', methods: ['GET', 'POST'])]
    public function seeProfile(Request $request, UserService $userService) : Response
    {

        return $this->render('user/profile.html.twig', [
            'imageURL' => $userService->getGravatar($this->getUser()->getUserIdentifier()),
            'info' => $userService->getGravatarInfo($this->getUser()->getUserIdentifier()),
        ]);
    }
}
