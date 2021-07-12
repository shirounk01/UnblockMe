<?php
// a blocker reports
namespace App\Form;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Entity\User;
use App\Repository\LicensePlateRepository;
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
    private $security; // bless this
    private $em;

    public function __construct(Security $security, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $repo = $this->em->getRepository('App:LicensePlate');
        $blockedCars = $repo->findBy(['user'=>$this->security->getUser()]);
        if(count($blockedCars)==1){
            $builder->add('blocker', TextType::class, array('disabled' => true, 'attr' => array('placeholder' => (string)$blockedCars[0])));
        }
        else{
            $builder->add('blocker', EntityType::class, [
                'class' => LicensePlate::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('u.user = :val')
                        ->setParameter('val', $this->security->getUser());
                },
                'choice_label' => 'license_plate'
            ]);
        }
        $builder
            ->add('blockee')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}
// app.user.username