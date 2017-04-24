<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use AppBundle\Form\ProductType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function defaultController()
    {
        return $this->render('base.html.twig');
    }

    /**
     * @Route("/addProduct", name="product-form")
     * @Security("has_role('ROLE_EDITOR')")
     *
     */
    public function AddProductAction(Request $request)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class,$product);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
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
        else if($form->isSubmitted() && !$form->isValid()){
            $this->addFlash('error','The product is not on the market');

        }


        return $this->render('default/index.html.twig', [
            'productForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/products", name="products")
     *
     */
    public function listProductsAction()
    {
        $em = $this->getDoctrine()->getManager();

        /**
         * @var Product[] $products
         */
        $products = $em->getRepository(Product::class)->findAll();

        return $this->render('default/products.html.twig',[
            'products' => $products,
        ]);

    }

    /**
     * @Route("/category/{id}", name="view_category")
     */
    public function viewCategoryAction($id,Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $products = $em->getRepository(Product::class)->findBy(['category' => $id]);
        $productsToReturn =[];

        foreach ($products as $product){
            if($product->getQuantity() > 0 && $product->getIsAvailable() == '1'){
                $productsToReturn[] = $product;
            }
        }

       return $this->render('default/products.html.twig',['products' => $productsToReturn,]);
    }

    /**
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Security("has_role('ROLE_EDITOR')")
     * @Route("/delete/{id}" , name="delete")
     */
    public function deleteProductAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $products = $em->getRepository(Product::class)->findOneBy(['id' => $id]);

        $em->remove($products);
        $em->flush();

        return $this->redirectToRoute('homepage');
    }
}
