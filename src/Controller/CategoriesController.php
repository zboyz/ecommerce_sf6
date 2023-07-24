<?php

namespace App\Controller;

use App\Repository\CategoriesRepository;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/categories', name: 'categories_')]
class CategoriesController extends AbstractController
{
    #[Route('/{slug}', name: 'list')]
    public function list($slug, CategoriesRepository $categoriesRepository,  ProductsRepository $productsRepository, Request $request): Response
    {
        $category = $categoriesRepository->findOneBy(['slug' => $slug]);
        
        //On va chercher le numéro de page dans l'url
        $page = $request->query->getInt('page', 1);

        //On va chercher la liste des produits de la catégorie
        $products = $productsRepository->findProductsPaginated($page, $category->getSlug(), 4);

        return $this->render('categories/list.html.twig', compact('category','products'));
    
        // La fonction compact fait exactement la même chose que :
        //return $this->render('categories/list.html.twig', [
        //    'category' => $category,
        //    'products' => $products
        //])
    }
}
