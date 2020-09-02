<?php

namespace CommissionBundle\Command;

use DbBundle\Entity\User;
use DbBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

abstract class AbstractCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Argument description');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createRequest();
        $this->loginUser($input->getArgument('username'));
    }
    
    protected function createRequest(): Request
    {
        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->getRequestStack()->push($request);

        return $request;
    }
    
    protected function loginUser(string $username): User
    {
        $user = $this->getUserRepository()->loadUserByUsername($username);
        if ($user === null) {
            throw new UsernameNotFoundException('User not found');
        }
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->getContainer()->get('security.token_storage')->setToken($token);

        $event = new InteractiveLoginEvent(new Request(), $token);
        $this->getContainer()->get('event_dispatcher')->dispatch('security.interactive_login', $event);

        return $user;
    }

    protected function loginApiGateway(){
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->getContainer()->getParameter('api_gateway_url')."/auth/login",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => '{"username":"'.$this->getContainer()->getParameter('mservice.username').'","password":"'.$this->getContainer()->getParameter('mservice.password').'","userType":2}',
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json"
          )
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
          return false;
        } 

        return $response;
    }

    protected function updatePeriod(string $period, string $token, string $type){
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->getContainer()->getParameter('api_gateway_url')."/revenue-share/periods/".$period."/".$type,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$token
          )
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
          return false;
        } 

        return $response;
    }

    protected function getToken($response): string{
        $response = json_decode($response, true);
        $token = array_get($response, 'token', false);

        return $token;
    }
    
    protected function getRequestStack(): RequestStack
    {
        return $this->getContainer()->get('request_stack');
    }

    protected function getUserRepository(): UserRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(User::class);
    }
    
    protected function getEntityManager(): EntityManager
    {
        return $this->getDoctrine()->getManager();
    }
    
    protected function getDoctrine(): Registry
    {
        return $this->getContainer()->get('doctrine');
    }
}
