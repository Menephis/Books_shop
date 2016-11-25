<?php
namespace src\Silex\Views;

abstract class AbstractTemplateEngine{
    /**
    * Current path to template directory
    *
    * @var string $pathToTemplates
    */
    protected $pathToTemplates;
    /**
    * @var protected $pathWebRoot
    */
    protected $pathWebRoot;
    /**
    * Set cuurent path to Template
    *
    * @param string $pathToTemplates
    *
    * @return void
    */
    public function __construct(string $pathToTemplates, string $webRoot)
    {
        if(!is_dir($pathToTemplates))
            throw new \Exception("Path to templates directory " . $pathToTemplates . " is invalid.");
//        if(!is_dir($webRoot))
//            throw new \Exception("Path to web directory " . $webRoot . " is invalid.");

        $this->pathToTemplates = $pathToTemplates;
        $this->pathWebRoot = $webRoot;
    }
    /**
    * Get sourse
    *
    * @return string $this->pathWebRoot
    */
    public function getSourse()
    {
        return $this->pathWebRoot;
    }
    /**
    * Render your template
    *
    * @param string $tmplName
    * @param array $data
    *
    * @return string $renderedPage
    */
    abstract public function render(string $tmplName, array $data);

}
?>
