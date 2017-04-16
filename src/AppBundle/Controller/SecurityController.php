<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="our_login")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function login()
    {
        return $this->render('security/login.html.twig');
    }
}