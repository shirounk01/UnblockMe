<?php


namespace App\Services;


use Doctrine\ORM\EntityManagerInterface;

class UserService
{

    private EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getGravatar( $email, $s = 150, $d = 'mp', $r = 'g', $img = false, $atts = [] ) {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5( strtolower( trim( $email ) ) );
        $url .= "?s=$s&d=$d&r=$r";
        if ( $img ) {
            $url = '<img src="' . $url . '"';
            foreach ( $atts as $key => $val ) {
                $url .= ' ' . $key . '="' . $val . '"';
            }
            $url .= ' />';
        }
        return $url;
    }

    public function getGravatarInfo($email){
        $url = 'https://www.gravatar.com/' . md5( strtolower( trim( $email ) ) ) . '.php';
        $str = file_get_contents( $url);
        $profile = unserialize( $str );
        if ( is_array( $profile ) && isset( $profile['entry'] ) )
            return $profile['entry'][0];
    }

}