<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use AppBundle\Entity\Product;
use AppBundle\Entity\ShoppingCart;
use AppBundle\Entity\User;
use AppBundle\Form\CategoryType;
use AppBundle\Form\ProductType;
use AppBundle\Form\UpdateProductType;
use AppBundle\Repository\RoleRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function defaultController()
    {
        return $this->render('default/homepage.html.twig');
    }

    /**
     * @Route("/addProduct", name="product-form")
     * @Security("has_role('ROLE_EDITOR')")
     *
     */
    public function addProductAction(Request $request)
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

        return $this->redirectToRoute('product-form');


    }


    /**
     * @param Request $request
     * @param $id
     *
     * @Route("/cart/{id}" , name="cart")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addToCartAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        $cart = new ShoppingCart();
        $cart->setUserID($this->getUser());

        $item = $em->getRepository('AppBundle:Product')->find($id);
        if ($user->getCash() < $item->getPrice()){
            throw new Exception("Not enought money");
        }

        $user->setCash($user->getCash() - $item->getPrice());
        $item->setQuantity($item->getQuantity() - 1);

        $cart->setProductID($item->getId());
        $cart->setUserID($user->getId());
        $cart->setProductName($item->getName());
        $cart->setProductPrice($item->getPrice());

        $em->persist($cart);
        $em->flush();

        return $this->redirectToRoute('homepage');
    }


    /**
     * @Security("has_role('ROLE_EDITOR')")
     * @Route("/allCategories", name="allCategories")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listCategories()
    {
        $em = $this->getDoctrine()->getManager();

        /**
         * @var Category[] $categories
         */
        $categories = $em->getRepository(Category::class)->findAll();

        return $this->render('default/categories.html.twig',[
            'categories' => $categories,
        ]);
    }


    /**
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Security("has_role('ROLE_EDITOR')")
     * @Route("/deleteCategory/{id}" , name="delete_category")
     */
    public function deleteCategory(Request $request,$id)
    {
        $em = $this->getDoctrine()->getManager();
        $products = $em->getRepository(Category::class)->findOneBy(['id' => $id]);

        $em->remove($products);
        $em->flush();

        return $this->redirectToRoute('allCategories');
    }

    /**
     * @Route("/addCategory", name="category-form")
     * @Security("has_role('ROLE_EDITOR')")
     *
     */
    public function addCategoryAction(Request $request)
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class,$category);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();



            $em->persist($category);
            $em->flush();

            $this->addFlash('success','The new category has been added');



            return $this->redirectToRoute('homepage');
        }
        else if($form->isSubmitted() && !$form->isValid()){
            $this->addFlash('error','The category has not been added');

        }


        return $this->render('default/index.html.twig', [
            'productForm' => $form->createView(),
        ]);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @Route("/shoppingcart", name="shoppingcart")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewShoppingCartAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $productsInCart = $em->getRepository(ShoppingCart::class)->findBy(['userID' => $user->getId()]);



        return $this->render('users/shoppingCart.html.twig',['productsInCart' => $productsInCart]);
    }
}
