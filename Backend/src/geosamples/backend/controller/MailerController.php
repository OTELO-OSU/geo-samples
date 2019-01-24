<?php
namespace geosamples\backend\controller;

use \geosamples\backend\controller\ConnectController as ConnectDB;
use \geosamples\backend\controller\FileController as File;
use \geosamples\model\ProjectsAccessRight as Projects_access_right;
use \geosamples\model\Users as Users;

class MailerController
{
    private $DBinstance;

    public function __construct()
    {
        $ConnectDB  = new ConnectDB();
        $DBinstance = $ConnectDB->EloConfigure($_SERVER['DOCUMENT_ROOT'] . '/../AuthDB.ini');
    }

    public function CheckSMTPstatus()
    {
        $file      = new File();
        $config    = $file->ConfigFile();
        $f         = fsockopen($config['SMTP'], 25, $errno, $errstr, 3);
        $connected = false;
        if ($f !== false) {
            $res = fread($f, 1024);
            if (strlen($res) > 0 && strpos($res, '220') === 0) {
                $connected = true;
            }
        }
        fclose($f);
        return $connected;
    }

    
    /**
     * Send a mail to reset password
     * @return true if error, else false
     */
    public function Send_Reset_Mail($email, $token)
    {
        $connected = self::CheckSMTPstatus();
        if ($connected === true) {
            $file    = new File();
            $config  = $file->ConfigFile();
            $headers = "From:<" . $config['NO_REPLY_MAIL'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $mail = mail($email, '[' . $config['PROJECT_NAME'] . '] Reset your password', '<html>
               <head>
               <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
               </head>
               <body>
               <h2>Reset your password</h2>

               <p>Hello , we got a request to reset your ' . $config['PROJECT_NAME'] . ' password , if you ignore this message , your password won\'t be changed.</p>
               <p>This link will expire in 30 min.</p>
               <a href="' . $config['REPOSITORY_URL'] . '/recover?token=' . $token . '">Click here to reset your password</a>

               </body>
               </html> ', $headers);
            if ($mail == true) {
                $error = "false";
            } else {
                $error = "true";
            }

        } else {
            $error = "true";
        }
        return $error;
    }

    /**
     * Send a mail to reset password
     * @return true if error, else false
     */
    public function Send_Validation_Mail($email, $token)
    {
        $connected = self::CheckSMTPstatus();
        if ($connected === true) {
            $file    = new File();
            $config  = $file->ConfigFile();
            $headers = "From:<" . $config['NO_REPLY_MAIL'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $mail = mail($email, '[' . $config['PROJECT_NAME'] . '] Validate your account', '<html>
               <head>
               <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
               </head>
               <body>
               <h2>Reset your password</h2>

               <p>Hello , we got a request to validate your account on  ' . $config['PROJECT_NAME'] . '  </p>
               <p>This link will expire in 30 min, Please click on this link.</p>
               <a href="' . $config['REPOSITORY_URL'] . '/activate_account?token=' . $token . '">Click here to activate your account</a>

               </body>
               </html> ', $headers);
            if ($mail == true) {
                $error = "false";
            } else {
                $error = "true";
            }

        } else {
            $error = "true";
        }
        return $error;
    }

    /**
     * Send a mail to notify reset password
     * @return true if error, else false
     */
    public function Send_password_success($email)
    {
        $connected = self::CheckSMTPstatus();
        if ($connected === true) {
            $file    = new File();
            $config  = $file->ConfigFile();
            $headers = "From:<" . $config['NO_REPLY_MAIL'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $mail = mail($email, '[' . $config['PROJECT_NAME'] . '] Your password has been modified with success', '<html>
               <head>
               <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
               </head>
               <body>
               <h2>Your password has been modified with success</h2>
               </body>
               </html> ', $headers);
            if ($mail == true) {
                $error = "false";
            } else {
                $error = "true";
            }

        } else {
            $error = "true";
        }
        return $error;
    }

    /**
     * Send a mail to notify admin validation account
     */
    public function Send_mail_validation($email)
    {
        $connected = self::CheckSMTPstatus();
        if ($connected === true) {
            $file    = new File();
            $config  = $file->ConfigFile();
            $headers = "From:<" . $config['NO_REPLY_MAIL'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $admin = Users::where('type', '=', "1")->get();
            foreach ($admin as $key => $value) {
                $mail = mail($value->mail, '[' . $config['PROJECT_NAME'] . '] Validation of account required!', '<html>
                   <head>
                   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                   </head>
                   <body>
                   <p>Hello, ' . $email . ' join ' . $config['PROJECT_NAME'] . ', please approve or remove it. </p>
                   </body>
                   </html> ', $headers);
            }

        } else {
            $error = "true";
        }
        return $error;
    }

    /**
     * Send a mail to user to notify account activation
     */
    public function Send_mail_account_activation($email)
    {
        $connected = self::CheckSMTPstatus();
        if ($connected === true) {
            $file    = new File();
            $config  = $file->ConfigFile();
            $headers = "From:<" . $config['NO_REPLY_MAIL'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $mail = mail($email, '[' . $config['PROJECT_NAME'] . '] Your account is now created!', '<html>
               <head>
               <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
               </head>
               <body>
               <p>Hello your account is now validated by administrator, you can sign in to <a href="' . $config['REPOSITORY_URL'] . '">' . $config['PROJECT_NAME'] . '</a>. </p>
               </body>
               </html> ', $headers);

        } else {
            $error = "true";
        }
        return $error;
    }

     public function Send_mail_referent_project($email,$project,$user)
    {
        $connected = self::CheckSMTPstatus();
        if ($connected === true) {
            $file    = new File();
            $config  = $file->ConfigFile();
            $headers = "From:<" . $config['NO_REPLY_MAIL'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $mail = mail($email, '[' . $config['PROJECT_NAME'] . '] You must verify a user account for the project '.$project.' !', '<html>
               <head>
               <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
               </head>
               <body>
               <p>Hello, '.$user.' want access to the project '.$project.', please go to your account to check it out! </a>. </p>
               </body>
               </html> ', $headers);

        } else {
            $error = "true";
        }
        return $error;
    }

 public function Send_mail_user_welcome_project($email,$project,$referent)
    {
        $connected = self::CheckSMTPstatus();
        if ($connected === true) {
            $file    = new File();
            $config  = $file->ConfigFile();
            $headers = "From:<" . $config['NO_REPLY_MAIL'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $referents_cc='';
            $referents=Projects_access_right::select('users.id_user','users.mail','users.name','users.firstname','users.type')->where('Projects.name', '=',$project )->where('users.type','=','2')->join('Projects', 'id_project', '=', 'Projects.id')->join('users','users.id_user','=','Projects_access_right.id_user')->get();
            foreach ($referents as $key => $value) {
              $referents_cc.=$value->mail.',';
            }
            $headers .= "CC: ".rtrim($referents_cc,',');

            $mail = mail($email, '[' . $config['PROJECT_NAME'] . '] Access granted to '.$project.' !', '<html>
               <head>
               <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
               </head>
               <body>
               <p>Hello, '.$referent.' approve you to the project '.$project.',you can access it now! </a>. </p>
               </body>
               </html> ', $headers);

        } else {
            $error = "true";
        }
        return $error;
    }

     public function Send_mail_user_denyaccess_project($email,$project,$referent)
    {
        $connected = self::CheckSMTPstatus();
        if ($connected === true) {
            $file    = new File();
            $config  = $file->ConfigFile();
            $headers = "From:<" . $config['NO_REPLY_MAIL'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $referents_cc='';
            $referents=Projects_access_right::select('users.id_user','users.mail','users.name','users.firstname','users.type')->where('Projects.name', '=',$project )->where('users.type','=','2')->join('Projects', 'id_project', '=', 'Projects.id')->join('users','users.id_user','=','Projects_access_right.id_user')->get();
            foreach ($referents as $key => $value) {
              $referents_cc.=$value->mail.',';
            }
            $headers .= "CC: ".rtrim($referents_cc,',');
            $mail = mail($email, '[' . $config['PROJECT_NAME'] . '] Access denied to '.$project.' !', '<html>
               <head>
               <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
               </head>
               <body>
               <p>Hello, '.$referent.' did not give his authorization for your access to the project '.$project.', If you want more information contact him at his email address</a>. </p>
               </body>
               </html> ', $headers);

        } else {
            $error = "true";
        }
        return $error;
    }







    /**
     * Send a mail to user to notify account activation
     */
    public function Send_mail_account_disable($email)
    {
        $connected = self::CheckSMTPstatus();
        if ($connected === true) {
            $file    = new File();
            $config  = $file->ConfigFile();
            $headers = "From:<" . $config['NO_REPLY_MAIL'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $mail = mail($email, '[' . $config['PROJECT_NAME'] . '] Your account is now disabled!', '<html>
               <head>
               <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
               </head>
               <body>
               <p>Hello your account is now disabled by administrator, you can contact us to <a href="' . $config['REPOSITORY_URL'] . '">' . $config['PROJECT_NAME'] . '</a> for more information. </p>
               </body>
               </html> ', $headers);

        } else {
            $error = "true";
        }
        return $error;
    }

    /**
     * Send a mail to user to notify account activation
     */
    public function Send_mail_account_removed($email)
    {
        $connected = self::CheckSMTPstatus();
        if ($connected === true) {
            $file    = new File();
            $config  = $file->ConfigFile();
            $headers = "From:<" . $config['NO_REPLY_MAIL'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $mail = mail($email, '[' . $config['PROJECT_NAME'] . '] Your account is now removed!', '<html>
               <head>
               <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
               </head>
               <body>
               <p>Hello your account is now removed by administrator, you can contact us to <a href="' . $config['REPOSITORY_URL'] . '">' . $config['PROJECT_NAME'] . '</a> for more information. </p>
               </body>
               </html> ', $headers);

        } else {
            $error = "true";
        }
        return $error;
    }

}
