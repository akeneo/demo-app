<?php

declare(strict_types=1);

namespace App\Controller;

use App\Validator\ReachableUrl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class WelcomeFromOutsideAction extends AbstractController
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    #[Route('/welcome-from-outside', name: 'welcome_from_outside')]
    public function __invoke(Request $request): Response
    {
        $form = $this->createFormBuilder([], ['csrf_protection' => false])
            ->add('pim_url', TextType::class, [
                'label' => 'Your PIM url ',
                'constraints' => new ReachableUrl(),
            ])
            ->add('save', SubmitType::class, ['label' => 'Connect'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $session = $request->getSession();
            $session->set('pim_url', \rtrim((string) $data['pim_url'], '/'));

            return new RedirectResponse($this->router->generate('authorization_activate'));
        }

        return $this->renderForm('welcome_from_outside.html.twig', [
            'form' => $form,
        ]);
    }
}
