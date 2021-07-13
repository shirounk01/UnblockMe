<?php
// a blocker reports
namespace App\Form;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Entity\User;
use App\Repository\LicensePlateRepository;
use App\Services\LicensePlateService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Security;

class BlockerType extends AbstractType
{
    private $security;
    private $em;
    private $licensePlateService;

    public function __construct(Security $security, EntityManagerInterface $em, LicensePlateService $licensePlateService)
    {
        $this->security = $security;
        $this->em = $em;
        $this->licensePlateService = $licensePlateService;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if($this->licensePlateService->countLicensePlates($this->security->getUser()) == 1)
        {
            $builder->add('blocker', TextType::class, array('disabled' => true, 'attr' => array('placeholder' => $this->licensePlateService->getFirstLicensePlate($this->security->getUser()))));
        }
        else
        {
            $builder
                ->add('blocker', EntityType::class, [
                    'class' => LicensePlate::class,
                    'query_builder' => function (LicensePlateRepository $er) {
                        return $er->findByUser($this->security->getUser());
                    },
                    'choice_label' => 'license_plate',
                ]);
        }
        $builder->add('blockee');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}
// app.user.username