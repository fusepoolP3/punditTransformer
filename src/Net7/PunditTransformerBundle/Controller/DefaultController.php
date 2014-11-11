<?php

namespace Net7\PunditTransformerBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class DefaultController extends Controller
{
    /**
     * The basic information method.
     * Display basic info on the transformer
     * @return Response
     */
    public function infoAction()
    {

        $punditTransformerURI = $this->container->getParameter('TransformerUrl');

        \EasyRdf_Namespace::set('trans', 'http://vocab.fusepool.info/transformer#');
        \EasyRdf_Namespace::set('dct', 'http://purl.org/dc/terms/');

        $graph = new \EasyRdf_Graph();

        $r = $graph->resource($punditTransformerURI, 'trans:Transformer');


        $r->add('dct:title', 'Pundit Transformer');
        $r->add('dct:description', 'Gets HTML/RDF documents and let the user add annotations on it');
        $r->add('trans:supportedInputFormat', 'text/html');
        $r->add('trans:supportedInputFormat', 'application/rdf+xml');
        $r->add('trans:supportedOutputFormat', 'text/turtle');

        return new Response($graph->serialise('turtle'), '200', array('Content-type' => 'text/turtle'));

    }

    /**
     * Get the document (either HTML of RDF containing the HTML in a literal, and prepare the stuff for pundit.
     * @return string
     */
    public function startAction()
    {
        $request = Request::createFromGlobals();

        // We expect the data to be passed as the body of the request
        $document = file_get_contents('php://input');

        $task = new \Net7\PunditTransformerBundle\Entity\Task();
        $task->setInput($document);
        $task->setInputPageContent($document);

        $validation = $task->validateInput();


        if (!$validation['status']) {
            if ($validation['message'] != \Net7\PunditTransformerBundle\Entity\Task::VALIDATION_EMPTY_INPUT) {
                $document = '<html><head><meta charset="utf-8"/></head><body>' . $document . '</body></html>';
                $task->setInput($document);
               } else {
                return new Response($validation['message'], 400, array());

            }
        }else if ($task->hasRdfInput()){

            $rdf = $task->getRdfFromInput();

            $task->setInputPageContent($rdf);

            $document = '<html><head><meta charset="utf-8"/></head><body>' . $rdf. '</body></html>';
            $task->setInput($document);

        }


        $em = $this->getDoctrine()->getManager();
        $em->persist($task);
        $em->flush();

        $statusUrl = $this->generateUrl('net7_pundit_transformer_status', array('token' => $task->getToken()));

        $content = '';

        // we notify the UI layer about the newly available task.
        $IRURI = $task->sendInteractionRequeset($this->container->getParameter('IRURL'), $this->generateUrl('net7_pundit_transformer_show', array('token' => $task->getToken()), true));

        $task->setInteractionRequestURI($IRURI);

        $em = $this->getDoctrine()->getManager();
        $em->persist($task);
        $em->flush();

        $response = new Response($content, 201, array());
        $response->headers->set('Location', $statusUrl);



        return $response;

    }

    /**
     * @param $token
     * @return Response
     * Check the status of a task, and reply with the result (if ended), the error (if an error was shipped) or a
     * trans:Processing if still running
     */
    public function statusAction($token)
    {

        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('Net7\PunditTransformerBundle\Entity\Task')->findOneBy(array('token' => $token));

        switch ($task->getStatus()) {
            case \Net7\PunditTransformerBundle\Entity\Task::ENDED_STATUS:
                $content = $task->getAnnotations();
                $statusCode = '200';
                break;

            case \Net7\PunditTransformerBundle\Entity\Task::STARTED_STATUS:

                $content =<<<EOF
@prefix trans: <http://vocab.fusepool.info/transformer#> .

<> trans:status "trans:Processing"

EOF;
                $statusCode = '202';
                break;

            default:
            case \Net7\PunditTransformerBundle\Entity\Task::ERROR_STATUS:
                $statusCode = '400';
                $content = "";
                break;


        }


        $contentType = array('content-type' => 'text/turtle');

        return new Response($content, $statusCode, $contentType);
    }

    /**
     * @param $token
     * @return Response
     *
     * Invoke Pundit with the content saved in the task passed as a parameter
     */
    public function showAction($token)
    {

        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('Net7\PunditTransformerBundle\Entity\Task')->findOneBy(array('token' => $token));


        if (!$task->isInStartedStatus()) {
            // Either the task has been finished (isInEndedStatus()) or it encountered an error (isInErrorStatus()).
            // In both cases we don't want to let the user annotate the task content.
            return $this->render('Net7PunditTransformerBundle:Default:taskUnavailable.html.twig',
                array('message' => 'The task you\'ve requested isn\'t available anymore'));
        }

        // we take the POSTed data
        $input = $task->getInput();

        // and pass it through our scraper, which will enrich it and create an HTML page invoking the Pundit client
        $scraper = new \Net7\PunditTransformerBundle\FusepoolScraper($input, $token);
        $page = $scraper->getContent();

        echo $page;

        die();
    }


    /**
     * @param $token
     */
    public function viewAction($token)
    {

        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('Net7\PunditTransformerBundle\Entity\Task')->findOneBy(array('token' => $token));

        if (!$task->isInEndedStatus()) {
            // Either the task has been finished (isInEndedStatus()) or it encountered an error (isInErrorStatus()).
            // In both cases we don't want to let the user annotate the task content.
            return $this->render('Net7PunditTransformerBundle:Default:taskUnavailable.html.twig',
                array('message' => 'The task you\'ve requested is still active.'));
        }

        echo $task->getInputPageContent();
        die();

    }

