<?php
/***************************************************\
 *
 *  SMailer (https://github.com/txthinking/SMailer)
 *
 *  Implement RFC0821, RFC0822, RFC1869, RFC2045, RFC2821
 *
\***************************************************/
/**
* @file SMailer.php
* @brief SMailer Class
* @author cloud@txthinking.com
* @version 0.9.1
* @date 2012-07-25
 */
class SMailer{
	/**
	 * smtp socket
	 */
	protected $smtp;
	/**
	 * smtp server
	 */
	protected $host;
	/**
	 * smtp server port
	 */
	protected $port;
	/**
	 * smtp secure ssl tls
	 */
	protected $secure;
	/**
	 * smtp username
	 */
	protected $username;
	/**
	 * smtp password
	 */
	protected $password;
	/**
	 * from email
	 */
	protected $from;
	/**
	 * to email
	 */
	protected $to;
	/**
	 * mail subject
	 */
	protected $subject;
	/**
	 * mail body
	 */
	protected $body;
	/**
	 *mail attachment
	 */
	protected $attachment;
	/**
	 * charset
	 */
	protected $charset;
	/**
	 * message header
	 */
	protected $header;
	/**
	 * header multipart boundaryMixed
	 */
	protected $boundaryMixed;
	/**
	 * header multipart alternative
	 */
	protected $boundaryAlternative;
	/**
	 * $this->CRLF
	 */
	protected $CRLF;
	/**
	 * responce message
	 */
	protected $message;
	/**
	 * SMailer version
	 */
	public $version = 'v0.9.1';

	/**
	 * construct function
	 */
	public function __construct(){
		$this->from = array();
		$this->to = array();
		$this->attachment = array();
		$this->charset =  "UTF-8";
		$this->header = array();
		$this->CRLF = "\r\n";
		$this->message = array();
		$this->message['all'] = '';
		$this->message['now'] = '';
	}

	/**
	 * set server and port
	 * @param unknown_type $host server
	 * @param unknown_type $port port
	 * @param unknown_type $secure ssl tls
	 */
	public function setServer($host, $port, $secure=null){
		$this->host = $host;
		$this->port = $port;
		$this->secure = $secure;
	}

