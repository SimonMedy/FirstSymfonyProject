<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategorieController extends AbstractController
{
    #[Route('/categorie', 'app_categories', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $em, Request $request, SluggerInterface $slugger): Response
    {
        $categories = $em->getRepository(Categorie::class)->findAll();
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //le formulaire envoyé est validé

            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newImageName = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                // Move the file to the directory where brochures are stored
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newImageName
                    );
                } catch (FileException $e) {
                    $this->addFlash('exception', 'Upload du fichier a échoué!');
                    // ... handle exception if something happens during file upload
                }
                $categorie->setImage($newImageName);
            }
            $em->persist($categorie);
            $em->flush();
            return $this->redirectToRoute('app_categories');
            $this->addFlash('success', 'Categorie ajoutée avec succès !');
        }

        //$searchForm = $this->createFormBuilder();

        //dd($categories);
        return $this->render('categories.html.twig', [
            'form' => $form->createView(),
            'categories' => $categories
        ]);
    }

    #[Route('/categorie/{id}', 'categorie', methods: ['GET', 'POST'],requirements: ['id' => '\d+'])]
    public function detail(int $id, EntityManagerInterface $em, Request $request, SluggerInterface $slugger): Response
    {
        $categorie = $em->getRepository(Categorie::class)->find($id);
        if ($categorie == null) {
            return $this->render('categorie_notfound.html.twig');
        }

        $previousImgName = $categorie->getImage();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newImageName = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                // Move the file to the directory where brochures are stored
                $filesystem = new Filesystem();
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newImageName
                    );
                    if ($previousImgName) {
                        $filesystem->remove($this->getParameter('images_directory') . '/' . $previousImgName);
                    }
                } catch (FileException $e) {
                    $this->addFlash('exception', 'Upload du fichier a échoué!');
                    // ... handle exception if something happens during file upload
                }
                $categorie->setImage($newImageName);
            }

            $em->persist($categorie);
            $em->flush();
            $this->addFlash('success', 'Categorie modifiée avec succès !');
            return $this->redirect("/home");
        }

        return $this->render('categorie_detail.html.twig', [
            'form' => $form->createView(),
            'id' => $id,
            'categorie' => $categorie,
        ]);
    }

    #[Route('categorie/delete/{id}', 'deleteCategorie', methods: ['POST', 'GET'])]
    public function deleteCategorie(Categorie $categorie = null, EntityManagerInterface $em, Request $request)
    {
        //$categorie = $em->getRepository(Categorie::class)->find($);
        if ($categorie === null) {
            return $this->render('home.html.twig');
        }

        $imageName = $categorie->getImage();

        if ($imageName) {
            $filesystem = new Filesystem();
            try {
                $filesystem->remove($this->getParameter('images_directory') . '/' . $imageName);
            } catch (FileException $e) {
                $this->addFlash('exception', 'Upload du fichier a échoué!');
            }
        }

        $em->remove($categorie);
        $em->flush();
        $this->addFlash('success', 'Categorie supprimée avec succès !');
        return $this->redirectToRoute('app_categories');
    }
}
