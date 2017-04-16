<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ArticleController extends Controller
{
    /**
     * @Route("/nonsecured", name="nonsecured")
     */
    public function nonSecuredMethod()
    {
        var_dump("unsecured hello"); exit();
    }

    /**
     * @Route("/secured", name="secured")
     * @Security("has_role('ROLE_USER')")
     */
    public function secured()
    {
        var_dump("hello from security");exit;
    }
}
