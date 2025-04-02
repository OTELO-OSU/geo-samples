<?php

namespace geosamples\backend\controller;

use \geosamples\backend\controller\ConnectController as ConnectDB;
use \geosamples\backend\controller\MailerController as Mailer;
use \geosamples\model\LostPassword as LostPassword;
use \geosamples\model\MailValidation as MailValidation;
use \geosamples\model\Users as Users;
use \geosamples\model\Projects as Projects;
use \geosamples\model\ProjectsAccessRight as Projects_access_right;
use \geosamples\model\ProjectsRequest as ProjectsRequest;
use \geosamples\backend\controller\FileController as File;
use \geosamples\backend\DTO\Project_access_rightDTO;
use geosamples\backend\DTO\ProjectDTO;
use geosamples\backend\DTO\TripleCroisementDTO;
use geosamples\backend\DTO\UserDTO;

class UserController
{
    private $DBinstance;

    public function __construct()
    {
        $ConnectDB = new ConnectDB();
        $DBinstance = $ConnectDB->EloConfigure($_SERVER['DOCUMENT_ROOT'] . '/../AuthDB.ini');
    }
    public function check_current_user($email)
    {
        $verif = Users::find($email);
        if ((count($verif) == 1) && ($verif->status != 0)) {
            return $verif;
        }
    }

    public function login($id, $password)
    {
        $verif = Users::select('mail', 'mdp', 'name', 'firstname', 'status', 'mail_validation', 'type')->where('mail', '=', $id)->get();
        $user = Users::select('mail')->where('mail', '=', $id)->first();
        $id_user = Users::select('id_user')->where('mail', '=', $id)->first();
        if (count($verif) == 0) {
            $error = "No account linked to this email!";
            return $error;
        } else {
            if (!empty($verif[0])) {
                $hash = $verif[0]->mdp;
                if (password_verify($password, $hash)) {
                    if ($verif[0]->status == 0 or $verif[0]->mail_validation == 0) {
                        $error = "Account not verified, please wait for an admin to activate your account";
                        return $error;
                    } else {
                        $this->giveRight($id_user);
                        $_SESSION['name'] = $verif[0]->name;
                        $_SESSION['firstname'] = $verif[0]->firstname;
                        $_SESSION['mail'] = $verif[0]->mail;
                        $_SESSION['admin'] = $verif[0]->type;
                        $file = new File();
                        $config = $file->ConfigFile();
                        $feeder = $this->is_feeder($_SESSION['mail'], $config['COLLECTION_NAME']);
                        $referent = $this->is_referent($_SESSION['mail'], $config['COLLECTION_NAME']);

                        if (($referent === true) or $_SESSION['admin'] == 1) {
                            $_SESSION['access'] = 1;
                        } elseif ($feeder === true) {
                            $_SESSION['access'] = 2;
                        }
                    }
                } else {
                    $error = "Bad password!";
                    return $error;
                }
            }
        }
    }

    public function giveRight($id_user)
    {
        $array = [];
        $array2 = [];
        $Projects = Projects_access_right::select('id_project', 'name', 'user_type')->where('id_user', '=', $id_user->id_user)
            ->join('Projects', 'id_project', '=', 'Projects.id')
            ->get();

        foreach ($Projects as $key => $value) {
            $array[] = new Project_access_rightDTO($value->id_project, $value->name, $value->user_type);
            $array2[$value->id_project] = $value->name;
        }

        $_SESSION['projects_access_right'] = $array;
        $_SESSION['projects_access_right_name'] = $array2;
    }

