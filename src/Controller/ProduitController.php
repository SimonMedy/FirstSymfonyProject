<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use Doctrine\DBAL\Schema\View;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProduitController extends AbstractController
{
    #[Route('/produits', name: 'app_produits')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $produits = $em->getRepository(Produit::class)->findAll();
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            //le formulaire envoyé est valide
            $em->persist($produit);
            $em->flush();
            return $this->redirectToRoute('app_produits');
            $this->addFlash('success', 'Produit ajouté avec succès !');
        }

        return $this->render('produit/index.html.twig', [
            'form' => $form->createView(),
            'controller_name' => 'ProduitController',
            'produits' => $produits,
        ]);
    }

    #[Route('/produits/{id}', 'produit', methods:['GET', 'POST'],requirements: ['id' => '\d+'])]
    public function detail(int $id, EntityManagerInterface $em, Request $request): Response{
        $produit = $em->getRepository(Produit::class)->find($id);
        if($produit == null){
            return $this->render('/produit/produit_notfound.html.twig');
        }

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em->persist($produit);
            $em->flush();
            $this->addFlash('success', 'Produit modifiée avec succès !');
            return $this->redirect("/home");
        }
        
        return $this->render('/produit/produit_detail.html.twig',[
            'form' => $form->createView(),
            'id' => $id,
            'produit' => $produit,
        ]);
    }

    #[Route('produit/delete/{id}', 'deleteProduit', methods:['POST', 'GET'],requirements: ['id' => '\d+'])]
    public function deleteCategorie(Produit $produit = null, EntityManagerInterface $em, Request $request){
        //$categorie = $em->getRepository(Categorie::class)->find($);
        if($produit === null){
            return $this->render('/produit/produit_notfound.html.twig');
        }

        $em->remove($produit);
        $em->flush();
        $this->addFlash('success', 'Produit supprimé avec succès !');
        return $this->redirectToRoute('app_produits');    
    }

}
