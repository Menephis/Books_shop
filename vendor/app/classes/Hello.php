<?
namespace app\classes;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Hello{
    public function sayHello(Request $request, Application $app){
            return "<h1>hello</h1>";
    }
}
?>