    public function signup($name, $firstname, $email, $password, $passwordconfirm, $project_name)
    {
        $project = Projects::select('id')->where('name', '=', $project_name[0])->get();

        if (count($project) == 0) {
            $error = "You must select a project!";
        } else {

            $verif = Users::select('mail', 'mdp')->where('mail', '=', $email)->get();
            if (count($verif) == 0) {
                if (preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{8,}/", $password)) {
                    if ($password == $passwordconfirm && empty(!$password)) {
                        $passwordtest = password_hash($password, PASSWORD_DEFAULT);
                        $signup = new Users;
                        $signup->name = $name;
                        $signup->firstname = $firstname;
                        $signup->mail = $email;
                        $signup->mdp = $passwordtest;
                        $signup->type = 0;
                        $signup->status = 0;
                        if ($signup->save()) {
                            $verif = Users::select('id_user')->where('mail', '=', $email)->get();
                            foreach ($project_name as $key => $value) {
                                if ($value != '') {
                                    $project = Projects::select('id')->where('name', '=', $value)->get();
                                    $Projects_request = new ProjectsRequest;
                                    $Projects_request->id_project = $project[0]->id;
                                    $Projects_request->id_user = $verif[0]->id_user;
                                    $Projects_request->save();
                                }
                            }

                            $token = bin2hex(openssl_random_pseudo_bytes(32));
                            $MailValidation = new MailValidation();
                            $MailValidation->mail = $email;
                            $MailValidation->datetime = date("Y-m-d H:i:s");
                            $MailValidation->token = $token;
                            $MailValidation->save();
                            $mail = new Mailer();
                            $mail->Send_Validation_Mail($email, $token);
                        }
                    } else {
                        $error = "Please type identical password!";
                    }
                } else {
                    $error = "Password must have at least one number, one uppercase and one lowercase, and one special characters";
                }
            } else {
                $error = "Email already registred in database !";
            }
        }
        return $error;
    }

    public function activate_account($token)
    {
        $verif = MailValidation::select('token', 'mail', 'datetime')->where('token', '=', $token)->get();
        if (count($verif) != 0) {
            $d2 = new \DateTime(date("Y-m-d H:i:s"));
            $diff = new \DateTime($verif[0]->datetime);
            $diff = $diff->diff($d2);
            if ($diff->format("%H") >= 01) {
                $verif = MailValidation::select('token', 'mail')->where('token', '=', $token)->delete();
                return false;
            } else {
                $write = Users::find($verif[0]->mail);
                $write->mail_validation = 1;
                if ($write->save()) {
                    $mail = new Mailer();
                    $mail->Send_mail_validation($verif[0]->mail);

                    $project = Projects::select('Projects.name')->where('users.mail', '=', $verif[0]->mail)
                        ->join('Projects_request', 'id_project', '=', 'Projects.id')
                        ->join('users', 'users.id_user', '=', 'Projects_request.id_user')
                        ->get();
                    foreach ($project as $key => $project) {
                        //var_dump($value->name);
                        $referents = Projects_access_right::select('users.id_user', 'users.mail', 'users.name', 'users.firstname', 'users.type')->where('Projects.name', '=', $project->name)
                            ->where('Projects_access_right.user_type', '=', '2')
                            ->join('Projects', 'id_project', '=', 'Projects.id')
                            ->join('users', 'users.id_user', '=', 'Projects_access_right.id_user')
                            ->get();
                        //var_dump($referents);
                        foreach ($referents as $key => $value) {
                            //var_dump($value->mail);
                            $mail = new Mailer();
                            $mail->Send_mail_referent_project($value->mail, $project->name, $verif[0]->mail);
                        }
                    }

                    $verif = MailValidation::select('token', 'mail')->where('token', '=', $token)->delete();

                    return true;
                }
            }
        } else {
            return false;
        }
    }

