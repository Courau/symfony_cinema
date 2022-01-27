<?php

namespace App\Controller;

use App\Service\CallApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Film;
use App\Repository\FilmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\FilmType;
use App\Form\ImportCsvForm;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;



class FilmController extends AbstractController{

    #[Route('/listFilm', name: 'listFilm')]
    public function filmList(FilmRepository $repo): Response{
        $films = $repo->listFilmOrder();
        return $this->render('film/listFilm.html.twig',[
            'films' => $films 
        ]);
    }

    #[Route('/film/{id}', name: 'film')]
    public function film(film $film): Response{
        return $this->render('film/film.html.twig', [
            'film'=>$film
        ]);
    }

    #[Route('/addFilm', name: 'addAFilm')]
    public function addFilm(FilmRepository $filmRepository, Request $request, EntityManagerInterface $entityManager, CallApiService $callApiService): Response{
        $film = new Film();
        $form = $this->createForm(FilmType::class, $film);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $apiResponse = $callApiService->getMovie($film->getName());
            $data = $form->getData();
            if ($apiResponse[0]['name'] == $data->getName()){
                if ($filmRepository->findOneBySomeField($apiResponse[0]['name']) == null){
                    $film->setVotersNumber(1);
                    $descriptionArray = $callApiService->findDescription($apiResponse[0]['url']);
                    $film->setDescription($descriptionArray['description']);
                    $entityManager->persist($film);
                    $entityManager->flush();   
                    return $this->redirectToRoute('film', ['id'=>$film->getId()]);
                } else {
                    echo "Film existant";
                }
            } else {
                echo "Film non trouvé, peut etre est ce " . $apiResponse[0]['name'];
            }           
        }  
        return $this->render('film/addfilm.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
