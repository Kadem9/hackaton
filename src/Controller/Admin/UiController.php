<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UiController extends AbstractController
{
    #[Route('/admin/ui', name: 'admin_ui')]
    public function index(): Response
    {
        $icons = ['home','edit','screen','note','users','cog','plus','check','times','left','right','logout','filter','search','website','mobile','code','italic','bold','fullscreen','link','bug','folder','trash','squares','eye','lock','print','sun','moon','duplicate','clipboard','insert','dotsdg','h1','text','expand','underline','bulletList','orderedList','image','customize','valigntop','valignbottom','valigntop','documentation','medias','jpg','png','pdf','valid','invalid','seo','faq','content','tag','user','nav','button','minus','galery','zoom-in','zoom-out','menu','upload','carousel', 'info', 'sort', 'sort-up', 'sort-down', 'member'];
        return $this->render('admin/ui/index.html.twig', [
            'icons' => $icons
        ]);
    }
}
