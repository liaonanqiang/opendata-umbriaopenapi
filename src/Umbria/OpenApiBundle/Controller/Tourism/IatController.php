<?php

namespace Umbria\OpenApiBundle\Controller\Tourism;

use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\DiExtraBundle\Annotation as DI;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Paginator;
use Nelmio\ApiDocBundle\Annotation as ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Umbria\OpenApiBundle\Serializer\View\EntityResponse;
use Umbria\OpenApiBundle\Service\FilterBag;

/**
 * Class IatController
 * @package Umbria\OpenApiBundle\Controller\Tourism
 *
 * @author Lorenzo Franco Ranucci <loryzizu@gmail.com>
 */
class IatController extends BaseController
{
    const DEFAULT_PAGE_SIZE = 100;

    private $filterBag;
    private $paginator;

    private $em;

    /**
     * @DI\InjectParams({
     *      "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *      "filterBag" = @DI\Inject("umbria_open_api.filter_bag"),
     *      "paginator" = @DI\Inject("knp_paginator")
     * })
     * @param $em EntityManager
     * @param $filterBag FilterBag
     * @param $paginator Paginator
     */
    public function __construct($em, $filterBag, $paginator)
    {
        parent::__construct($em);
        $this->filterBag = $filterBag;
        $this->paginator = $paginator;
        $this->em = $em;
    }

    /**
     * @Rest\Options(pattern="/open-api/tourism-iat")
     */
    public function optionsTourismIatAction()
    {
        $response = new Response();

        $response->setContent(json_encode(array(
            'Allow' => 'GET,OPTIONS',
            'GET' => array(
                'description' => 'A get request for iat',
            ),
        )));
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'json');

        return $response;
    }

    /**
     * @Rest\Get(pattern="/open-api/tourism-iat")
     *
     * @param Request $request
     *
     * @return View
     *
     * @internal param Request $request
     *
     * @ApiDoc\ApiDoc(
     *  section = "Tourism",
     *  description = "Lista IAT regione Umbria",
     *  tags = {
     *      "beta"
     *  },
     *  parameters={
     *      {"name"="start", "dataType"="integer", "required"=false, "description"="Elemento da cui partire"},
     *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Numero di elementi restituiti"}
     *  },
     *  statusCodes={
     *      200="Returned when successful"
     *  }
     * )
     */
    public function getTourismIatListAction(Request $request)
    {

        $filters = $this->filterBag->getFilterBag($request);
        $offset = $filters->has('start') ? $filters->get('start') : 0;
        $limit = $filters->has('limit') ? $filters->get('limit') : self::DEFAULT_PAGE_SIZE;
        if ($limit == 0) {
            $limit = self::DEFAULT_PAGE_SIZE;
        }
        $page = floor($offset / $limit) + 1;


        $builder = $this->em->createQueryBuilder()
            ->select('i')
            ->from('UmbriaOpenApiBundle:Tourism\GraphsEntities\Iat', 'i');

        /** @var AbstractPagination $resultsPagination */
        $resultsPagination = $this->paginator->paginate($builder, $page, $limit);
        /** @var AbstractPagination $countPagination */
        $countPagination = $this->paginator->paginate($builder, 1, 1);

        $view = new View(new EntityResponse($resultsPagination->getItems(), count($resultsPagination), $countPagination->getTotalItemCount()));

        return $view;
    }





}
