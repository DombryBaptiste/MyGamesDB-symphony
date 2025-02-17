<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\{TextType, EmailType, PasswordType};
use App\Twig\Extension\AppExtension;

class ConnexionController extends AbstractController
{
    //#[Route('/connexion', name: 'app_connexion')]
    public function index(SessionInterface $session, EntityManagerInterface $em, Request $request): Response
    {

        if($session->get('isConnected')){
            return $this->redirectToRoute('app_home');
        }

        $formBar = $this->createFormBuilder()
            ->add('search', TextType::class,
                ['row_attr' => ['class' => 'search_bar']])
            ->getForm();
        $formBar->handleRequest($request);
        if($formBar->isSubmitted() && $formBar->isValid()) {
            $data = $formBar->getData();
            return $this->redirectToRoute('app_search', ['string' => $data['search']]);
        }

        $form = $this->createFormBuilder()
                ->add('email', EmailType::class, 
                    ['label' => 'Email :',
                    'row_attr' => ['class' => 'rowForm']])
                ->add('password', PasswordType::class, ['label' => 'Mot de passe :',
                    'row_attr' => ['class' => 'rowForm']])
                ->getForm()
            ;

            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();

                $userEmailConnexion = $data['email'];
                $userPasswordConnexion = $data['password'];

                if($this->isUsedEmail($em, $data)){
                    if($this->isGoodPassword($em, $data)){
                        $sessionID = $this->getSessionId($em, $data);
                        $session->set('UserID', $sessionID);
                        $session->set('isConnected', true);
                        $pseudo = $this->getPseudoWithEmail($em, $data);
                        $session->set('userPseudo', $pseudo);
                        $session->set('userEmail', $userEmailConnexion);
                        return $this->redirectToRoute('app_home');
                    } else {
                        $this->addFlash('error', 'Mot de passe invalide.');
                        return $this->render('connexion/index.html.twig', ['formBar' => $formBar->createView(), 'session' => $session, 'form' => $form->createView()]);
                    }
                } else {
                    $this->addFlash('error', 'Aucun compte est associé a cet email.');
                    return $this->render('connexion/index.html.twig', ['formBar' => $formBar->createView(), 'session' => $session, 'form' => $form->createView()]);
                }
            }
        return $this->render('connexion/index.html.twig', ['formBar' => $formBar->createView(), 'session' => $session, 'form' => $form->createView()]);
        
        
    }

    public function disconect(SessionInterface $session): Response 
    {

        if($session->get('isConnected')) {
            $session->set('isConnected', false);
             return $this->redirectToRoute('app_home');
        } else {
            return $this->redirectToRoute('app_home');
        }
    }

    // OUTILS

    private function isUsedEmail(EntityManagerInterface $em, Array $data): bool{
        $repo = $em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => $data['email']]);
        if(isset($user)){
            return true;
        } else {
            return false;
        } 
    }

    private function isGoodPassword(EntityManagerInterface $em, Array $data): bool {
        $repo = $em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => $data['email']]);
        if(sha1($data['password']) == $user->getPassword()){
            return true;
        } else {
            return false;
        }
    }

    private function getPseudoWithEmail(EntityManagerInterface $em, array $data): string {
        $repo = $em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => $data['email']]);
        return $user->getPseudo();
    }

    private function getSessionId(EntityManagerInterface $em, array $data){
        $repo = $em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => $data['email']]);
        return $user->getId();
    }
}
