<?php
namespace src\Silex\Views;

use src\Silex\Views\AbstractTemplateEngine;

class TemplateEngine extends AbstractTemplateEngine
{
    /**
    * Render your template
    *
    * @param string $tmplName
    * @param array $data - any data wich will render
    *
    * @return string $renderedPage
    */
    public function render(string $tmplName, array $data = null)
    {
        if(is_file($path = $this->pathToTemplates . DIRECTORY_SEPARATOR . $tmplName . ".php")) {
            if($data != null)
                extract($data, EXTR_SKIP);
            ob_start();
            require($path);
            return ob_get_clean();
        } else {
            throw new Exception("Template path - $path is invalid.");
        }
    }
}
?>
