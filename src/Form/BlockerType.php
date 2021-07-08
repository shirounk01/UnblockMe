<?php
// a blocker reports
namespace App\Form;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Entity\User;
use App\Repository\LicensePlateRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Security;

class BlockerType extends AbstractType
{
    private $security; // bless this

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //todo
        $builder
            ->add('blocker', EntityType::class, [
                'class' => LicensePlate::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('u.user = :val')
                        ->setParameter('val', $this->security->getUser());
                },
                'choice_label' => 'license_plate',
            ])
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