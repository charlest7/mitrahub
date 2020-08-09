<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\Session;
use App\Repository\UserRepository;


class UserService
{
    public function checkSessionAvailibility(){
        $session = new Session();
        $emailSesssion = $session->get('email');
        $status = false;

        if(!isset($_SESSION['email'])){
            $status = true;
        }

        return $status;
    }

    public function checkSubmissionAndGenerateSession($userEntity, $userPassword){
        $status = true;

        if($userEntity == null){
            $status = 'data is not found';
        }else if($userEntity->getPassword() != $userPassword){
            $status = 'password is wrong';
        }else{
            $status = $this->generateSession($userEntity);
        }

        return $status;
    }

    public function checkSubmissionAndGenerateSessionRegister($userEntity, $userData){
        $status = $this->validateUserInputRegister($userData);

        return $status;
    }

    public function validateUserInputRegister($userData){
        $status = true;

        if($userData->email != "" && $userData->password ==  $userData->repeatPassword
         && $userData->password != ""){
             $status = "data is wrong";
        }

        return $status;
    }

    public function destroySession(){
        $session = new session();
        $emailSesssion = $session->get('email');
        $status = false;

        if($emailSesssion != null){
            session_destroy();
            $status = true;
        }
        
    }

    public function generateSession($userEntity){
        $session = new Session();
      
        $session->set('email', $userEntity->getEmail());
        $session->set('type', $userEntity->getType());
        $session->set('userId', $userEntity->getUserId());

        $session->set('timeout', time());

        return "Session is Generated";
    }

}
?>