    public function recover_send_mail($email)
    {
        $verif = Users::select('mail')->where('mail', '=', $email)->get();
        if (count($verif) != 0) {
            $verif2 = LostPassword::select('token', 'mail', "datetime")->where('mail', '=', $email)->get();
            if (count($verif2) != 0) {
                $token = bin2hex(openssl_random_pseudo_bytes(32));
                $verif = LostPassword::find($verif2[0]->mail);
                $verif->token = $token;
                $verif->datetime = date("Y-m-d H:i:s");
                $verif->save();
                $mailer = new Mailer();
                $mailer->Send_Reset_Mail($email, $token);
            } else {
                $token = bin2hex(openssl_random_pseudo_bytes(32));
                $lostpassword = new LostPassword;
                $lostpassword->mail = $verif[0]->mail;
                $lostpassword->token = $token;
                $lostpassword->datetime = date("Y-m-d H:i:s");
                $lostpassword->save();
                $mailer = new Mailer();
                $mailer->Send_Reset_Mail($email, $token);
            }
            return "Please check your email and click on the link to reset your password";
        } else {
            return "Mail not found in our database";
        }
    }

    public function check_token($token)
    {
        $verif = LostPassword::select('token', 'mail', "datetime")->where('token', '=', $token)->get();
        if (count($verif) != 0) {
            $d2 = new \DateTime(date("Y-m-d H:i:s"));
            $diff = new \DateTime($verif[0]->datetime);
            $diff = $diff->diff($d2);
            if ($diff->format("%H") >= 01) {
                $verif = LostPassword::select('token', 'mail', "datetime")->where('token', '=', $token)->delete();
                return false;
            } else {
                return $verif[0]->token;
            }
        } else {
            return false;
        }
    }

    public function change_password($token, $password, $passwordconfirm)
    {
        $verif = LostPassword::select('token', 'mail', "datetime")->where('token', '=', $token)->get();
        if (count($verif) != 0) {
            $d2 = new \DateTime(date("Y-m-d H:i:s"));
            $diff = new \DateTime($verif[0]->datetime);
            $diff = $diff->diff($d2);
            if ($diff->format("%H") >= 01) {
                $verif = LostPassword::select('token', 'mail', "datetime")->where('token', '=', $token)->delete();
                return "Invalid Token";
            } else {
                $verif2 = Users::find($verif[0]->mail);
                if (preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{8,}/", $password)) {

                    if ($password == $passwordconfirm && empty(!$password)) {
                        $passwordtest = password_hash($password, PASSWORD_DEFAULT);
                        $verif2->mdp = $passwordtest;
                        if ($verif2->save()) {
                            LostPassword::select('token', 'mail', "datetime")
                                ->where('token', '=', $token)->delete();
                            $mailer = new Mailer();
                            $mailer->Send_password_success($verif[0]->mail);

                            return "Password updated successfully";
                        }
                    } else {
                        return "Please type identical password!";
                    }
                } else {
                    return "Password invalid";
                }
            }
        } else {
            return "Invalid Token";
        }
    }

    public function getUserInfo($email)
    {
        $verif = Users::select('name', 'firstname')->where('mail', '=', $email)->get();
        $_SESSION['name'] = $verif[0]->name;
        $_SESSION['firstname'] = $verif[0]->firstname;
        return $verif;
    }

