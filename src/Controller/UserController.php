<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Mailer;
use App\Form\ResetPassType;
use App\Form\UserCreateType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

#[Route('/user', name: 'user_')]
class UserController extends AbstractController
{
    public function __construct(
        private Mailer $mailer
    ){}

    #[Route('/inscription', name: 'subscribe')]
    public function subscribe(Request $request, UserPasswordHasherInterface $passwordEncoder, UserRepository $userRepository): Response
    {
        if ($this->getUser()) {
            $this->addFlash("warning", "Vous avez déjà un compte !");
            return $this->redirectToRoute('homepage');
        }

        $user = new User();
        $form = $this->createForm(UserCreateType::class, $user);
        $form->handleRequest($request);
        

        if($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('password')->getData();
            $encodedPassword = $passwordEncoder->hashPassword($user, $plainPassword); 
            $user->setPassword($encodedPassword);
            $user->setToken($this->generateToken());
            $message = 'merci de confirmer votre addresse email';
            $template ='user/confirm-account.html.twig';

            
            $this->mailer->sendEmail($user->getEmail(), $user->getToken(),$message, $template);
            $userRepository->save($user, true);

            $this->addFlash("success", "Votre compte a bien été créé ! Merci de vous authentifier.");
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/subscribe.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @param string $token
     */
    #[Route('/confirmation-email/{token}', name: 'confirm_account')]
    public function confirmAccount(string $token, UserRepository $userRepository)
    {
        $user = $userRepository->findOneBy(["token" => $token]);
        
        if(!$user) {
            throw $this->createNotFoundException("Ce compte n'exsite pas");
        }
        
        $user->setToken(null);
        $user->setIsVerified(true);
        $userRepository->save($user, true);
        $this->addFlash("success", "Compte actif !"); 
        return $this->redirectToRoute("homepage");           
        
    }

    #[Route('/send-back', name: 'send__back')]
    public function sendBack()
    {
        /** @var $user instanceof User */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash("error", "Vous devez être connecté pour accéder à cette page");
            return $this->redirectToRoute('homepage');
        }
        
        
        if($user->isIsVerified()){
            $this->addFlash('warning', 'Cet utilisateur est déjà activé');
            return $this->redirectToRoute('homepage');    
        }

            // $user->setToken($this->generateToken());
            $message = 'merci de confirmer votre addresse email';
            $template ='user/confirm-account.html.twig';

            // do anything else you need here, like send an email

            $this->mailer->sendEmail($user->getEmail(), $user->getToken(),$message, $template);

            $this->addFlash("success", "Un email vous été envoyé. Merci de valider votre compte !");
            return $this->redirectToRoute('homepage');
    }

    #[Route('/mot-passe-oublié', name: 'forgotten_password')]
    public function forgottenPassword (Request $request, TokenGeneratorInterface $tokenGenerator, UserRepository $userRepository) {
        $form = $this->createForm(ResetPassType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var User $user */
            $user = $userRepository->findOneByEmail($data['email']);
            
            if (!$user) {
                $this->addFlash('danger', 'Cette adresse n\'existe pas');
                $this->redirectToRoute('home');
            }

            $token = $tokenGenerator->generateToken();

            try {
                $user->setToken($token);
                $userRepository->save($user, true);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Une erreur est survenue : ' . $e->getMessage());
                return $this->redirectToRoute('app_login');
            }

            // $url = $this->generateUrl('password_reset', ['token' => $token]);
            $message = 'mot de passe oublié';
            $template ='user/password-reset.html.twig';

            $this->mailer->sendEmail($user->getEmail(), $user->getToken(), $message, $template);
            $this->addFlash("message", "Un e-mail de réinitialisation de mot de passe vous a été envoyé"); 
            
            return $this->redirectToRoute("app_login");
        }
        return $this->render('user/forgotten_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param string $token
     */
    #[Route('/password-reset/{token}', name: 'password_reset')]
    public function resetPassword(string $token, Request $request, UserPasswordHasherInterface $passwordEncoder, UserRepository $userRepository)
    {
        /** @var User $user */
        $user = $userRepository->findOneBy(['token' => $token]);
        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);

        if(!$user) {
            $this->addFlash('danger', 'token inconnu');
            return $this->redirectToRoute('app_login');
        }
        
        if($form->isSubmitted() && $form->isValid()) {
            $user->setToken(null);
            $plainPassword = $form->get('password')->getData();
            $encodedPassword = $passwordEncoder->hashPassword($user, $plainPassword); 
            $user->setPassword($encodedPassword);
            $userRepository->save($user, true);

            $this->addFlash('success', 'Mot de passe modifié avec succés');
            $this->redirectToRoute('app_login');
        }
        
        return $this->render('user/reset.html.twig', 
            [
                'form' => $form->createView(),
                'token' => $token,
            ]
        );
    }

    #[Route('/change-password', name: 'change_password')]
    #[IsGranted('ROLE_USER')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordEncoder) 
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserPasswordUpdateType::class, $user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('newPassword')->getData();
            $encodedPassword = $passwordEncoder->hashPassword($user, $plainPassword); 
            $user->setPassword($encodedPassword);

            $this->entityManager->flush();
            $this->addFlash("success", "Le mot de passe à bien été modifié");
            return $this->redirectToRoute("homepage");
        }

        return $this->render(
            'user/change-password.html.twig',
            [
                "form" => $form->createView()
            ]
        );
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user, true);
        }

            $this->addFlash("success", "Votre compte a bien été supprimé");
            return $this->redirectToRoute('homepage', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
