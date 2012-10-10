<?php
/**
* jMailer : based on PHPMailer - PHP email class
* Class for sending email using either
* sendmail, PHP mail(), SMTP, or files for tests.  Methods are
* based upon the standard AspEmail(tm) classes.
*
* @package     jelix
* @subpackage  utils
* @author      Laurent Jouanneau
* @contributor Kévin Lepeltier, GeekBay, Julien Issler
* @copyright   2006-2010 Laurent Jouanneau
* @copyright   2008 Kévin Lepeltier, 2009 Geekbay
* @copyright   2010 Julien Issler
* @link        http://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require(LIB_PATH.'phpMailer/class.phpmailer.php');


/**
 * jMailer based on PHPMailer - PHP email transport class
 * @package jelix
 * @subpackage  utils
 * @author Laurent Jouanneau
 * @contributor Kévin Lepeltier
 * @copyright   2006-2008 Laurent Jouanneau
 * @copyright   2008 Kévin Lepeltier
 * @since 1.0b1
 * @see PHPMailer
 */
class jMailer extends PHPMailer {

    /**
     * the selector of the template used for the mail.
     * Use the Tpl() method to change this property
     * @var string
     */
    protected $bodyTpl = '';

    protected $defaultLang;

    /**
     * the path of the directory where to store mails
     * if mailer is file.
    */
    public $filePath = '';

    /**
     * initialize some member
     */
    function __construct(){
        global $gJConfig;
        $this->defaultLang = $gJConfig->locale;
        $this->CharSet = $gJConfig->charset;
        if ($gJConfig->mailer['mailerType'])
            $this->Mailer = $gJConfig->mailer['mailerType'];
        $this->Hostname = $gJConfig->mailer['hostname'];
        $this->Sendmail = $gJConfig->mailer['sendmailPath'];
        $this->Host = $gJConfig->mailer['smtpHost'];
        $this->Port = $gJConfig->mailer['smtpPort'];
        $this->Helo = $gJConfig->mailer['smtpHelo'];
        $this->SMTPAuth = $gJConfig->mailer['smtpAuth'];
        $this->SMTPSecure = $gJConfig->mailer['smtpSecure'];
        $this->Username = $gJConfig->mailer['smtpUsername'];
        $this->Password = $gJConfig->mailer['smtpPassword'];
        $this->Timeout = $gJConfig->mailer['smtpTimeout'];
        if($gJConfig->mailer['webmasterEmail'] != '') {
            $this->From = $gJConfig->mailer['webmasterEmail'];
        }

        $this->FromName = $gJConfig->mailer['webmasterName'];
        $this->filePath = JELIX_APP_VAR_PATH.$gJConfig->mailer['filesDir'];
        parent::__construct(true);
    }

    /**
     * Sets Mailer to store message into files instead of sending it
     * useful for tests.
     * @return void
     */
    public function IsFile() {
        $this->Mailer = 'file';
    }


    /**
     * Find the name and address in the form "name<address@hop.tld>"
     * @param string $address
     * @return array( $name, $address )
     */
    function getAddrName($address, $kind = false) {
        if (preg_match ('`^([^<]*)<([^>]*)>$`', $address, $tab )) {
            $name = $tab[1];
            $addr = $tab[2];
        }
        else {
            $name = '';
            $addr = $address;
        }
        if (!$kind) {
            return array($addr, $name);
        }
        $this->AddAnAddress($kind, $addr, $name);
    }

    protected $tpl = null;

    /**
     * Adds a Tpl référence.
     * @param string $selector
     * @param boolean $isHtml  true if the content of the template is html.
     *                 IsHTML() is called.
     * @return jTpl the template object.
     */
    function Tpl( $selector, $isHtml = false ) {
        $this->bodyTpl = $selector;
        $this->tpl = new jTpl();
        $this->IsHTML($isHtml);
        return $this->tpl;
    }

    /**
     * Creates message and assigns Mailer. If the message is
     * not sent successfully then it returns false.  Use the ErrorInfo
     * variable to view description of the error.
     * @return bool
     */
    function Send() {

        if (isset($this->bodyTpl) && $this->bodyTpl != "") {
            if ($this->tpl == null)
                $this->tpl = new jTpl();
            $mailtpl = $this->tpl;
            $metas = $mailtpl->meta( $this->bodyTpl , ($this->ContentType == 'text/html'?'html':'text') );

            if (isset($metas['Subject']))
                $this->Subject = $metas['Subject'];

            if (isset($metas['Priority']))
                $this->Priority = $metas['Priority'];
            $mailtpl->assign('Priority', $this->Priority );

            if (isset($metas['Sender']))
                $this->Sender = $metas['Sender'];
            $mailtpl->assign('Sender', $this->Sender );

            if (isset($metas['to']))
                foreach( $metas['to'] as $val )
                    $this->getAddrName( $val, 'to' );
            $mailtpl->assign('to', $this->to );

            if (isset($metas['cc']))
                foreach( $metas['cc'] as $val )
                    $this->getAddrName($val, 'cc');
            $mailtpl->assign('cc', $this->cc );

            if (isset($metas['bcc']))
                foreach( $metas['bcc'] as $val )
                    $this->getAddrName($val, 'bcc');
            $mailtpl->assign('bcc', $this->bcc);

            if (isset($metas['ReplyTo']))
                foreach( $metas['ReplyTo'] as $val )
                    $this->getAddrName($val, 'ReplyTo');
            $mailtpl->assign('ReplyTo', $this->ReplyTo );

            if (isset($metas['From'])) {
                $adr = $this->getAddrName($metas['From']);
                $this->SetFrom($adr[0], $adr[1]);
            }

            $mailtpl->assign('From', $this->From );
            $mailtpl->assign('FromName', $this->FromName );

            if ($this->ContentType == 'text/html') {
                $this->MsgHTML($mailtpl->fetch( $this->bodyTpl, 'html'));
            }
            else
                $this->Body = $mailtpl->fetch( $this->bodyTpl, 'text');
        }

        return parent::Send();
    }

    public function CreateHeader() {
        if ($this->Mailer == 'file') {
            // to have all headers in the file, like cc, bcc...
            $this->Mailer = 'sendmail';
            $headers = parent::CreateHeader();
            $this->Mailer = 'file';
            return $headers;
        }
        else
            return parent::CreateHeader();
    }

    /**
     * store mail in file instead of sending it
     * @access public
     * @return bool
     */
    protected function FileSend($header, $body) {
        return jFile::write ($this->getStorageFile(), $header.$body);
    }

    protected function getStorageFile() {
        return rtrim($this->filePath,'/').'/mail.'.$GLOBALS['gJCoord']->request->getIP().'-'.date('Ymd-His').'-'.uniqid(mt_rand(), true);
    }

    function SetLanguage($lang_type = 'en', $lang_path = 'language/') {
        $lang = explode('_', $lang_type);
        return parent::SetLanguage($lang[0], $lang_path);
    }

    protected function Lang($key) {
      if(count($this->language) < 1) {
        $this->SetLanguage($this->defaultLang); // set the default language
      }
      if(isset($this->language[$key])) {
        return $this->language[$key];
      } else {
        return 'Language string failed to load: ' . $key;
      }
    }
}
