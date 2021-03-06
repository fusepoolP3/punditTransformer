<?php

namespace Net7\PunditTransformerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Task
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Net7\PunditTransformerBundle\Entity\TaskRepository")
 */
class Task
{


    const STARTED_STATUS = 1;
    const ENDED_STATUS = 2;
    const ERROR_STATUS = 3;

    const VALIDATION_EMPTY_INPUT = 'Empty input';

    private $rdfInputContent;


    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var input
     *
     * @ORM\Column(name="input", type="text")
     */
    private $input;


    /**
     * @var input_page_content
     *
     * @ORM\Column(name="input_page_content", type="text")
     */
    private $inputPageContent;

    /**
     * @var status
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;


    /**
     * @var output_page_content
     *
     * @ORM\Column(name="output_page_content", type="text")
     */
    private $outputPageContent;


    /**
     * @var annotations
     *
     * @ORM\Column(name="annotations", type="text")
     */
    private $annotations;


    /**
     * @var token
     *
     * @ORM\Column(name="token", type="string")
     */
    private $token;


    /**
     * @var interactionRequestURI
     *
     * @ORM\Column(name="interactionRequestURI", type="text")
     */
    private $interactionRequestURI;


    /**
     * @var contentLocation
     *
     * @ORM\Column(name="contentLocation", type="text")
     */
    private $contentLocation;


    public function __construct()
    {
        $this->setStartedStatus();
        $this->setPageContent('');
        $this->setInput('');
        $this->setInputPageContent('');
        $this->setOutputPageContent('');
        $this->setAnnotations('');
        $this->setRandomToken();
        $this->setInteractionRequestURI('');
        $this->setContentLocation('');
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set pageContent
     *
     * @param string $pageContent
     * @return Task
     */
    public function setPageContent($pageContent)
    {
        $this->pageContent = $pageContent;

        return $this;
    }

    /**
     * Get pageContent
     *
     * @return string
     */
    public function getPageContent()
    {
        return $this->pageContent;
    }

    /**
     * Set input
     *
     * @param string $input
     * @return Task
     */
    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Get input
     *
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }


    /**
     * Set inputPageContent
     *
     * @param string $inputPageContent
     * @return Task
     */
    public function setInputPageContent($inputPageContent)
    {
        $this->inputPageContent = $inputPageContent;

        return $this;
    }

    /**
     * Get inputPageContent
     *
     * @return string
     */
    public function getInputPageContent()
    {
        return $this->inputPageContent;
    }


    /**
     * Set outputPageContent
     *
     * @param string $outputPageContent
     * @return Task
     */
    public function setOutputPageContent($outputPageContent)
    {
        $this->outputPageContent = $outputPageContent;

        return $this;
    }

    /**
     * Get outputPageContent
     *
     * @return string
     */
    public function getOutputPageContent()
    {
        return $this->outputPageContent;
    }


    /**
     * Set annotations
     *
     * @param string $annotations
     * @return Task
     */
    public function setAnnotations($annotations)
    {
        $this->annotations = $annotations;

        return $this;
    }

    /**
     * Get annotations
     *
     * @return string
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }


    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Task
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }


    public function setStartedStatus()
    {
        $this->status = self::STARTED_STATUS;
        return $this;
    }

    public function setEndedStatus()
    {
        $this->status = self::ENDED_STATUS;
        return $this;
    }

    public function setErrorStatus()
    {
        $this->status = self::ERROR_STATUS;
        return $this;
    }

    public function isInErrorStatus()
    {
        return $this->status == self::ERROR_STATUS;
    }

    public function isInEndedStatus()
    {
        return $this->status == self::ENDED_STATUS;
    }

    public function isInStartedStatus()
    {
        return $this->status == self::STARTED_STATUS;
    }


    /**
     * Set token
     *
     * @param string $token
     * @return Task
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    public function setRandomToken()
    {
        $this->setToken(uniqid());
    }


    /**
     * Set interactionRequestURI
     *
     * @param string $interactionRequestURI
     * @return Task
     */
    public function setInteractionRequestURI($interactionRequestURI)
    {
        $this->interactionRequestURI = $interactionRequestURI;

        return $this;
    }

