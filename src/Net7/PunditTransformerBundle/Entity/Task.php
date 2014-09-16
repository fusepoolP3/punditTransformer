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


    public function __construct()
    {
        $this->setStartedStatus();
        $this->setPageContent('');
        $this->setInput('');
        $this->setInputPageContent('');
        $this->setOutputPageContent('');
        $this->setAnnotations('');
        $this->setRandomToken();
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
     * @param $data
     * @return array
     *
     * test the input field and return an array with the status (boolean) and a message, if there were any errors
     */
    public function validateInput($data)
    {

        $res = array('status' => true);

        if (trim($data) == '') {
            $res = array('status' => false, 'message' => 'Empty Input');

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
}
