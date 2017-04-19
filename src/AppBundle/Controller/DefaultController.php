<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use AppBundle\Form\ProductType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="product-form")
     */
    public function indexAction(Request $request)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class,$product);

        $form->handleRequest($request);

        if($form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $product->setOwner($this->getUser());

            $file = $product->getImagePath();
            $path = '/../web/images/items/';
            $filename = md5($product->getName() . $product->getCategory());
            $file->move(
                $this->get('kernel')->getRootDir() . $path,
                $filename . '.png'
            );
            $product->setImagePath('images/items/' . $filename . '.png');

            $em->persist($product);
            $em->flush();

            $this->addFlash('success','The product is on the market');



            return $this->redirectToRoute('product-form');
        }

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'productForm' => $form->createView(),
        ]);
    }
}
