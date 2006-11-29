<?php
/**
* @package     jelix
* @subpackage  core
* @version     $Id$
* @author      Jouanneau Laurent
* @contributor
* @copyright   2005-2006 Jouanneau laurent
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

/**
 * handle classical request but only to control and produce css content
 * @package     jelix
 * @subpackage  core
 */
class jCssRequest extends jRequest {

    public $type = 'css';

    public $defaultResponseType = 'css';

    protected function _initParams(){
        $url  = jUrl::parse($_SERVER['SCRIPT_NAME'], $this->url_path_info, $_GET);
        $this->params = array_merge($url->params, $_POST);
    }

    public function allowedResponses(){ return array('jResponseCss');}
}
?>