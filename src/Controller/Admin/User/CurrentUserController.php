<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use App\Service\User\UserPreferenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/current-user', name: 'admin_current_user_')]
class CurrentUserController extends AbstractController
{
    #[Route('/save-preference', name: 'save_preference', methods: ['POST'])]
    public function index(Request $request, UserPreferenceService $userPreferenceService): Response
    {
        $type = $request->request->get('type_reference');
        $preferenceKey = $request->request->get('preference_key');
        $redirect = $request->request->get('redirect', $this->generateUrl('admin_home'));

        if($type === 'checkbox') {
            $all = $request->request->all();
            $tds = array_keys($all['td'] ?? []);
            $values = $all['values'] ?? [];
            $preferenceValue = [];
            foreach ($values as $value) {
                if(!in_array($value, $tds)) {
                    $preferenceValue[] = ".{$value}";
                }
            }
            $userPreferenceService->savePreference($this->getUser()->getId(), $preferenceKey, json_encode($preferenceValue));
        }

        return $this->redirect($redirect);
    }
}