	/**
	 * auth with server
	 * @param unknown_type $username
	 * @param unknown_type $password
	 */
	public function setAuth($username, $password){
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * set mail from
	 * @param string $name
	 * @param string $email
	 */
	public function setFrom($name, $email){
		$this->from['name'] = $name;
		$this->from['email'] = $email;
	}

	/**
	 * set mail receiver
	 * @param string $name
	 * @param string $email
	 */
	public function setTo($name, $email){
		$this->to[$name] = $email;
	}

	/**
	 * set mail subject
	 * @param unknown_type $subject
	 */
	public function setSubject($subject){
		$this->subject = $subject;
	}

	/**
	 * set mail body
	 * @param unknown_type $body
	 */
	public function setBody($body){
		$this->body = $body;
	}

	/**
	 * set mail attachment
	 * @param unknown_type $attachment
	 */
	public function setAttachment($name, $path){
		$this->attachment[$name] = $path;
	}

	/**
	 * send mail
	 * @return boolean
	 */
	public function send(){

		if (!$this->connect()){
			return false;
		}
		if (!$this->ehlo()){
			return false;
		}
		if ($this->secure == 'tls'){
			if(!$this->starttls()){
				return false;
			}
			if (!$this->ehlo()){
				return false;
			}
		}
		if (!$this->authLogin()){
			return false;
		}
		if (!$this->mailFrom()){
			return false;
		}
		if (!$this->rcptTo()){
			return false;
		}
		if (!$this->data()){
			return false;
		}
		if (!$this->quit()){
			return false;
		}
		return fclose($this->smtp);
	}

	/**
	 * connect the server
	 * SUCCESS 220
 	 * @return boolean
	 */
	protected function connect(){
		$host = ($this->secure == 'ssl') ? 'ssl://' . $this->host : $this->host;
		$this->smtp = fsockopen($host, $this->port);
		//set block mode
//		stream_set_blocking($this->smtp, 1);
		if (!$this->smtp){
			return false;
		}
		if ($this->getCode() != 220){
			return false;
		}
		return true;
	}

	/**
	 * SMTP STARTTLS
	 * SUCCESS 220
	 * @return boolean
	 */
	protected function starttls(){
		fputs($this->smtp,"STARTTLS" . $this->CRLF);
		if ($this->getCode() != 220){
			return false;
		}
		if(!stream_socket_enable_crypto($this->smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
	        return false;
	    }
	    return true;
	}

	/**
	 * SMTP EHLO
	 * SUCCESS 250
	 * @return boolean
	 */
	protected function ehlo(){
		$in = "EHLO " . $this->host . $this->CRLF;
		fputs($this->smtp, $in, strlen($in));
		if ($this->getCode() != 250){
			return false;
		}
		return true;
	}

	/**
	 * SMTP AUTH LOGIN
	 * SUCCESS 334
	 * SUCCESS 334
	 * SUCCESS 235
	 * @return boolean
	 */
	protected function authLogin(){
		$in = "AUTH LOGIN" . $this->CRLF;
		fputs($this->smtp, $in, strlen($in));
		if ($this->getCode() != 334){
			return false;
		}
		$in = base64_encode($this->username) . $this->CRLF;
		fputs($this->smtp, $in, strlen($in));
		if ($this->getCode() != 334){
			return false;
		}
		$in = base64_encode($this->password) . $this->CRLF;
		fputs($this->smtp, $in, strlen($in));
		if ($this->getCode() != 235){
			return false;
		}
		return true;
	}
	/**
	 * SMTP MAIL FROM
	 * SUCCESS 250
	 * @return boolean
	 */
	protected function mailFrom(){
		$in = "MAIL FROM:<" . $this->from['email'] . ">" . $this->CRLF;
		fputs($this->smtp, $in, strlen($in));
		if ($this->getCode() != 250){
			return false;
		}
		return true;
	}

	/**
	 * SMTP RCPT TO
	 * SUCCESS 250
	 * @return boolean
	 */
	protected function rcptTo(){
		foreach ($this->to as $v){
			$in = "RCPT TO:<" . $v . ">" . $this->CRLF;
			fputs($this->smtp, $in, strlen($in));
			if ($this->getCode() != 250){
				return false;
			}
		}
		return true;
	}

	/**
	 * SMTP DATA
	 * SUCCESS 354
	 * SUCCESS 250
	 * @return boolean
	 */
	protected function data(){
		$in = "DATA" . $this->CRLF;
		fputs($this->smtp, $in, strlen($in));
		if ($this->getCode() != 354){
			return false;
		}
    	$this->body = chunk_split(base64_encode($this->body));

    	$in = '';
    	$this->createHeader();
		foreach ($this->header as $k=>$v){
			$in .= $k . ': ' . $v . $this->CRLF;
		}
		if (empty($this->attachment)){
            $in .= $this->createBody();
		}else {
            $in .= $this->createBodyWithAttachment();
		}
		$in .= $this->CRLF;
		$in .= $this->CRLF . '.' . $this->CRLF;
		fputs($this->smtp, $in, strlen($in));
		if ($this->getCode() != 250){
			return false;
		}
		return true;
	}

    /**
     * @brief createBody create body
     *
     * @return
     */
    protected function createBody(){
        $in = "";
        $in .= "Content-Type: multipart/alternative; boundary=\"$this->boundaryAlternative\"" . $this->CRLF;
        $in .= $this->CRLF;
        $in .= "--" . $this->boundaryAlternative . $this->CRLF;
        $in .= "Content-Type: text/plain; charset=\"" . $this->charset . "\"" . $this->CRLF;
        $in .= "Content-Transfer-Encoding: base64" . $this->CRLF;
        $in .= $this->CRLF;
        $in .= $this->body . $this->CRLF;
        $in .= $this->CRLF;
        $in .= "--" . $this->boundaryAlternative . $this->CRLF;
        $in .= "Content-Type: text/html; charset=\"" . $this->charset ."\"" . $this->CRLF;
        $in .= "Content-Transfer-Encoding: base64" . $this->CRLF;
        $in .= $this->CRLF;
        $in .= $this->body . $this->CRLF;
        $in .= $this->CRLF;
        $in .= "--" . $this->boundaryAlternative . "--" . $this->CRLF;
        return $in;
    }

    /**
     * @brief createBodyWithAttachment create body with attachment
     *
     * @return body
     */
    protected function createBodyWithAttachment(){
        $in = "";
        $in .= $this->CRLF;
        $in .= $this->CRLF;
        $in .= '--' . $this->boundaryMixed . $this->CRLF;
        $in .= "Content-Type: multipart/alternative; boundary=\"$this->boundaryAlternative\"" . $this->CRLF;
        $in .= $this->CRLF;
        $in .= "--" . $this->boundaryAlternative . $this->CRLF;
        $in .= "Content-Type: text/plain; charset=\"" . $this->charset . "\"" . $this->CRLF;
        $in .= "Content-Transfer-Encoding: base64" . $this->CRLF;
        $in .= $this->CRLF;
        $in .= $this->body . $this->CRLF;
        $in .= $this->CRLF;
        $in .= "--" . $this->boundaryAlternative . $this->CRLF;
        $in .= "Content-Type: text/html; charset=\"" . $this->charset ."\"" . $this->CRLF;
        $in .= "Content-Transfer-Encoding: base64" . $this->CRLF;
        $in .= $this->CRLF;
        $in .= $this->body . $this->CRLF;
        $in .= $this->CRLF;
        $in .= "--" . $this->boundaryAlternative . "--" . $this->CRLF;
        foreach ($this->attachment as $k=>$v){
            $in .= $this->CRLF;
            $in .= '--' . $this->boundaryMixed . $this->CRLF;
            $in .= "Content-Type: application/octet-stream; name=\"". $k ."\"" . $this->CRLF;
            $in .= "Content-Transfer-Encoding: base64" . $this->CRLF;
            $in .= "Content-Disposition: attachment; filename=\"" . $k . "\"" . $this->CRLF;
            $in .= $this->CRLF;
            $in .= chunk_split(base64_encode(file_get_contents($v))) . $this->CRLF;
        }
        $in .= $this->CRLF;
        $in .= $this->CRLF;
        $in .= '--' . $this->boundaryMixed . '--' . $this->CRLF;
        return $in;
    }
    /**
     * SMTP QUIT
     * SUCCESS 221
     * @return boolean
     */
    protected function quit(){
        $in = "QUIT" . $this->CRLF;
        fputs($this->smtp, $in, strlen($in));
        if ($this->getCode() != 221){
            return false;
        }
        return true;
    }

    /**
     * create message header
     */
    protected function createHeader(){
        $this->header['Date'] = date('r');
        $this->header['Return-Path'] = $this->from['email'];
        $this->header['From'] = $this->from['name'] . " <" . $this->from['email'] .">";
        $this->header['To'] = '';
        foreach ($this->to as $k=>$v){
            $this->header['To'] .= $k . " <" . $v . ">, ";
        }
        $this->header['To'] = substr($this->header['To'], 0, -2);
        $this->header['Subject'] = $this->subject;
        $this->header['Message-ID'] = '<' . md5('TX'.md5(time()).uniqid()) . '@' . $this->username . '>';
        $this->header['X-Priority'] = '3';
        $this->header['X-Mailer'] = 'SMailer '. $this->version . '(https://github.com/txthinking/SMailer)';
        $this->header['MIME-Version'] = '1.0';
        if (!empty($this->attachment)){
            $this->boundaryMixed = md5(md5(time().'SMailer').uniqid());
            $this->header['Content-Type'] = "multipart/mixed; \r\n\tboundary=\"" . $this->boundaryMixed . "\"";
        }
        $this->boundaryAlternative = md5(md5(time().'SMailer').uniqid());
    }

    /**
     * get smtp response code
     * once time has three digital and a space
     * @return int
     */
    protected function getCode() {
        $this->message['now'] = "";
        while($str = @fgets($this->smtp,515)) {
            $this->message['all'] .= $str;
            $this->message['now'] .= $str;
            if(substr($str,3,1) == " ") {
                return substr($str,0,3);
            }
        }
        return false;
    }
}