        /**
     * Invoked by the Pundit client, it is called upon finishing the annotation work.
     * The user will have clicked the "finish" button, so we need to get the annotations from the annotation server
     * and store them in the Task (we persist it in the DB).
     *
     * The task is also marked as completed, no further annotations can be made on it.
     *
     */
    public function saveFromPunditAction()
    {

        $request = Request::createFromGlobals();
        $http_referer = $request->server->get('HTTP_REFERER');
        $token = substr($http_referer, strpos($http_referer, '/show/') + strlen('/show/'));

        if (!$token) {
            // Don't know how, but we didn't receive the token. We ship a 404 error.
            return new Response('Missing token', 404, array());
        }

        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('Net7\PunditTransformerBundle\Entity\Task')->findOneBy(array('token' => $token));

        // The POST request will contain a json as it content
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body, true);


        $punditContent = $data['punditContent'];
        $html = htmlspecialchars_decode($data['punditPage']);
        $asBaseUrl = $data['annotationServerBaseURL'];
        $apiUrl = $asBaseUrl . 'api/open/metadata/search?scope=all&query={"resources":["' . $punditContent . '"]}';

        $punditAnnotations = $data['annotations'];



        $rdfGraph = new \EasyRdf_Graph();

        \EasyRdf_Namespace::set('oa','http://www.w3.org/ns/oa#');
        \EasyRdf_Namespace::set('fam', 'http://vocab.fusepool.info/fam#');
        \EasyRdf_Namespace::set('nif', 'http://persistence.uni-leipzig.org/nlp2rdf/ontologies/nif-core#');


        foreach ($punditAnnotations as $annotation){


            $baseUri = str_replace('show', 'view', $annotation['pageContext']);
            $annotationUri = $baseUri . '#' . $annotation['id'];
            $baseRangeUri = $baseUri  . '#char=0';
            $rangeUri = $baseUri  . '#char=' . $annotation['start'] . ',' .$annotation['end'];

            $res = $rdfGraph->resource($annotationUri);

            $res->add('fam:selector', $rangeUri);
            $res->add('fam:extracted-from', $baseUri);
            $res->add('fam:entity-reference', $annotation['object']);
            if (is_array($annotation['objectData'])) {
                $res->add('fam:entity-mention', $annotation['objectData']['label']);
                $res->add('fam:entity-label', $annotation['objectData']['label']);

                foreach ($annotation['objectData']['type'] as $type) {

                    $res->add('fam:entity-type', $type);
                }
            }
            $rdfGraph->add($res, 'a', $annotation['predicate']);


            $rangeRes = $rdfGraph->resource($rangeUri);
            $rangeRes->add('nif:referenceContext', $baseRangeUri);
            $rangeRes->add('nif:beginIndex', '"'.$annotation['start'].'"^^xsd:int');
            $rangeRes->add('nif:endIndex', '"'.$annotation['end'].'"^^xsd:int');

             $rdfGraph->add($rangeRes, 'a', 'fam:NifSelector, nif:String');



            $task->setAnnotations($rdfGraph->serialise('turtle'));


        }



//        $task->setOutputPageContent($html);
        /*

        $annotations = new \EasyRdf_Graph($apiUrl);

        try {

            $annotations->load();
            $annotationTurtle = $annotations->serialise('turtle');

            foreach ($annotations->resources() as $key => $resource) {

                $annotationId = $resource->get('<http://purl.org/pundit/ont/ao#id>');

                if (!$annotationId) {
                    // some of the sub-graphs aren't about annotation, let's skip them
                    continue;
                }
                // fam:selector
                $target = $resource->get('<http://www.openannotation.org/ns/hasTarget>');

                // fam:extracted-from
                $pageContent = $resource->get('<http://purl.org/pundit/ont/ao#hasPageContext>');

                $metadataUrl = $asBaseUrl . 'api/open/annotations/' . $annotationId . '/metadata';
                $graphUrl = $asBaseUrl . 'api/open/annotations/' . $annotationId . '/graph';
                $itemsUrl = $asBaseUrl . 'api/open/annotations/' . $annotationId . '/items';

                $md = new \EasyRdf_Graph($metadataUrl);
                $gr = new \EasyRdf_Graph($graphUrl);
                $it = new \EasyRdf_Graph($itemsUrl);

                $md->load();
                $gr->load();
                $it->load();
                $annotationTurtle .= $md->serialise('turtle');
                $annotationTurtle .= $gr->serialise('turtle');
                $annotationTurtle .= $it->serialise('turtle');

            }

            $task->setAnnotations($annotationTurtle);

        } catch (\EasyRdf_Exception $e) {
            // we haven't received data from the Annotation Server, maybe there isn't any annotation on the page
            // we ignore the exception and procede with closing the task (the user has choosen to finish it up anyway)
        }
        */




        $task->setEndedStatus();

        $task->sendInteractionRequestDeletion();


        $em = $this->getDoctrine()->getManager();
        $em->persist($task);
        $em->flush();

        $response = new Response('', 200, array());
        return $response;
    }


    public function vocabularyAction()
    {
        $request = Request::createFromGlobals();
        $callback = $request->get('jsonp');

//        ob_start();
//        include($this->get('kernel')->getRootDir().'/../web/fusepool-vocabulary.json');
//        $vocabulary = ob_get_clean();

        $vocabulary = file_get_contents($this->get('kernel')->getRootDir().'/../web/fusepool-vocabulary.json');

//        $normalizer = new GetSetMethodNormalizer();

        $response = new JsonResponse($vocabulary, 200, array());
//        $response = new JsonResponse($normalizer->normalize($vocabulary), 200, array());
        $response->setCallback($callback);


        return $response;
    }

}