    /**
     * Get interactionRequestURI
     *
     * @return string
     */
    public function getInteractionRequestURI()
    {
        return $this->interactionRequestURI;
    }


    /**
     * Set contentLocation
     *
     * @param string $contentLocation
     * @return Task
     */
    public function setContentLocation($contentLocation)
    {
        $this->contentLocation = $contentLocation;

        return $this;
    }

    /**
     * Get contentLocation
     *
     * @return string
     */
    public function getContentLocation()
    {
        return $this->contentLocation;
    }


    public function hasRdfInput()
    {

        $dom = new \DOMDocument();
        $dom->loadXML($this->input);

        $rdf = $dom->getElementsByTagNameNS('http://vocab.fusepool.info/fp3#', 'html');

        $this->rdfInputContent = $dom->saveXML($rdf->item(0));
        $this->removeFPtagsFromRdfInputContent();

        return ($this->rdfInputContent != '');

    }


    public function removeFPtagsFromRdfInputContent()
    {
        $this->rdfInputContent = preg_replace('/<fp:html(.*?)>/', '', preg_replace('/<\/fp:html>/', '', $this->rdfInputContent));
        return true;
    }

    public function getRdfFromInput()
    {
        $res = '';
        if ($this->rdfInputContent != '') {


            $res = $this->rdfInputContent;
        }


        return $res;
    }

    /**
     * @param $data
     * @return array
     *
     * test the input field and return an array with the status (boolean) and a message, if there were any errors
     */
    public function validateInput()
    {

        $data = $this->getInput();
        $res = array('status' => true);

        if (trim($data) == '') {
            $res = array('status' => false, 'message' => self::VALIDATION_EMPTY_INPUT);

        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadXML($data);

        if (($errors = libxml_get_errors()) != false) {
            $msg = '';
            foreach ($errors as $error) {
                $msg .= $error->message . "\r\n";
            }


            $res = array('status' => false, 'message' => $msg);
        }
        return $res;
    }


    /**
     * @param $IRURL
     * @param $url
     * @return mixed
     *
     * Performs an User Interaction Request and returns the InteractionRequestURI
     * (to be used at a later stage to delete it when the Task is over)
     */
    public function sendInteractionRequest($IRURL, $url, $task_token, $logger=false)
    {

        \EasyRdf_Namespace::set('fp3', 'http://vocab.fusepool.info/fp3#');
        \EasyRdf_Namespace::set('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');

        $requestContent = <<<EOF
@prefix ldp: <http://www.w3.org/ns/ldp#> .
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix dcterms: <http://purl.org/dc/terms/> .

<> a <http://vocab.fusepool.info/fp3#InteractionRequest> ;
	<http://vocab.fusepool.info/fp3#interactionResource> "$url" ;
	<http://www.w3.org/2000/01/rdf-schema#comment> "Pundit-annotation - $task_token"@en .
EOF;


        if ($logger) {
            $logger->info('IRURL = ' . $IRURL);
            $logger->info('contrent = ' . $requestContent);

        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $IRURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestContent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/turtle'));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $response = curl_exec($ch);
        $headers = $this->get_headers_from_curl_response($response);

        if ($logger) {

            $logger->info('response = ' . $response);
        }

        if (isset($headers['Location'])) {
            $location = $headers['Location'];
        } else {
            $location = '';
        }
        return $location;
    }


    public function sendInteractionRequestDeletion()
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getInteractionRequestURI());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $content = curl_exec($ch);

        return $content;
    }


    private function get_headers_from_curl_response($response)
    {
        $headers = array();

        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else {
                list ($key, $value) = explode(': ', $line);

                $headers[$key] = $value;
            }

        return $headers;
    }

}

