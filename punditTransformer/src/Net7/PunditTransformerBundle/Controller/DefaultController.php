<?php

namespace Net7\PunditTransformerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class DefaultController extends Controller
{

    const platformURIfile = 'platformURI';


    /**
     * The basic information method.
     * Display basic info on the transformer
     * @return Response
     */



    public function infoAction()
    {
        $punditTransformerURI = $this->generateUrl('net7_pundit_transformer_info', array(),true);

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
        $contentLocation = $request->headers->get('Content-Location');


        // We expect the data to be passed as the body of the request
        $document = file_get_contents('php://input');

        $task = new \Net7\PunditTransformerBundle\Entity\Task();
        $task->setInput($document);

        // we don't save the input page if we have received a Content-Location header, as we'll use that to show the
        // original content, by just forwarding to it
        if ($contentLocation) {
            $task->setContentLocation($contentLocation);
        } else {
            $task->setInputPageContent($document);
        }
        $validation = $task->validateInput();


        if (!$validation['status']) {
            if ($validation['message'] != \Net7\PunditTransformerBundle\Entity\Task::VALIDATION_EMPTY_INPUT) {
                $document = '<html><head><meta charset="utf-8"/></head><body>' . $document . '</body></html>';
                $task->setInput($document);
            } else {
                return new Response($validation['message'], 400, array());

            }
        } else if ($task->hasRdfInput()) {

            $rdf = $task->getRdfFromInput();

            if (!$contentLocation) {
                // we don't save the input page if we have received a Content-Location header, as we'll use that to show the
                // original content, by just forwarding to it

                $task->setInputPageContent($rdf);
            }
            $document = '<html><head><meta charset="utf-8"/></head><body>' . $rdf . '</body></html>';
            $task->setInput($document);

        }


        $em = $this->getDoctrine()->getManager();
        $em->persist($task);
        $em->flush();

        $statusUrl = $this->generateUrl('net7_pundit_transformer_status', array('token' => $task->getToken()));

        $content = '';


        // we notify the UI layer about the newly available task.
        $IRURI = $task->sendInteractionRequest($this->getIRURL(), $this->generateUrl('net7_pundit_transformer_show', array('token' => $task->getToken()), true), $task->getToken());


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

                $content = <<<EOF
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


        if (!$task || !$task->isInStartedStatus()) {
            // Either the task has been finished (isInEndedStatus()) or it encountered an error (isInErrorStatus()).
            // In both cases we don't want to let the user annotate the task content.
            return $this->render('Net7PunditTransformerBundle:Default:taskUnavailable.html.twig',
                array('message' => 'The task you\'ve requested isn\'t available.'));
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


        // If a content location header was provided to the startAction, we forward to it, it will contain the content
        if ($location = $task->getContentLocation()) {
            header('Location: ' . $location);
            exit();
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

        \EasyRdf_Namespace::set('oa', 'http://www.w3.org/ns/oa#');
        \EasyRdf_Namespace::set('fam', 'http://vocab.fusepool.info/fam#');
        \EasyRdf_Namespace::set('nif', 'http://persistence.uni-leipzig.org/nlp2rdf/ontologies/nif-core#');


        foreach ($punditAnnotations as $annotation) {

            if (($location = $task->getContentLocation()) != '') {
                // we take the Content-Location header received
                $baseUri = $location;
            } else {
                $baseUri = str_replace('show', 'view', $annotation['pageContext']);
            }

            $annotationUri = $baseUri . '#' . $annotation['id'];

            $baseRangeUri = $baseUri . '#char=0';
            $rangeUri = $baseUri . '#char=' . $annotation['start'] . ',' . $annotation['end'];

            $res = $rdfGraph->resource($annotationUri);
            $res->add('fam:selector', $rdfGraph->resource($rangeUri));
            $res->add('fam:extracted-from', $baseUri);

            if ($annotation['predicate'] == 'http://purl.org/pundit/ont/oa#isDate') {
                $res->add('fam:entity-mention', $annotation['object']);
                $res->add('fam:entity-type', $rdfGraph->resource('oa:isDate'));
            } else {
                $res->add('fam:entity-reference', $rdfGraph->resource($annotation['object']));
                $res->add('fam:entity-mention', $annotation['objectData']['label']);
                $res->add('fam:entity-label', $annotation['objectData']['label']);

                if (is_array($annotation['objectData'])) {
                    foreach ($annotation['objectData']['type'] as $type) {
                        $res->add('fam:entity-type', $rdfGraph->resource($type));
                    }
                }
                $rdfGraph->add($res, 'a', $rdfGraph->resource($annotation['predicate']));
            }

            $rangeRes = $rdfGraph->resource($rangeUri);
            $rangeRes->add('nif:referenceContext', $rdfGraph->resource($baseRangeUri));


            $begin = \EasyRdf_Literal::create($annotation['start'], null, 'xsd:int');
            if(!$begin){
                $begin = 0;
            }
            $rangeRes->add('nif:beginIndex', $begin);


            $end = \EasyRdf_Literal::create($annotation['end'], null, 'xsd:int');
            if (!$end){
                $end= 0 ;
            }

            $rangeRes->add('nif:endIndex', $end);




//            $rangeRes->add('nif:beginIndex', \EasyRdf_Literal::create($annotation['start'], null, 'xsd:int'));
//            $rangeRes->add('nif:endIndex', \EasyRdf_Literal::create($annotation['end'], null, 'xsd:int'));

            $rangeRes->add('nif:anchorOf', $annotation['anchorOf']);
            $rangeRes->add('nif:before', $annotation['before']);
            $rangeRes->add('nif:after', $annotation['after']);
            $rdfGraph->add($rangeRes, 'a', $rdfGraph->resource('fam:NifSelector'));
            $rdfGraph->add($rangeRes, 'a', $rdfGraph->resource('nif:String'));

            $targetUri = $annotationUri . '-target';
            $oaSpecificResource = $rdfGraph->resource($targetUri);
            $oaSpecificResource->add('oa:hasSelector', $rdfGraph->resource($rangeUri));
            $oaSpecificResource->add('oa:hasSource', $rdfGraph->resource($baseUri));
            $rdfGraph->add($oaSpecificResource, 'a', $rdfGraph->resource('oa:SpecificResource'));

            $oaAnnotation = $rdfGraph->resource($annotationUri . '-annotation');
            $annotationDate = date('c', strtotime($annotation['annotatedAt']));
            $oaAnnotation->add('oa:annotatedAt', $annotationDate);
            $oaAnnotation->add('oa:serializedAt', $annotationDate);
            $oaAnnotation->add('oa:annotatedBy', $annotation['annotatedBy']);
            $oaAnnotation->add('oa:hasBody', $rdfGraph->resource($annotationUri));
            $oaAnnotation->add('oa:hasTarget', $rdfGraph->resource($targetUri));
            $oaAnnotation->add('oa:serializedBy', "it.netseven.pundittransformer");

            $rdfGraph->add($oaAnnotation, 'a', $rdfGraph->resource('oa:Annotation'));

            $task->setAnnotations($rdfGraph->serialise('turtle'));

        }

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

        $vocabulary = file_get_contents($this->get('kernel')->getRootDir() . '/../web/fusepool-vocabulary.json');
        $response = new JsonResponse($vocabulary, 200, array());
        $response->setCallback($callback);

        return $response;
    }

    public function punditConfigAction(){
        $configFile = '../web/fusepool_conf.js';
        $h = fopen($configFile, 'r');
        $conf = fread($h, filesize($configFile));
        fclose($h);

        // net7_pundit_transformer_info is the / route, aka the baseurl
        $conf = str_replace('%%saveFromPunditUrl%%', $this->generateUrl('net7_pundit_transformer_save_from_pundit', array(),true), $conf);


        $response = new Response($conf, 200, array());

        return $response;

    }



    public function setPlatformURI($uri){
        $h = fopen(self::platformURIfile, 'w');
        fwrite($h, $uri, strlen($uri));
        fclose($h);
        return $uri;

    }
    public function getPlatformURI(){

        $h = fopen(self::platformURIfile, 'r');
        $uri = fread($h,  filesize(self::platformURIfile));
        fclose($h);
        return $uri;
    }

    public function fusepoolConfigAction(){




        $request = Request::createFromGlobals();
        $platformURI = $request->get('fusepool');


        $this->setPlatformURI($platformURI);


        $responseArray  = array('platform' => $platformURI);
        $response = new JsonResponse($responseArray, 200, array());

        return $response;

    }

    private function getIRURL(){

        $platformURI = $this->getPlatformURI();



        $ch = curl_init($platformURI);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);

        curl_close($ch);


        \EasyRdf_Namespace::set('fp3', 'http://vocab.fusepool.info/fp3#');


        $graph = new \EasyRdf_Graph($platformURI);
        $graph->parse($output, 'turtle');

        $resources = $graph->resources();

        $predicate = 'fp3:userInteractionRequestRegistry';

        foreach($resources as $key => $r) {
            if ($r->get($predicate)) {
                return $r->get($predicate);
            }
        }

        throw new \Exception('Missing IRURI !');
    }
}
