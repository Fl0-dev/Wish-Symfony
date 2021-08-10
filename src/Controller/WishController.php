<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Wish;
use App\Form\WishType;
use App\Repository\UserRepository;
use App\Repository\WishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/wish", name="wish_")
 */
class WishController extends AbstractController
{
    /**
     * @Route("", name="list")
     */
    public function list(WishRepository $wishRepository): Response
    {

        $wishes = $wishRepository->findBy([], ["dateCreated" => "DESC"]);
        return $this->render('wish/list.html.twig', [
            "wishes" => $wishes,
        ]);
    }

    /**
     * @Route("/detail/{id}", name="detail")
     */
    public function detail(int $id, WishRepository $wishRepository): Response
    {


        $wish = $wishRepository->find($id);

        //s'il n'existe pas en bdd, on déclenche une erreur 404
        if (!$wish) {
            throw $this->createNotFoundException("Ce wish n'existe pas");
        }
        return $this->render('wish/detail.html.twig', [
            "wish" => $wish,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Wish $wish , Request $request): Response
    {
        $form = $this->createForm(WishType::class, $wish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('wish_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('wish/edit.html.twig', [
            'wish' => $wish,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="delete")
     */
    public function delete(Wish $wish, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($wish);
        $entityManager->flush();
        return $this->redirectToRoute('wish_list');
    }

    /**
     * @Route("/create", name="create")
     */
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $wish = new Wish();
        //pour mettre le pseudo directement dans le champ
        $pseudo = $this->getUser()->getPseudo();
        $wish->setAuthor($pseudo);

        $wish->setIsPublished(true);
        $wish->setDateCreated(new \DateTime());
        //création du form de WishType
        $wishForm = $this->createForm(WishType::class, $wish);
        //retour de la requête
        $wishForm->handleRequest($request);

        //si form soumis et valide
        if ($wishForm->isSubmitted() && $wishForm->isValid()) {
            $entityManager->persist($wish);
            $entityManager->flush();

            //message flash
            $this->addFlash('success', 'wish ajouté !');
            return $this->redirectToRoute('wish_detail', ['id' => $wish->getId()]);
        }
        return $this->render('wish/create.html.twig', [
            'wishForm' => $wishForm->createView()
        ]);
    }

    /**
     * @Route ("/{id}/listByUser", name="listByUser")
     */
    public function listByUser(User $user,UserRepository $userRepository):Response
    {
            $user = $userRepository->find($user);
        if (!$user) {
            throw $this->createNotFoundException("Cet(te) auteur(e) n'existe pas");
        }
        return $this->render('wish/listByUser.html.twig', [
            "user" => $user,
        ]);
    }
}
