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
     * @var string
     *
     * @ORM\Column(name="page_content", type="text")
     */
    private $pageContent;

    /**
     * @var status
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;


    /**
     * @var output
     *
     * @ORM\Column(name="output", type="text")
     */
    private $output;


    /**
     * @var token
     *
     * @ORM\Column(name="token", type="string")
     */
    private $token;


     public function __construct() {
        $this->setStartedStatus();
        $this->setPageContent('');
        $this->setInput('');
        $this->setOutput('');
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

    /**
     * Set output
     *
     * @param string $output
     * @return Task
     */
    public function setOutput($output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Get output
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }


    public function setStartedStatus(){
        $this->status = self::STARTED_STATUS;
        return $this;
    }

    public function setEndedStatus(){
        $this->status = self::ENDED_STATUS;
        return $this;
    }

    public function setErrorStatus(){
        $this->status = self::ERROR_STATUS;
        return $this;
    }

    public function isInErrorStatus(){
        return $this->status == self::ERROR_STATUS;
    }

    public function isInEndedStatus(){
        return $this->status == self::ENDED_STATUS;
    }

    public function isInStartedStatus(){
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

    public function setRandomToken(){
        $this->setToken(uniqid());
    }
}