    public function setUserInfo($email, $name, $firstname)
    {
        $verif = Users::find($email);
        $verif->name = $name;
        $verif->firstname = $firstname;
        if ($verif->save()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retourne tous les users dont le status est 1
     * @return UserDTO[]
     */
    public function getAllUsersApproved()
    {
        $array = [];
        $users = Users::where('status', '=', "1")->get();
        foreach ($users as $user) {
            $array[] = new UserDTO($user->id_user, $user->name, $user->firstname, $user->mail, $user->mdp, $user->status, $user->mail_validation, $user->type);
        }
        return $array;
    }

    /**
     * Retourne tous les users dans le status = 0 et mail_validation = 1
     * @return UserDTO[]
     */
    public function getAllUsersWaiting(): array
    {
        $array = [];
        $users = Users::where('status', '=', "0")->where('mail_validation', '=', "1")->get();
        if (count($users) != 0) {
            foreach ($users as $user) {
                $array[] = new UserDTO($user->id_user, $user->name, $user->firstname, $user->mail, $user->mdp, $user->status, $user->mail_validation, $user->type);
            }
            return $array;
        } else {
            return [];
        }
    }

    /**
     * Permet l'auto complete lors de l'ajout d'un user dans un projet
     */
    public function getAllUsersApprovedAutocomplete()
    {
        if ($_SESSION['admin'] == 1) {
            $verif = Users::where('status', '=', "1")->get();
            foreach ($verif as $key => $value) {
                $obj = new \stdClass();
                $obj->title = $value->mail;
                $array[] = $obj;
            }
            return $array;
        } else {

            foreach ($_SESSION['projects_access_right'] as $key => $value) {
                if ($value->user_type == 2) {

                    $verif = Users::where('status', '=', "1")->get();
                    foreach ($verif as $key => $value) {
                        $obj = new \stdClass();
                        $obj->title = $value->mail;
                        $array[] = $obj;
                    }
                    return $array;
                }
            }
        }
    }

    /**
     * Récupère tous les projets sous forme de array contenant des ProjectsDTO
     * @return ProjectDTO[]
     */
    public function getAllProject(): array
    {
        $verif = Projects::all();
        $array = [];
        if (count($verif) != 0) {
            foreach ($verif as $pro) {
                $array[] = new ProjectDTO($pro->id, $pro->name);
            }
            return $array;
        } else {
            return [];
        }
    }

    /**
     * User qui attendent une validation pour rentrer dans un projet
     * @return UserDTO[]
     */
    public function getUserAwaitingValidationFromReferent(array $project_array): array
    {
        $array = [];
        foreach ($project_array as $pro) {
            $verif = ProjectsRequest::select('users.id_user', 'users.mail', 'users.name', 'users.firstname', 'users.type', 'Projects.name as project_name')->where('Projects.name', '=', $pro->name)->where('users.mail_validation', '=', '1')
                ->join('Projects', 'id_project', '=', 'Projects.id')
                ->join('users', 'users.id_user', '=', 'Projects_request.id_user')
                ->get();
            if (count($verif) != 0) {
                foreach ($verif as $row) {
                    $array[] = new TripleCroisementDTO($row->id_user, $row->mail, $row->name, $row->firstname, $row->user_type, $row->project_name);
                }
            }
        }
        if (count($array) != 0) {
            return $array;
        } else {
            return [];
        }
    }

    /**
     * Retourne les projets avec sa liste de users pour les réferents
     * @return array[nom_projet] => TripleCroisementDTO[]
     */
    public function getReferentProjectsUsers()
    {
        $users = [];

        //Admin
        if ($_SESSION['admin'] == 1) {

            //Récupère tous les projets
            $projects = $this->getAllProject();

            //Pour chaque projets
            foreach ($projects as $pro) {
                $users[$pro->name] = $this->getUserInProjectForReferent($pro->name);
            }
        } else {
            foreach ($_SESSION['projects_access_right'] as $pro) {
                if (($pro->user_type == 2)) {
                    $users[$pro->name] = $this->getUserInProjectForReferent($pro->name);
                }
            }
        }

        return $users;
    }

    /**
     * Récupère les users dans les projets pour l'ui du référent
     * @return TripleCroisementDTO[]
     *
     */
    public function getUserInProjectForReferent($project_name)
    {
        $array = [];
        $verif = Projects_access_right::select('users.id_user', 'users.mail', 'users.name', 'users.firstname', 'Projects_access_right.user_type', 'Projects.name as project_name')
            ->join('Projects', 'id_project', '=', 'Projects.id')
            ->join('users', 'users.id_user', '=', 'Projects_access_right.id_user')
            ->whereraw('(user_type = 0 or user_type = 3 or user_type = 2)')
            ->where('Projects.name', '=', $project_name)->get();

        if (count($verif) != 0) {
            foreach ($verif as $row) {
                $array[] = new TripleCroisementDTO($row->id_user, $row->mail, $row->name, $row->firstname, $row->user_type, $row->project_name);
            }
            return $array;
        } else {
            return [];
        }
    }

    public function getProjectReferent($project_name)
    {

        $verif = Projects_access_right::select('users.id_user', 'users.mail', 'users.name', 'users.firstname', 'Projects_access_right.user_type', 'Projects.name as project_name')->join('Projects', 'id_project', '=', 'Projects.id')
            ->join('users', 'users.id_user', '=', 'Projects_access_right.id_user')
            ->whereraw('(user_type = 2)')
            ->where('Projects.name', '=', $project_name)->get();
        if (count($verif) != 0) {
            foreach ($verif as $key => $value) {
                $array[] = $value;
            }
            return $array;
        } else {
            return false;
        }
    }

    public function getReferentProject()
    {
        $array = [];
        foreach ($_SESSION['projects_access_right'] as $pro) {
            if (($pro->user_type == 2) || ($_SESSION['admin'] == 1)) {
                $row = Projects::select("id", "name")
                    ->where("name", $pro->name)
                    ->first();
                $array[] = new ProjectDTO($row->id, $row->name);
            }
        }
        return $array;
    }

    public function getNotReferentProject()
    {
        $array = [];

        foreach ($_SESSION['projects_access_right'] as $pro) {
            if (($pro->user_type != 2)) {
                $array[$pro->user_type][] = $pro->name;
            }
        }
        return $array;
    }

    public function getProjectForUser($mail)
    {
        $verif = Projects_access_right::select('id_project', 'Projects.name')->where('users.mail', '=', $mail)->join('Projects', 'id_project', '=', 'Projects.id')
            ->join('users', 'users.id_user', '=', 'Projects_access_right.id_user')
            ->get();
        if (count($verif) != 0) {
            foreach ($verif as $key => $value) {
                $array[$value
                    ->id_project] = $value->name;
            }
            return $array;
        } else {
            return false;
        }
    }

    public function getUserInProject($project_name)
    {
        if ($_SESSION['admin'] == 1) {
            $verif = Projects_access_right::select('users.id_user', 'users.mail', 'users.name', 'users.firstname', 'users.type')
                ->join('Projects', 'id_project', '=', 'Projects.id')
                ->join('users', 'users.id_user', '=', 'Projects_access_right.id_user')
                ->where('Projects.name', '=', $project_name)
                ->get();
            if (count($verif) != 0) {
                foreach ($verif as $key => $value) {
                    $array[] = $value;
                }
                return $array;
            } else {
                return false;
            }
        } else {
            foreach ($_SESSION['projects_access_right'] as $key => $value) {
                if ($project_name == $value->name and $value->user_type == 2) {
                    $verif = Projects_access_right::select('users.id_user', 'users.mail', 'users.name', 'users.firstname', 'users.type')->where('Projects.name', '=', $project_name)->join('Projects', 'id_project', '=', 'Projects.id')
                        ->join('users', 'users.id_user', '=', 'Projects_access_right.id_user')
                        ->get();
                    if (count($verif) != 0) {
                        foreach ($verif as $key => $value) {
                            $array[] = $value;
                        }
                        return $array;
                    } else {
                        return false;
                    }
                }
            }
        }
    }

    public function AddUserToProject($mail, $project_name)
    {
        $this->approveUser($mail);
        $user_id = users::select('id_user')->where('mail', '=', $mail)->get();
        $project_id = Projects::select('id')->where('name', '=', $project_name)->get();
        ProjectsRequest::select('id')
            ->where('id_user', '=', $user_id[0]->id_user)
            ->where('id_project', '=', $project_id[0]->id)
            ->delete();
        $exist = Projects_access_right::select('id')->where('id_user', '=', $user_id[0]->id_user)
            ->where('id_project', '=', $project_id[0]->id)
            ->get();
        if (count($user_id) != 0 && count($project_id) != 0 && (count($exist) == 0)) {
            $verif = new Projects_access_right;
            $verif->id_user = $user_id[0]->id_user;
            $verif->id_project = $project_id[0]->id;
            $verif->user_type = 0;
            if ($verif->save()) {
                $mailer = new Mailer();
                $mailer->Send_mail_user_welcome_project($mail, $project_name, $_SESSION['mail']);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function DeleteUserFromProject($mail, $project_name)
    {
        $user_id = users::select('id_user')->where('mail', '=', $mail)->get();
        $project_id = Projects::select('id')->where('name', '=', $project_name)->get();
        ProjectsRequest::select('id')
            ->where('id_user', '=', $user_id[0]->id_user)
            ->where('id_project', '=', $project_id[0]->id)
            ->delete();
        if (count($user_id) != 0 && count($project_id) != 0) {

            if ($exist = Projects_access_right::select('id')->where('id_user', '=', $user_id[0]->id_user)
                ->where('id_project', '=', $project_id[0]->id)
                ->delete()
            ) {
                $mailer = new Mailer();
                $mailer->Send_mail_user_denyaccess_project($mail, $project_name, $_SESSION['mail']);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function is_referent($email, $project_name)
    {
        $verif = Projects_access_right::select('Projects_access_right.user_type')->join('Projects', 'Projects.id', '=', 'Projects_access_right.id_project')
            ->join('users', 'users.id_user', '=', 'Projects_access_right.id_user')
            ->where('Projects.name', '=', $project_name)->where('users.mail', '=', $email)->get();
        if ($verif[0]->user_type == 2) {
            return true;
        } else {
            return false;
        }
    }

    public function is_feeder($email, $project_name)
    {
        $verif = Projects_access_right::select('Projects_access_right.user_type')->join('Projects', 'Projects.id', '=', 'Projects_access_right.id_project')
            ->join('users', 'users.id_user', '=', 'Projects_access_right.id_user')
            ->where('Projects.name', '=', $project_name)->where('users.mail', '=', $email)->get();
        if ($verif[0]->user_type == 3) {
            return true;
        } else {
            return false;
        }
    }

    public function Create_project($name)
    {
        $test = Projects::where('name', '=', $name)->get();
        if (count($test) >= 1) {
            return 'Project ' . $name . ' already exist';
        } else {
            $verif = new Projects;
            $verif->name = $name;
            if ($verif->save()) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function approveUser($email)
    {
        $verif = Users::find($email);
        if ($verif->status == 0) {
            $verif->status = 1;
            if ($verif->save()) {
                $mail = new Mailer();
                $mail->Send_mail_account_activation($email);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function disableUser($email)
    {
        $verif = Users::find($email);
        $verif->status = 0;
        if ($verif->save()) {
            $mail = new Mailer();
            $mail->Send_mail_account_disable($email);
            return true;
        } else {
            return false;
        }
    }

    public function SetRightUser($email, $type, $project_name)
    {
        if ($_SESSION['admin'] == 1) {
            $verif = Projects::select('Projects_access_right.id')->join('Projects_access_right', 'id_project', '=', 'Projects.id')
                ->join('users', 'users.id_user', '=', 'Projects_access_right.id_user')
                ->where('Projects.name', '=', $project_name)->where('users.mail', '=', $email)->get();
            $verif = Projects_access_right::find($verif[0]->id);
            $verif->user_type = $type;
            if ($verif->save()) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function modifyUser($email, $name, $firstname, $type)
    {
        $verif = Users::find($email);
        $verif->name = $name;
        $verif->firstname = $firstname;
        $verif->type = $type;
        if ($verif->save()) {
            return true;
        } else {
            return false;
        }
    }

    public function deleteUser($email)
    {
        $verif = Users::find($email)->delete();

        //Projects_access_right::select('Projects_access_right.id')->join('users','Projects_access_right.id_user', '=', 'users.id_user')->where('users.mail','=',$email)->delete();
        if ($verif == true) {
            $mail = new Mailer();
            $mail->Send_mail_account_removed($email);
            return true;
        } else {
            return false;
        }
    }
}
