<?php

namespace App\Controller\Admin;

use App\Entity\Images;
use App\Entity\Products;
use App\Form\ProductsFormType;
use App\Repository\ImagesRepository;
use App\Repository\ProductsRepository;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/produits', name: 'admin_products_')]
class ProductsController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ProductsRepository $productsRepository): Response
    {
        $produits = $productsRepository->findAll();
        return $this->render('admin/products/index.html.twig', compact('produits'));
    }

    #[Route('/ajout', name: 'add')]
    public function add(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, PictureService $pictureService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // On crée un "nouveau produit"
        $product = new Products();
    
        // On crée le formulaire
        $productForm = $this->createForm(ProductsFormType::class, $product);
    
        // On traite la requête du formulaire
        $productForm->handleRequest($request);
    
        // On vérifie si le formulaire est soumis ET valide
        if ($productForm->isSubmitted() && $productForm->isValid()) {
            // On récupère les images
            $images = $productForm->get('images')->getData();
            
            foreach ($images as $image) {
                // On définit le dossier de destination
                $folder = 'products';
    
                // On appelle le service d'ajout
                $fichier = $pictureService->add($image, $folder, 300, 300);
    
                $img = new Images();
                $img->setName($fichier);
                $product->addImage($img);
            }
    
            // On génère le slug
            $slug = $slugger->slug($product->getName());
            $product->setSlug($slug);
    
            // On stocke
            $em->persist($product);
            $em->flush();
    
            $this->addFlash('success', 'Produit ajouté avec succès');
    
            // On redirige
            return $this->redirectToRoute('admin_products_index');
        }
    
        return $this->render('admin/products/add.html.twig', [
            'productForm' => $productForm->createView()
        ]);
    }

    #[Route('/edition/{id}', name: 'edit')]
    public function edit($id, Products $product, Request $request, EntityManagerInterface $em, ProductsRepository $productsRepository, ImagesRepository $imagesRepository, SluggerInterface $slugger, PictureService $pictureService): Response
    {
        // On vérifie si l'utilisateur peut éditer avec le Voter
        $this->denyAccessUnlessGranted('PRODUCT_EDIT', $product);

        // On divise le prix par 100
        // $prix = $product->getPrice() / 100;
        // $product->setPrice($prix);

        // On crée le formulaire
        $productForm = $this->createForm(ProductsFormType::class, $product);

        // On traite la requête du formulaire
        $productForm->handleRequest($request);

        //On vérifie si le formulaire est soumis ET valide
        if($productForm->isSubmitted() && $productForm->isValid()){
            // On récupère les images
            $images = $productForm->get('images')->getData();

            foreach($images as $image){
                // On définit le dossier de destination
                $folder = 'products';
                
                // On appelle le service d'ajout
                $fichier = $pictureService->add($image, $folder, 300, 300);

                $img = new Images();
                $img->setName($fichier);
                $product->addImage($img);
            }

            // On génère le slug
            $slug = $slugger->slug($product->getName());
            $product->setSlug($slug);

            // On arrondit le prix 
            // $prix = $product->getPrice() * 100;
            // $product->setPrice($prix);

            // On stocke
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit modifié avec succès');

            // On redirige
            return $this->redirectToRoute('admin_products_index');
        }

        // return $this->render('admin/products/edit.html.twig',[
        //     'productForm' => $productForm->createView(),
        //     'product' => $product,
        //     'images' => $images
        // ]);
        // Récupère les données du produit en utilisant le slug
        $product = $productsRepository->findOneBy(['id' => $id]);

        // Récupère les données de l'image associée au produit
        $images = $imagesRepository->findOneBy(['products' => $product]);

        return $this->renderForm('admin/products/edit.html.twig', compact('productForm', 'product', 'images'));
        // ['productForm' => $productForm]
    }


    #[Route('/suppression/{id}', name: 'delete')]
    public function delete(Products $product): Response
    {
        // On vérifie si l'utilisateur peut supprimer avec le Voter
        $this->denyAccessUnlessGranted('PRODUCT_DELETE', $product);

        return $this->render('admin/products/index.html.twig');
    }

    #[Route('/suppression/image/{id}', name: 'delete_image')]
    public function deleteImage($id, Images $image, Request $request, EntityManagerInterface $em, PictureService $pictureService): Response
    {
        // On récupère le contenu de la requête
        $data = json_decode($request->getContent(), true);

        $nom = $image->getName();

        if($pictureService->delete($nom, 'products', 300, 300)){
            // On supprime l'image de la base de données
            $em->remove($image);
            $em->flush();
    // Redirection vers la page d'édition du produit avec l'identifiant du produit
            return $this->redirectToRoute('admin_products_edit', ['id' => $image->getProducts()->getId()]);
        } else {
            // La suppression a échoué
            // Redirection vers la page d'édition du produit avec l'identifiant du produit
            return $this->redirectToRoute('admin_products_edit', ['id' => $image->getProducts()->getId()]);
        }
    }
}