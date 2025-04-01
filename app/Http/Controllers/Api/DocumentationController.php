<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use App\Http\Utils\Constants;


/**
 * @OA\Info(
 *     title="API Meeting Lara",
 *     version="1.0.0",
 *     description="API pour la gestion des réunions et des comités locaux"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class DocumentationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/localities",
     *     summary="Liste des localités",
     *     description="Récupère la liste de toutes les localités avec leurs relations",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des localités",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Locality")
     *         )
     *     )
     * )
     */
    public function index()
    {
        return view('api.documentation');
    }

    /**
     * @OA\Get(
     *     path="/localities/{id}",
     *     summary="Détails d'une localité",
     *     description="Récupère les détails d'une localité spécifique avec ses relations",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la localité",
     *         @OA\JsonContent(ref="#/components/schemas/Locality")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Localité non trouvée"
     *     )
     * )
     */
    public function show()
    {
        // Cette méthode n'est pas utilisée, elle est juste pour la documentation
    }

    /**
     * @OA\Get(
     *     path="/localities/{id}/children",
     *     summary="Sous-localités",
     *     description="Récupère la liste des sous-localités d'une localité spécifique",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des sous-localités",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Locality")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Localité non trouvée"
     *     )
     * )
     */
    public function children()
    {
        // Cette méthode n'est pas utilisée, elle est juste pour la documentation
    }

    /**
     * @OA\Get(
     *     path="/locality-types",
     *     summary="Liste des types de localités",
     *     description="Récupère la liste de tous les types de localités avec le nombre de localités par type",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des types de localités",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/LocalityType")
     *         )
     *     )
     * )
     */
    public function types()
    {
        // Cette méthode n'est pas utilisée, elle est juste pour la documentation
    }

    /**
     * @OA\Get(
     *     path="/locality-types/{id}",
     *     summary="Détails d'un type de localité",
     *     description="Récupère les détails d'un type de localité spécifique",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du type de localité",
     *         @OA\JsonContent(ref="#/components/schemas/LocalityType")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Type de localité non trouvé"
     *     )
     * )
     */
    public function showType()
    {
        // Cette méthode n'est pas utilisée, elle est juste pour la documentation
    }

    /**
     * @OA\Get(
     *     path="/locality-types/{id}/localities",
     *     summary="Localités d'un type",
     *     description="Récupère la liste des localités d'un type spécifique",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des localités du type",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Locality")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Type de localité non trouvé"
     *     )
     * )
     */
    public function typeLocalities()
    {
        // Cette méthode n'est pas utilisée, elle est juste pour la documentation
    }
} 